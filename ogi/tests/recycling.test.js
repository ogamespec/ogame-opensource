/**
 * Tests of the recycling yield (debris calculator) and of the parts of the battle
 * report parser it needs: the destroyed units and the round count.
 *
 * The expected numbers are taken from the engine's `CalcDebris()`
 * (game/core/battle.php), which multiplies the **resource cost** of a unit
 * (TechPrice($gid, 1)) by the destroyed amount and the universe debris percentage,
 * rounding up per unit id and resource:
 *
 *     L.Fighter  = 3000 metal + 1000 crystal, 30%  ->  900 metal + 300 crystal = 1200
 *     R.Launcher = 2000 metal,               30%  ->  600 metal
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const templates = require('../src/lib/templates.js');
const battle = require('../src/parsers/battle.js');
const recycling = require('../src/analyze/recycling.js');

const loca = templates.createLoca(locaData);

test('battle report: destroyed units are the first composition minus the last one', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const source = helpers.readGolden(lang, 'bericht_battle_p0.html');
        const report = battle.parseBattleReport(source, { loca: loca, lang: lang });

        // The report has the "before the battle" pair of tables and one round.
        assert.equal(report.roundCount, 1, lang + ': rounds in the structure');
        assert.equal(report.rounds, 1, lang + ': rounds counted from the text');
        assert.equal(battle.splitGroups(source).length, 4, lang + ': participant groups');

        // The attacker keeps all of its 10 Light Fighters, the defender loses all 3
        // Rocket Launchers (its slot shows "destroyed", so it has no unit table).
        assert.equal(report.destroyed.attackers.length, 1, lang + ': attacker slots');
        assert.deepEqual(report.destroyed.attackers[0].units, {}, lang + ': attacker losses');
        assert.equal(report.destroyed.attackers[0].name, 'PlayerOne', lang + ': attacker name');
        assert.deepEqual(report.destroyed.attackers[0].coords, { g: 1, s: 1, p: 4 }, lang + ': attacker coords');

        assert.equal(report.destroyed.defenders.length, 1, lang + ': defender slots');
        assert.deepEqual(report.destroyed.defenders[0].units, { 401: 3 }, lang + ': defender losses');
        assert.equal(report.destroyed.defenders[0].name, 'PlayerTwo', lang + ': defender name');
        assert.deepEqual(report.destroyed.defenders[0].coords, { g: 1, s: 3, p: 4 }, lang + ': defender coords');
    }
});

test('battle report: a report without rounds does not throw', function () {
    const short = loca.get('en', 'BATTLE_LOST') + ' <!--A:167658,W:167658-->';
    const report = battle.parseBattleReport(short, { loca: loca, lang: 'en' });
    assert.equal(report.roundCount, 0);
    assert.deepEqual(report.destroyed, { attackers: [], defenders: [] });
    assert.deepEqual(report.losses, { attacker: 167658, defender: 167658 }, 'the comment still carries the losses');

    // Nothing to recycle anywhere in here.
    const empty = recycling.battleRecycling(report, unitsData);
    assert.deepEqual(empty.total, { metal: 0, crystal: 0, deuterium: 0, total: 0 });
    assert.deepEqual(recycling.battleRecycling(null, unitsData).total, { metal: 0, crystal: 0, deuterium: 0, total: 0 });
    assert.deepEqual(recycling.recyclingYield(null, unitsData), { metal: 0, crystal: 0, deuterium: 0, total: 0 });
});

test('recycling: debris of a ship is a percentage of its cost', function () {
    // L.Fighter: 3000 metal, 1000 crystal, structure 4000 -> 30% = 1200 in total.
    assert.deepEqual(recycling.recyclingYield({ 204: 1 }, unitsData), {
        metal: 900, crystal: 300, deuterium: 0, total: 1200,
    });

    // The amounts add up linearly, per unit id.
    assert.deepEqual(recycling.recyclingYield({ 204: 2, 202: 1 }, unitsData), {
        metal: 900 * 2 + 600, crystal: 300 * 2 + 600, deuterium: 0, total: 3600,
    });
});

test('recycling: defence produces debris only when the universe allows it', function () {
    // 0.84 default: uni_did = 0 (game/install.php), so defence leaves nothing behind.
    assert.deepEqual(recycling.recyclingYield({ 401: 3 }, unitsData), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });
    assert.deepEqual(recycling.recyclingYield({ 401: 3 }, unitsData, { debrisFactor: 30, debrisFactorDef: 0 }), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });

    // Rocket Launcher: 2000 metal -> 30% = 600 metal for every lost launcher.
    assert.deepEqual(recycling.recyclingYield({ 401: 3 }, unitsData, { debrisFactorDef: 30 }), {
        metal: 1800, crystal: 0, deuterium: 0, total: 1800,
    });

    // Missiles are defence as well ($defmap of the engine contains them).
    assert.deepEqual(recycling.recyclingYield({ 502: 1 }, unitsData, { debrisFactorDef: 30 }), {
        metal: 2400, crystal: 0, deuterium: 0, total: 2400,
    });
});

test('recycling: deuterium in debris needs the universe flag', function () {
    // Cruiser: 20000 metal, 7000 crystal, 2000 deuterium.
    const without = recycling.recyclingYield({ 206: 5 }, unitsData);
    assert.deepEqual(without, { metal: 30000, crystal: 10500, deuterium: 0, total: 40500 });

    const withDeuterium = recycling.recyclingYield({ 206: 5 }, unitsData, { deuteriumInDebris: true });
    assert.deepEqual(withDeuterium, { metal: 30000, crystal: 10500, deuterium: 3000, total: 43500 });
});

test('recycling: the options default to a 0.84 universe', function () {
    assert.deepEqual(recycling.normalizeOptions(), { debrisFactor: 30, debrisFactorDef: 0, deuteriumInDebris: false });
    assert.deepEqual(recycling.normalizeOptions({ debrisFactor: 70, debrisFactorDef: 40, deuteriumInDebris: true }), {
        debrisFactor: 70, debrisFactorDef: 40, deuteriumInDebris: true,
    });
    // Nonsense values fall back to the defaults instead of poisoning the numbers.
    assert.deepEqual(recycling.normalizeOptions({ debrisFactor: -5, debrisFactorDef: 'x', deuteriumInDebris: 0 }), {
        debrisFactor: 30, debrisFactorDef: 0, deuteriumInDebris: false,
    });
    // A universe without debris at all: everything is zero.
    assert.deepEqual(recycling.recyclingYield({ 204: 10 }, unitsData, { debrisFactor: 0, debrisFactorDef: 0 }), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });
});

test('recycling: unknown units and empty amounts produce nothing', function () {
    // A unit the object tables do not know (an unmapped locale name, a mod unit)
    // cannot be valued, so it contributes no debris - like an object without a cost.
    assert.deepEqual(recycling.recyclingYield({ 999999: 5 }, unitsData), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });
    assert.deepEqual(recycling.recyclingYield({ 'Colony Ship': 2 }, unitsData), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });
    assert.deepEqual(recycling.recyclingYield({ 204: 0 }, unitsData), {
        metal: 0, crystal: 0, deuterium: 0, total: 0,
    });
});

test('recycling: a parsed battle report yields the debris of both sides', function () {
    const report = battle.parseBattleReport(helpers.readGolden('en', 'bericht_battle_p0.html'), {
        loca: loca,
        lang: 'en',
    });

    // With the 0.84 defaults the whole field of this report is empty: the attacker
    // lost nothing and defence debris is switched off.
    const defaults = recycling.battleRecycling(report, unitsData);
    assert.deepEqual(defaults.attackers, { metal: 0, crystal: 0, deuterium: 0, total: 0 });
    assert.deepEqual(defaults.defenders, { metal: 0, crystal: 0, deuterium: 0, total: 0 });
    assert.equal(defaults.total.total, 0);

    // A universe with defence debris: the 3 lost Rocket Launchers leave 1800 metal.
    // (The hand-built golden fixture states "100 metal and 50 crystal" in its text;
    // that sentence does not match its own composition, which is why the calculator
    // works from the compositions instead.)
    const withDefence = recycling.battleRecycling(report, unitsData, { debrisFactorDef: 30 });
    assert.deepEqual(withDefence.defenders, { metal: 1800, crystal: 0, deuterium: 0, total: 1800 });
    assert.deepEqual(withDefence.attackers, { metal: 0, crystal: 0, deuterium: 0, total: 0 });
    assert.deepEqual(withDefence.total, { metal: 1800, crystal: 0, deuterium: 0, total: 1800 });

    // Both sides at once, from a synthetic report object (an attacker that lost 3
    // Light Fighters and a defender that lost 5 Rocket Launchers).
    const both = recycling.battleRecycling({
        destroyed: {
            attackers: [{ name: 'A', coords: { g: 1, s: 1, p: 1 }, units: { 204: 3 } }],
            defenders: [{ name: 'D', coords: { g: 1, s: 1, p: 2 }, units: { 401: 5 } }],
        },
    }, unitsData, { debrisFactor: 30, debrisFactorDef: 30 });
    assert.deepEqual(both.attackers, { metal: 2700, crystal: 900, deuterium: 0, total: 3600 });
    assert.deepEqual(both.defenders, { metal: 3000, crystal: 0, deuterium: 0, total: 3000 });
    assert.deepEqual(both.total, { metal: 5700, crystal: 900, deuterium: 0, total: 6600 });
});

test('recycling: recyclers are counted per 20000 cargo and rounded up', function () {
    assert.equal(recycling.recyclersNeeded(0, unitsData), 0);
    assert.equal(recycling.recyclersNeeded(-100, unitsData), 0);
    assert.equal(recycling.recyclersNeeded(1, unitsData), 1);
    assert.equal(recycling.recyclersNeeded(20000, unitsData), 1);
    assert.equal(recycling.recyclersNeeded(20001, unitsData), 2);
    assert.equal(recycling.recyclersNeeded(40000, unitsData), 2);
    assert.equal(recycling.recyclersNeeded(40001, unitsData), 3);
    assert.equal(recycling.recyclersNeeded(NaN, unitsData), 0);

    // The mixed form: metal + crystal + deuterium of a debris field.
    assert.equal(recycling.recyclersNeeded({ metal: 30000, crystal: 10000 }, unitsData), 2);
    assert.equal(recycling.recyclersNeeded({ metal: 30000, crystal: 10000, deuterium: 5000 }, unitsData), 3);
    assert.equal(recycling.recyclersNeeded({ total: 19000 }, unitsData), 1);

    // The cargo capacity comes from the object tables, so another universe (or a
    // modification) can change it.
    const custom = { params: { 209: { cargo: 5000 } }, costs: unitsData.costs, lists: unitsData.lists };
    assert.equal(recycling.recyclersNeeded(40000, custom), 8);
});

test('recycling: profit compares loot, own debris and own losses', function () {
    const bad = recycling.profit(3500, { metal: 900, crystal: 300 }, 5000);
    assert.deepEqual(bad, { loot: 3500, ownYield: 1200, ownLosses: 5000, net: -300, profitable: false });

    const good = recycling.profit(10000, 0, 5000);
    assert.equal(good.net, 5000);
    assert.equal(good.profitable, true);

    // A loot object is accepted as well.
    assert.equal(recycling.profit({ metal: 2000, crystal: 1000, deuterium: 500 }, 0, 0).loot, 3500);
    assert.equal(recycling.profit(null, null, null).net, 0);
});
