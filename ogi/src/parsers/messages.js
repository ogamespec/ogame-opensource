/**
 * OGame Infinity 0.84 — message list parser.
 *
 * The 0.84 messages page (game/pages/messages.php) has no machine readable
 * markup at all: a message is two or three sibling <tr> rows, the message id is
 * only reachable through `name="delmes<id>"`, the type is only encoded in the
 * `pm=` parameter of the folder links, and the subject of a spy/battle report is
 * a link whose `onclick` pops up `page=bericht&bericht=<id>`.
 *
 * Everything this parser returns is therefore reconstructed from those anchors.
 * The folder parameter (`pm`) is passed in by the caller, because the extension
 * crawls the message folders one by one (the 0.84 lists are not paginated, they
 * simply stop after 25/50 rows).
 */
(function (root) {
    'use strict';

    // In the browser the helpers are already attached to the shared OGI
    // namespace (the manifest loads them first); in Node they are required.
    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    // Message types of the 0.84 server (game/core/defs.php).
    const MTYP = {
        PM: 0,
        SPY_REPORT: 1,
        BATTLE_REPORT_LINK: 2,
        EXP: 3,
        ALLY: 4,
        MISC: 5,
        BATTLE_REPORT_TEXT: 6,
    };

    // Subjects that identify a fleet message (game/loca/<lang>_<lang>/fleetmsg.php).
    const FLEET_SUBJECTS = [
        ['FLEET_MESSAGE_INTEL', 'recycle'],
        ['FLEET_MESSAGE_ARRIVE', 'transport-out'],
        ['FLEET_MESSAGE_TRADE', 'transport-in'],
        ['FLEET_MESSAGE_RETURN', 'return'],
        ['FLEET_MESSAGE_HOLD', 'deploy'],
        ['FLEET_COLONIZE_SUBJ', 'colonize'],
        ['FLEET_MESSAGE_SPY', 'spy-observed'],
        ['FLEET_MESSAGE_OBSERVE', 'observe'],
        ['FLEET_MESSAGE_FLEET', 'fleet'],
        ['FLEET_MESSAGE_BATTLE', 'battle'],
        ['MSG_OPER_PM', 'message'],
    ];

    /**
     * Parse the "MM-DD HH:MM:SS" date the 0.84 message list shows (it has no
     * year, so the current year is assumed and corrected around New Year).
     *
     * @param {string} text Date text.
     * @param {number} [nowSeconds] Current time, for tests.
     * @returns {number|null} Unix timestamp.
     */
    function parseGameDate(text, nowSeconds) {
        const match = /(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/.exec(String(text || ''));
        if (!match) return null;
        const now = nowSeconds === undefined ? Math.floor(Date.now() / 1000) : nowSeconds;
        const nowDate = new Date(now * 1000);
        let year = nowDate.getUTCFullYear();
        let stamp = Math.floor(Date.UTC(
            year,
            parseInt(match[1], 10) - 1,
            parseInt(match[2], 10),
            parseInt(match[3], 10),
            parseInt(match[4], 10),
            parseInt(match[5], 10)
        ) / 1000);
        if (stamp > now + 86400) {
            year -= 1;
            stamp = Math.floor(Date.UTC(
                year,
                parseInt(match[1], 10) - 1,
                parseInt(match[2], 10),
                parseInt(match[3], 10),
                parseInt(match[4], 10),
                parseInt(match[5], 10)
            ) / 1000);
        }
        return stamp;
    }

    /**
     * Extract the coordinates of a fragment ("[1:3:4]" text or a showGalaxy call).
     *
     * @param {string} fragment HTML or text.
     * @returns {{g: number, s: number, p: number}|null} Coordinates.
     */
    function parseCoords(fragment) {
        const galaxy = html.coordinates(fragment || '');
        if (galaxy.length) return { g: galaxy[0].g, s: galaxy[0].s, p: galaxy[0].p };
        const match = /\[\s*(\d+)\s*:\s*(\d+)\s*:\s*(\d+)\s*\]/.exec(html.normalizeText(fragment || ''));
        if (match) {
            return { g: parseInt(match[1], 10), s: parseInt(match[2], 10), p: parseInt(match[3], 10) };
        }
        return null;
    }

    /**
     * Read the message folders (the "Spy Reports 1 / 1" rows) of the page.
     *
     * @param {string} pageHtml Page source.
     * @returns {Array<{pm: number, total: number, unread: number}>} Folders.
     */
    function parseFolders(pageHtml) {
        const out = [];
        const re = /page=messages&(?:amp;)?dsp=1&(?:amp;)?pm=(\d+)[^"']*["'][^>]*>([\s\S]*?)<\/a>[\s\S]{0,120}?<th>\s*(\d+)\s*\/\s*(\d+)\s*<\/th>/gi;
        let match;
        while ((match = re.exec(pageHtml)) !== null) {
            out.push({
                pm: parseInt(match[1], 10),
                total: parseInt(match[3], 10),
                unread: parseInt(match[4], 10),
            });
        }
        return out;
    }

    /**
     * Classify a message subject.
     *
     * @param {object} ctx Context: loca, lang.
     * @param {string} subjectHtml Subject cell HTML.
     * @param {number|null} folderPm Folder the row was read from.
     * @returns {object} Classification: kind plus the fields found in the subject.
     */
    function classifySubject(ctx, subjectHtml, folderPm) {
        const text = html.normalizeText(subjectHtml);
        const info = { kind: 'unknown', reportId: null, result: null, losses: null, coords: null };

        const battleClass = /combatreport_([a-z]+)_(iwon|ilost|draw)/i.exec(subjectHtml);
        const reportLink = /bericht=(\d+)/i.exec(subjectHtml);
        if (reportLink) info.reportId = parseInt(reportLink[1], 10);

        if (battleClass) {
            info.kind = 'battle';
            info.side = /ididattack/i.test(battleClass[1]) ? 'attacker' : 'defender';
            info.result = battleClass[2].toLowerCase();
            const losses = /\(\s*V\s*:\s*([\d.,]+)\s*,\s*A\s*:\s*([\d.,]+)\s*\)/.exec(text);
            if (losses) {
                info.losses = {
                    defender: numbers.parseGameNumber(losses[1]),
                    attacker: numbers.parseGameNumber(losses[2]),
                };
            }
            info.coords = parseCoords(subjectHtml);
            return info;
        }

        if (/espionagereport/i.test(subjectHtml)) {
            info.kind = 'spy';
            info.coords = parseCoords(subjectHtml);
            return info;
        }

        // Expedition reports use a template subject: "Expedition result [#1:#2:#3]".
        if (ctx && ctx.loca) {
            if (ctx.loca.match(ctx.lang, 'EXP_MESSAGE_SUBJ', text)) {
                info.kind = 'expedition';
                info.coords = parseCoords(subjectHtml);
                return info;
            }
            for (const entry of FLEET_SUBJECTS) {
                const values = ctx.loca.match(ctx.lang, entry[0], text);
                if (values) {
                    info.kind = entry[1];
                    return info;
                }
            }
        }

        if (info.reportId !== null) {
            info.kind = 'battle';
            info.coords = parseCoords(subjectHtml);
            return info;
        }

        // Fall back to the folder the message was listed in.
        if (folderPm === MTYP.SPY_REPORT) info.kind = 'spy';
        else if (folderPm === MTYP.BATTLE_REPORT_LINK) info.kind = 'battle';
        else if (folderPm === MTYP.EXP) info.kind = 'expedition';
        else if (folderPm === MTYP.PM) info.kind = 'message';
        else if (folderPm === MTYP.ALLY) info.kind = 'ally';

        return info;
    }

    /**
     * Parse the message table of the 0.84 messages page.
     *
     * The scan is offset based on purpose: a message body row contains a whole
     * nested report (tables with their own <tr>/<td>), so the body is sliced out
     * of the page source between two message rows instead of relying on row
     * nesting (which the 0.84 markup breaks anyway).
     *
     * @param {string} pageHtml Page source.
     * @param {object} [options] Options: loca, lang, pm, now.
     * @returns {{folders: Array, messages: Array}} Parsed page.
     */
    function parseMessagesPage(pageHtml, options) {
        const opt = options || {};
        const ctx = { loca: opt.loca || null, lang: opt.lang || 'en' };
        const folderPm = opt.pm === undefined || opt.pm === null ? null : opt.pm;
        const now = opt.now;

        // Every flat message row: "<tr ...> ... name="delmes<id>" ... </tr>".
        const rowRe = /<tr\b[^>]*>([\s\S]*?)<\/tr>/gi;
        const found = [];
        let match;
        while ((match = rowRe.exec(pageHtml)) !== null) {
            const idMatch = /name="delmes(\d+)"/.exec(match[1]);
            if (!idMatch) continue;
            found.push({
                id: parseInt(idMatch[1], 10),
                start: match.index,
                end: rowRe.lastIndex,
                html: match[1],
            });
        }

        const messages = [];
        for (let i = 0; i < found.length; i++) {
            const row = found[i];
            const cells = html.cells(row.html);
            const message = {
                id: row.id,
                folder: folderPm,
                dateText: cells[1] ? cells[1].text : '',
                date: parseGameDate(cells[1] ? cells[1].text : '', now),
                from: cells[2] ? cells[2].text : '',
                fromCoords: parseCoords(cells[2] ? cells[2].html : ''),
                subjectHtml: cells[3] ? cells[3].html : '',
                subjectText: cells[3] ? cells[3].text : '',
                bodyHtml: null,
            };

            // Body: the "class=b colspan=N" cell that follows before the next
            // message row. Everything up to the last </td> of that slice belongs
            // to the message (the nested report tables included).
            const limit = i + 1 < found.length ? found[i + 1].start : pageHtml.length;
            const tail = pageHtml.slice(row.end, limit);
            const bodyCell = /<td[^>]*class\s*=\s*["']?b\b[^>]*colspan[^>]*>/i.exec(tail)
                || /<td[^>]*colspan[^>]*class\s*=\s*["']?b\b[^>]*>/i.exec(tail);
            if (bodyCell) {
                const open = tail.indexOf('>', bodyCell.index);
                message.bodyHtml = html.cellContent(tail, open + 1);
            }

            const info = classifySubject(ctx, message.subjectHtml, folderPm);
            message.kind = info.kind;
            message.side = info.side || null;
            message.result = info.result || null;
            message.losses = info.losses || null;
            message.reportId = info.reportId === undefined ? null : info.reportId;
            message.coords = info.coords || parseCoords(message.subjectHtml) || message.fromCoords;
            messages.push(message);
        }

        return { folders: parseFolders(pageHtml), messages: messages };
    }

    const api = {
        MTYP: MTYP,
        FLEET_SUBJECTS: FLEET_SUBJECTS,
        parseGameDate: parseGameDate,
        parseCoords: parseCoords,
        parseFolders: parseFolders,
        classifySubject: classifySubject,
        parseMessagesPage: parseMessagesPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
