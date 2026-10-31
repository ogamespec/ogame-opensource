/**
 * Tests of the parsers against the golden snapshots of the real 0.84 pages.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const locaData = require('../src/data/loca.js');
const templates = require('../src/lib/templates.js');
const messages = require('../src/parsers/messages.js');
const spy = require('../src/parsers/spy.js');
const battle = require('../src/parsers/battle.js');
const texts = require('../src/parsers/texts.js');
const galaxy = require('../src/parsers/galaxy.js');

const loca = templates.createLoca(locaData);
const LANGS = ['en', 'ru', 'de', 'fr', 'jp', 'es', 'it'];

/**
 * Substitute #N placeholders the way the server's va() helper does.
 *
 * @param {string} template Localization string.
 * @param {Array} values Values.
 * @returns {string} Rendered text.
 */
function va(template, values) {
    return String(template).replace(/#(\d)/g, function (match, index) {
        const value = values[Number(index) - 1];
        return value === undefined ? match : value;
    });
}

test('message list: folders, ids, dates and subjects of every folder', function () {
    const source = helpers.readGolden('en', 'messages_p0.html');
    const page = messages.parseMessagesPage(source, { loca: loca, lang: 'en', now: helpers.NOW });

    assert.equal(page.folders.length, 6);
    assert.deepEqual(page.folders.map(function (folder) { return folder.pm; }), [1, 2, 3, 4, 0, 5]);

    const byId = {};
    for (const message of page.messages) byId[message.id] = message;

    assert.equal(byId[5].kind, 'battle');
    assert.equal(byId[5].result, 'iwon');
    assert.equal(byId[5].side, 'attacker');
    assert.deepEqual(byId[5].losses, { defender: 100000, attacker: 50000 });
    assert.equal(byId[5].reportId, 4);
    assert.deepEqual(byId[5].coords, { g: 1, s: 3, p: 4 });

    assert.equal(byId[3].kind, 'spy');
    assert.equal(byId[3].bodyHtml.indexOf('Fleet') !== -1, true, 'spy report is inlined in the message body');
    assert.equal(byId[6].kind, 'expedition');
    assert.equal(byId[6].bodyHtml.indexOf('expedition') !== -1, true);
    assert.equal(messages.parseGameDate('09-01 10:27:15', helpers.NOW), 1788258435);
});

test('message list: the folder tells the type of unclassified messages', function () {
    const source = helpers.readGolden('en', 'messages_private_p0.html');
    const page = messages.parseMessagesPage(source, { loca: loca, lang: 'en', pm: 0, now: helpers.NOW });
    assert.ok(page.messages.length >= 2);
    for (const message of page.messages) assert.equal(message.kind, 'message');
});

test('spy report: planet, owner, resources and fleet in every language', function () {
    for (const lang of LANGS) {
        const source = helpers.readGolden(lang, 'bericht_spy_p0.html');
        const report = spy.parseSpyReport(source, { loca: loca, lang: lang });
        assert.ok(report, lang + ': report not recognized');
        assert.equal(report.planet, 'Home', lang + ': planet');
        assert.equal(report.player, 'PlayerTwo', lang + ': player');
        assert.deepEqual(report.coords, { g: 1, s: 3, p: 4, text: '1:3:4' }, lang + ': coordinates');
        assert.equal(report.resources[700], 20000, lang + ': metal');
        assert.equal(report.resources[701], 12000, lang + ': crystal');
        assert.equal(report.resources[702], 6000, lang + ': deuterium');
        assert.equal(report.resources[703], 53, lang + ': energy');
        assert.deepEqual(report.fleet, { 202: 5, 204: 8, 210: 3 }, lang + ': fleet');
        assert.deepEqual(report.defence, { 401: 3, 402: 2 }, lang + ': defence');
        assert.equal(Object.keys(report.buildings).length, 11, lang + ': buildings');
        assert.equal(report.counter, 0, lang + ': spy counter');
    }
});

test('battle report: result, loot, losses, debris and participants', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const source = helpers.readGolden(lang, 'bericht_battle_p0.html');
        const report = battle.parseBattleReport(source, { loca: loca, lang: lang });
        assert.equal(report.result, 'awon', lang + ': result');
        assert.deepEqual(report.loot, { metal: 2000, crystal: 1000, deuterium: 500, total: 3500 }, lang + ': loot');
        assert.deepEqual(report.losses, { attacker: 0, defender: 3000 }, lang + ': losses');
        assert.deepEqual(report.debris, { metal: 100, crystal: 50 }, lang + ': debris');
        assert.equal(report.repaired, true, lang + ': repaired defence');
        assert.equal(report.rounds, 1, lang + ': rounds');
        assert.equal(report.attackers[0].name, 'PlayerOne', lang + ': attacker');
        assert.deepEqual(report.attackers[0].units, { 204: 10 }, lang + ': attacker units');
        assert.equal(report.defenders[0].name, 'PlayerTwo', lang + ': defender');
        assert.deepEqual(report.defenders[0].units, { 401: 3 }, lang + ': defender units');
    }
});

test('battle report: the shortened report keeps the losses in its comment', function () {
    const source = loca.get('en', 'BATTLE_LOST') + ' <!--A:167658,W:167658-->';
    const report = battle.parseBattleReport(source, { loca: loca, lang: 'en' });
    assert.equal(report.lost, true);
    assert.deepEqual(report.losses, { attacker: 167658, defender: 167658 });
});

test('expedition message: outcome, size and the found resources', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const ctx = { loca: loca, lang: lang };

        const resources = loca.get(lang, 'EXP_RESFOUND_SMALL_1') +
            va(loca.get(lang, 'EXP_FOUND'), ['12.345', loca.get(lang, 'NAME_700')]);
        const found = texts.parseExpedition(resources, ctx);
        assert.equal(found.type, 'resources', lang + ': type');
        assert.equal(found.size, 'small', lang + ': size');
        assert.equal(found.resource.gid, 700, lang + ': resource id');
        assert.equal(found.resource.amount, 12345, lang + ': amount');

        const darkMatter = loca.get(lang, 'EXP_DMFOUND_MED_1') +
            va(loca.get(lang, 'EXP_FOUND'), ['500', loca.get(lang, 'NAME_704')]);
        const dm = texts.parseExpedition(darkMatter, ctx);
        assert.equal(dm.type, 'darkmatter', lang + ': dm type');
        assert.equal(dm.resource.gid, 704, lang + ': dm id');
        assert.equal(dm.resource.amount, 500, lang + ': dm amount');

        const ships = loca.get(lang, 'EXP_FLEET_MED_1') + loca.get(lang, 'EXP_FLEET_FOUND') +
            '<br>' + loca.get(lang, 'NAME_203') + ' 5<br>' + loca.get(lang, 'NAME_204') + ' 3';
        const fleet = texts.parseExpedition(ships, ctx);
        assert.equal(fleet.type, 'ships', lang + ': ships type');
        assert.deepEqual(fleet.ships, { 203: 5, 204: 3 }, lang + ': found ships');

        const nothing = texts.parseExpedition(loca.get(lang, 'EXP_NOTHING_3'), ctx);
        assert.equal(nothing.type, 'nothing', lang + ': nothing type');
    }
});

test('expedition message: the real golden message is classified', function () {
    const source = helpers.readGolden('en', 'messages_expedition_p0.html');
    const page = messages.parseMessagesPage(source, { loca: loca, lang: 'en', pm: 3, now: helpers.NOW });
    const expedition = page.messages[0];
    const result = texts.parseExpedition(expedition.bodyHtml, { loca: loca, lang: 'en' });
    assert.equal(result.type, 'ships');
    assert.equal(result.size, 'small');
    assert.equal(result.key, 'EXP_FLEET_SMALL_1');
});

test('fleet messages: recycling and transports', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const ctx = { loca: loca, lang: lang };

        const recycle = va(loca.get(lang, 'FLEET_RECYCLE'), ['10', '200.000', '150.000', '75.000', '150.000', '75.000']);
        const harvest = texts.parseRecycle(recycle, ctx);
        assert.equal(harvest.recyclers, 10, lang + ': recyclers');
        assert.deepEqual(harvest.harvested, { metal: 150000, crystal: 75000, deuterium: 0 }, lang + ': harvested');

        const out = va(loca.get(lang, 'FLEET_TRANSPORT_OWN'),
            ['<a onclick="showGalaxy(1,3,4);" href="#">[1:3:4]</a>', '1.000', '2.000', '3.000']);
        const transport = texts.parseTransportOwn(out, ctx);
        assert.deepEqual(transport.delivered, { metal: 1000, crystal: 2000, deuterium: 3000 }, lang + ': delivered');
        assert.deepEqual(transport.coords, { g: 1, s: 3, p: 4, text: '1:3:4' }, lang + ': transport coordinates');

        const incoming = va(loca.get(lang, 'FLEET_TRANSPORT_OTHER'),
            ['PlayerTwo', 'Home', '<a onclick="showGalaxy(1,3,4);" href="#">[1:3:4]</a>',
                '1.000', '2.000', '3.000', '0', '0', '0', '1.000', '2.000', '3.000']);
        const delivery = texts.parseTransportOther(incoming, ctx);
        assert.equal(delivery.player, 'PlayerTwo', lang + ': delivering player');
        assert.equal(delivery.delivered.metal, 1000, lang + ': delivered metal');
    }
});

test('galaxy page: planets, owners, moons and activity', function () {
    const source = helpers.readGolden('en', 'galaxy_p0.html');
    const page = galaxy.parseGalaxyPage(source, { loca: loca, lang: 'en' });
    assert.equal(page.planets.length, 2);
    assert.deepEqual(page.planets[0].coords, { g: 1, s: 1, p: 4 });
    assert.equal(page.planets[0].name, 'Home');
    assert.equal(page.planets[0].owner, 'PlayerOne');
    assert.equal(page.planets[0].moon.name, 'Moon');
    assert.deepEqual(page.planets[0].moon.coords, { g: 1, s: 1, p: 4 });

    const other = galaxy.parseGalaxyPage(helpers.readGolden('en', 'galaxy_system3_p0.html'), { loca: loca, lang: 'en' });
    assert.equal(other.planets[0].owner, 'PlayerTwo');
    assert.equal(other.planets[0].coords.s, 3);
});

test('galaxy page: activity markers and debris fields', function () {
    const active = '<tr><th width="30"><a href="#">4</a></th>' +
        '<th width="30"><a onmouseover=\'return overlib("<table width=240><tr><td class=c colspan=2 >Planet Home [1:1:4]</td></tr></table>", STICKY);\' onmouseout="return nd();">' +
        '<img src="planeten/small/x.jpg" height="30" width="30"/></a></th>' +
        '<th width="130" style=\'white-space: nowrap;\'>Home&nbsp;(*)</th>' +
        '<th width="30"></th>' +
        '<th width="30"><a onmouseover="return overlib(\'x\')" href="#" onclick="doit(8, 1, 1, 4, 2, 25)"><img src="planeten/debris.jpg"/></a></th>' +
        '<th width="150"><a onmouseover="return overlib(\'Player PlayerOne.\')" onmouseout="return nd();"><span class="normal">PlayerOne</span></a></th>' +
        '<th width="80"></th><th width="125"></th></tr>';
    const page = galaxy.parseGalaxyPage('<table>' + active + '</table>', { loca: loca, lang: 'en' });
    assert.equal(page.planets.length, 1);
    assert.equal(page.planets[0].activity, '*');
    assert.equal(page.planets[0].debrisHarvesters, 25);
    assert.equal(page.planets[0].name, 'Home');

    const idle = '<tr><th width="30"><a href="#">5</a></th>' +
        '<th width="30"><a onmouseover=\'return overlib("<td class=c colspan=2 >Planet Colony [1:2:5]</td>")\' onmouseout="return nd();"><img src="x.jpg"/></a></th>' +
        '<th width="130">Colony&nbsp;(42 min)</th><th width="30"></th><th width="30"></th>' +
        '<th width="150"><span class="normal">PlayerThree</span></th><th width="80"></th><th width="125"></th></tr>';
    const idlePage = galaxy.parseGalaxyPage('<table>' + idle + '</table>', { loca: loca, lang: 'en' });
    assert.equal(idlePage.planets[0].activity, '42');
    assert.equal(idlePage.planets[0].name, 'Colony');
});
