/**
 * OGame Infinity 0.84 — data collection.
 *
 * Turns game pages into database records. This module is pure (no DOM, no
 * network): the browser part (src/pages/*.js) only fetches the pages and hands
 * them over, which keeps the whole pipeline testable against the golden snapshots
 * of testing/golden/.
 */
(function (root) {
    'use strict';

    const messages = typeof require === 'function' ? require('../parsers/messages.js') : root.OGI;
    const spyParser = typeof require === 'function' ? require('../parsers/spy.js') : root.OGI;
    const battleParser = typeof require === 'function' ? require('../parsers/battle.js') : root.OGI;
    const textParser = typeof require === 'function' ? require('../parsers/texts.js') : root.OGI;
    const statisticsParser = typeof require === 'function' ? require('../parsers/statistics.js') : root.OGI;
    const imperiumParser = typeof require === 'function' ? require('../parsers/imperium.js') : root.OGI;

    // Message folders of the 0.84 messages page (game/pages/messages.php).
    const FOLDERS = [1, 2, 3, 4, 5, 0];

    /**
     * Create a collector.
     *
     * @param {object} options Options: loca, lang, store.
     * @returns {object} Collector API.
     */
    function createCollector(options) {
        const opt = options || {};
        const store = opt.store;
        const ctx = { loca: opt.loca, lang: opt.lang || 'en' };
        let now = opt.now || null;

        function timestamp() {
            if (now !== null) return now;
            return Math.floor(Date.now() / 1000);
        }

        /**
         * Store a spy report (from an inlined message body or a report popup).
         *
         * @param {object} record Parsed report.
         * @returns {boolean} True when it was added.
         */
        function addSpy(record) {
            if (!record || !record.coords) return false;
            if (record.msgId !== undefined && !store.isNewMessage('spy', record.msgId)) {
                return false;
            }
            store.push('spies', record);
            store.observePlanet({
                coords: record.coords,
                name: record.planet,
                owner: record.player,
                date: record.date,
                report: true,
            });
            store.observePlayer({ name: record.player, date: record.date, coords: record.coords });
            if (record.msgId !== undefined) store.markMessage('spy', record.msgId, record.date);
            return true;
        }

        /**
         * Store an expedition record.
         *
         * @param {object} record Parsed expedition.
         * @returns {boolean} True when it was added.
         */
        function addExpedition(record) {
            if (!record) return false;
            if (record.msgId !== undefined && !store.isNewMessage('expedition', record.msgId)) return false;
            store.push('expeditions', record);
            if (record.msgId !== undefined) store.markMessage('expedition', record.msgId, record.date);
            return true;
        }

        /**
         * Store a combat record (the message row alone, or the full report).
         *
         * A battle is first seen as a row in the message list (outcome and both
         * loss values) and later, when its popup is read, enriched with the loot,
         * the debris and the participants. Re-reading the message list must not
         * throw that detail away, so an existing record is merged field by field
         * and empty values never overwrite known ones.
         *
         * @param {object} record Combat record.
         * @returns {boolean} True when a new record was added.
         */
        function addCombat(record) {
            if (!record) return false;
            if (record.msgId !== undefined) {
                const existing = store.findRecord('combats', record.msgId);
                if (existing) {
                    const patch = {};
                    for (const key of Object.keys(record)) {
                        const value = record[key];
                        if (value === null || value === undefined) continue;
                        if (Array.isArray(value) && value.length === 0) continue;
                        if (typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length === 0) continue;
                        patch[key] = value;
                    }
                    store.updateRecord('combats', record.msgId, patch);
                    return false;
                }
                if (!store.isNewMessage('combat', record.msgId)) return false;
            }
            store.push('combats', record);
            if (record.msgId !== undefined) store.markMessage('combat', record.msgId, record.date);
            return true;
        }

        /**
         * Store a recycling report.
         *
         * @param {object} record Harvest record.
         * @returns {boolean} True when it was added.
         */
        function addHarvest(record) {
            if (!record) return false;
            if (record.msgId !== undefined && !store.isNewMessage('harvest', record.msgId)) return false;
            store.push('harvests', record);
            if (record.msgId !== undefined) store.markMessage('harvest', record.msgId, record.date);
            return true;
        }

        /**
         * Store a transport record (import or export).
         *
         * @param {object} record Trade record.
         * @returns {boolean} True when it was added.
         */
        function addTrade(record) {
            if (!record) return false;
            if (record.msgId !== undefined && !store.isNewMessage('trade', record.msgId)) return false;
            store.push('trades', record);
            if (record.msgId !== undefined) store.markMessage('trade', record.msgId, record.date);
            return true;
        }

        /**
         * Read a messages page of one folder and store everything it contains.
         *
         * @param {string} pageHtml Page source.
         * @param {object} [pageOptions] Options: pm.
         * @returns {{added: number, reports: Array, parsed: number}} Result; `reports`
         *          lists the message ids whose detail is only available in the
         *          report popup (battle reports, partially shown spy reports).
         */
        function ingestMessagesPage(pageHtml, pageOptions) {
            const pageOpt = pageOptions || {};
            const parsed = messages.parseMessagesPage(pageHtml, {
                loca: ctx.loca,
                lang: ctx.lang,
                pm: pageOpt.pm === undefined ? null : pageOpt.pm,
                now: pageOpt.now === undefined ? now : pageOpt.now,
            });
            const reports = [];
            let added = 0;

            for (const message of parsed.messages) {
                const date = message.date === null ? timestamp() : message.date;
                switch (message.kind) {
                    case 'spy': {
                        if (message.bodyHtml) {
                            const record = spyParser.parseSpyReport(message.bodyHtml, ctx);
                            if (record) {
                                record.date = date;
                                record.msgId = message.id;
                                if (addSpy(record)) added++;
                            }
                        } else if (message.reportId !== null) {
                            reports.push({ msgId: message.id, reportId: message.reportId, kind: 'spy', date: date, coords: message.coords });
                        }
                        break;
                    }
                    case 'expedition': {
                        if (!message.bodyHtml) break;
                        const record = textParser.parseExpedition(message.bodyHtml, ctx);
                        record.date = date;
                        record.msgId = message.id;
                        record.coords = record.coords || message.coords;
                        if (addExpedition(record)) added++;
                        break;
                    }
                    case 'battle': {
                        // The row already tells the outcome and both loss values.
                        if (addCombat({
                            date: date,
                            msgId: message.id,
                            side: message.side,
                            result: message.result,
                            losses: message.losses,
                            coords: message.coords,
                            loot: null,
                            debris: null,
                        })) added++;
                        if (message.reportId !== null && !pageOpt.noReports) {
                            reports.push({ msgId: message.id, reportId: message.reportId, kind: 'battle', date: date, coords: message.coords });
                        }
                        break;
                    }
                    case 'recycle': {
                        if (!message.bodyHtml) break;
                        const record = textParser.parseRecycle(message.bodyHtml, ctx);
                        if (record) {
                            record.date = date;
                            record.msgId = message.id;
                            if (addHarvest(record)) added++;
                        }
                        break;
                    }
                    case 'transport-in':
                    case 'transport-out': {
                        if (!message.bodyHtml) break;
                        const record = message.kind === 'transport-in'
                            ? textParser.parseTransportOther(message.bodyHtml, ctx)
                            : textParser.parseTransportOwn(message.bodyHtml, ctx);
                        if (record) {
                            record.date = date;
                            record.msgId = message.id;
                            if (addTrade(record)) added++;
                        }
                        break;
                    }
                    default:
                        break;
                }
            }

            store.data().meta.lastCrawl = timestamp();
            store.save();
            return { added: added, reports: reports, parsed: parsed.messages.length };
        }

        /**
         * Read a report popup (page=bericht) and merge it into the database.
         *
         * @param {string} pageHtml Report page source.
         * @param {object} meta Options: msgId, kind, date, coords.
         * @returns {boolean} True when a record was stored or updated.
         */
        function ingestReportPage(pageHtml, meta) {
            const info = meta || {};
            const date = info.date === undefined || info.date === null ? timestamp() : info.date;
            if (info.kind === 'battle') {
                const report = battleParser.parseBattleReport(pageHtml, ctx);
                const merged = addCombat({
                    date: date,
                    msgId: info.msgId,
                    side: info.side || null,
                    result: info.result || report.result,
                    losses: info.losses || report.losses,
                    loot: report.loot,
                    debris: report.debris,
                    moonChance: report.moonChance,
                    moonCreated: report.moonCreated,
                    repaired: report.repaired,
                    rounds: report.rounds,
                    attackerUnits: report.attackers && report.attackers[0] ? report.attackers[0].units : null,
                    defenderUnits: report.defenders && report.defenders[0] ? report.defenders[0].units : null,
                    attackers: report.attackers || [],
                    defenders: report.defenders || [],
                });
                return merged || !!store.findRecord('combats', info.msgId);
            }
            const record = spyParser.parseSpyReport(pageHtml, ctx);
            if (!record) return false;
            record.date = date;
            record.msgId = info.msgId;
            return addSpy(record);
        }

        /**
         * Read a statistics page and store what it tells about every player.
         *
         * @param {string} pageHtml Page source.
         * @param {object} [pageOptions] Options: who, type, start.
         * @returns {{rows: number, self: object|null}} Result.
         */
        function ingestStatisticsPage(pageHtml, pageOptions) {
            const pageOpt = pageOptions || {};
            const parsed = statisticsParser.parseStatisticsPage(pageHtml, pageOpt);
            const stamp = timestamp();
            let self = null;

            for (const row of parsed.rows) {
                const observed = {
                    name: row.player,
                    date: stamp,
                    rank: parsed.type === 'ressources' ? row.rank : null,
                    ally: row.ally,
                    home: row.coords,
                    coords: row.coords,
                };
                if (parsed.type === 'ressources') observed.points = row.points;
                store.observePlayer(observed);

                const snapshot = { t: stamp };
                if (parsed.type === 'ressources') {
                    snapshot.points = row.points;
                    snapshot.rank = row.rank;
                } else if (parsed.type === 'fleet') {
                    snapshot.fleet = row.points;
                    snapshot.fleetRank = row.rank;
                } else if (parsed.type === 'research') {
                    snapshot.research = row.points;
                    snapshot.researchRank = row.rank;
                }
                store.observeStats(row.player, snapshot);

                if (row.self) {
                    self = Object.assign({}, row, { type: parsed.type });
                    store.data().meta.selfName = row.player;
                    if (row.coords) {
                        store.observePlanet({
                            coords: row.coords,
                            owner: row.player,
                            date: stamp,
                        });
                    }
                }
            }

            store.data().meta.lastStats = stamp;
            store.save();
            return { rows: parsed.rows.length, self: self };
        }

        /**
         * Read the empire page: the own planets with their resources, production and
         * object levels.
         *
         * @param {string} pageHtml Page source.
         * @returns {{planets: number}} Result.
         */
        function ingestImperiumPage(pageHtml) {
            const parsed = imperiumParser.parseImperiumPage(pageHtml, ctx);
            const stamp = timestamp();
            const selfName = (store.data().meta && store.data().meta.selfName) || null;

            // The own planets belong in the planet database as well: the empire page
            // is the only page that lists them all with their names.
            for (const planet of parsed.planets) {
                if (!planet.coords) continue;
                store.observePlanet({
                    coords: planet.coords,
                    name: planet.name,
                    owner: selfName,
                    date: stamp,
                    type: null,
                });
            }
            store.setEmpire(parsed, stamp);
            return { planets: parsed.planets.length };
        }

        return {
            FOLDERS: FOLDERS,
            ctx: ctx,
            setNow: function (value) { now = value; },
            ingestMessagesPage: ingestMessagesPage,
            ingestReportPage: ingestReportPage,
            ingestStatisticsPage: ingestStatisticsPage,
            ingestImperiumPage: ingestImperiumPage,
            ingestStatisticsPage: ingestStatisticsPage,
            addSpy: addSpy,
            addExpedition: addExpedition,
            addCombat: addCombat,
            addHarvest: addHarvest,
            addTrade: addTrade,
        };
    }

    const api = {
        FOLDERS: FOLDERS,
        createCollector: createCollector,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
