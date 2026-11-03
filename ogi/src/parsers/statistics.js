/**
 * OGame Infinity 0.84 — statistics page parser.
 *
 * The 0.84 statistics page (game/pages/statistics.php) is a ranked table with HTML
 * comments as the only landmarks, because it has no ids or classes at all:
 *
 *     <!-- rank -->  <th> 1&nbsp;&nbsp; <a onmouseover='return overlib("<font
 *                       color=lime>+2</font><br/><font color=white>From 2026-09-01
 *                       10:51:09"); …'><font color='lime'>+</font></a> </th>
 *     <!-- nick -->  <a href="index.php?page=galaxy&…&p1=1&p2=3&p3=4" style='color:87CEEB'>PlayerTwo</a>
 *     <!--  ally --> <a href="index.php?page=allianzen&…">TST</a>
 *     <!-- points --> <th> 50 </th>
 *
 * The nickname link carries the player's home planet (`p1/p2/p3`) and its colour
 * tells the relation: `#` + lime is the player themselves, sky blue (87CEEB) is
 * the own alliance, white is everybody else. The rank delta tooltip holds both the
 * change of the place and the date it was recorded.
 *
 * The page has three views of the same table (`type=ressources|fleet|research`),
 * which together are the account breakdown by points.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    /**
     * Read the selected value of a `<select name="...">`.
     *
     * @param {string} source Page source.
     * @param {string} name Select name.
     * @returns {string|null} Selected value.
     */
    function selectedValue(source, name) {
        const re = new RegExp('<select[^>]*name="' + name + '"[^>]*>([\\s\\S]*?)</select>', 'i');
        const select = re.exec(source);
        if (!select) return null;
        const selected = /<option[^>]*value="([^"]*)"[^>]*selected/i.exec(select[1]);
        if (selected) return selected[1];
        const first = /<option[^>]*value="([^"]*)"/i.exec(select[1]);
        return first ? first[1] : null;
    }

    /**
     * Parse the ranked table of the statistics page.
     *
     * @param {string} source Page source.
     * @param {object} [options] Options: who/type/start override.
     * @returns {{rows: Array, who: string, type: string, start: number}} Parsed page.
     */
    function parseStatisticsPage(source, options) {
        const opt = options || {};
        const who = opt.who || selectedValue(source, 'who') || 'player';
        const type = opt.type || selectedValue(source, 'type') || 'ressources';
        const start = opt.start === undefined
            ? parseInt(selectedValue(source, 'start') || '0', 10)
            : opt.start;

        const rows = [];
        if (who !== 'player') {
            // The alliance view has different columns; it is parsed by the caller
            // only for the rank, so the rows stay empty on purpose.
            return { rows: rows, who: who, type: type, start: start };
        }

        const chunks = source.split(/<!--\s*rank\s*-->/i).slice(1);
        for (const chunk of chunks) {
            const row = chunk.split(/<\/tr>/i)[0];
            const entry = { rank: null, delta: null, rankDate: null, player: null, self: false, sameAlliance: false, coords: null, ally: null, points: null, type: type };

            const rank = /<th[^>]*>\s*(\d+)/i.exec(row);
            if (rank) entry.rank = parseInt(rank[1], 10);

            const delta = /overlib\("?<font color=([a-z0-9]+)>([+\-*])(\d*)/i.exec(row);
            if (delta) {
                entry.delta = {
                    color: delta[1],
                    sign: delta[2],
                    value: delta[3] === '' ? 0 : parseInt(delta[3], 10),
                };
            }
            const rankDate = /(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/.exec(row);
            if (rankDate) entry.rankDate = rankDate[1];

            const nickPart = row.split(/<!--\s*nick\s*-->/i)[1] || row;
            const nick = /<a\s+([^>]*)>([\s\S]*?)<\/a>/i.exec(nickPart);
            if (nick) {
                const attrs = nick[1];
                entry.player = html.normalizeText(nick[2]);
                entry.self = /href\s*=\s*["']?#/i.test(attrs) || /color:\s*lime/i.test(attrs);
                entry.sameAlliance = /87CEEB/i.test(attrs);
                const coords = /p1=(\d+)[^0-9]+p2=(\d+)[^0-9]+p3=(\d+)/i.exec(attrs);
                if (coords) {
                    entry.coords = {
                        g: parseInt(coords[1], 10),
                        s: parseInt(coords[2], 10),
                        p: parseInt(coords[3], 10),
                    };
                }
            }

            const allyPart = row.split(/<!--\s*ally\s*-->/i)[1];
            if (allyPart) {
                const ally = /<a[^>]*>([\s\S]*?)<\/a>/i.exec(allyPart);
                if (ally) entry.ally = html.normalizeText(ally[1]);
            }

            const pointsPart = row.split(/<!--\s*points\s*-->/i)[1];
            if (pointsPart) {
                const points = /<th[^>]*>([\s\S]*?)<\/th>/i.exec(pointsPart);
                if (points) {
                    const value = numbers.parseGameNumber(html.normalizeText(points[1]));
                    if (!isNaN(value)) entry.points = value;
                }
            }

            if (entry.player) rows.push(entry);
        }

        return { rows: rows, who: who, type: type, start: start };
    }

    const api = {
        selectedValue: selectedValue,
        parseStatisticsPage: parseStatisticsPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
