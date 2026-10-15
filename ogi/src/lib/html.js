/**
 * OGame Infinity 0.84 — tolerant HTML helpers.
 *
 * OGame 0.84 pages are a mix of clean tables and 2003-era markup (unbalanced
 * <div>/<font> tags, rows without <tr>, and so on — see the spy report generated
 * in game/core/fleet.php). Browser DOM parsers silently repair that; a plain
 * string parser must be tolerant instead. The extension therefore works on the
 * raw page source with the small scanning helpers below, which also makes the
 * parsers testable in Node against the golden snapshots in testing/golden/.
 */
(function (root) {
    'use strict';

    const NAMED_ENTITIES = {
        amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ',
        auml: 'ä', ouml: 'ö', uuml: 'ü', Auml: 'Ä', Ouml: 'Ö', Uuml: 'Ü',
        szlig: 'ß', eacute: 'é', egrave: 'è', agrave: 'à', ccedil: 'ç',
    };

    /**
     * Decode the HTML entities OGame uses.
     *
     * @param {string} text Text with entities.
     * @returns {string} Decoded text.
     */
    function decodeEntities(text) {
        return String(text === null || text === undefined ? '' : text)
            .replace(/&#x([0-9a-f]+);/gi, function (m, hex) {
                return String.fromCodePoint(parseInt(hex, 16));
            })
            .replace(/&#(\d+);/g, function (m, dec) {
                return String.fromCodePoint(parseInt(dec, 10));
            })
            .replace(/&([a-z]+);/gi, function (m, name) {
                const key = name.toLowerCase();
                if (Object.prototype.hasOwnProperty.call(NAMED_ENTITIES, key)) return NAMED_ENTITIES[key];
                if (Object.prototype.hasOwnProperty.call(NAMED_ENTITIES, name)) return NAMED_ENTITIES[name];
                return m;
            });
    }

    /**
     * Convert a fragment to plain text: <br> becomes a line break, every other
     * tag is dropped, entities are decoded.
     *
     * @param {string} html HTML fragment.
     * @returns {string} Text.
     */
    function stripTags(html) {
        return decodeEntities(String(html === null || html === undefined ? '' : html)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/(?:tr|p|div|table|center|h\d)>/gi, '\n')
            .replace(/<[^>]*>/g, ''));
    }

    /**
     * Normalize text for comparisons: no tags, single spaces, trimmed. This is
     * the form in which localization templates and page fragments are matched
     * against each other.
     *
     * @param {string} html HTML or text.
     * @returns {string} Normalized text.
     */
    function normalizeText(html) {
        return stripTags(html)
            .replace(/[\s\u00a0]+/g, ' ')
            .trim();
    }

    /**
     * Split a fragment into <tr> blocks. Rows never nest, so a non-greedy scan is
     * safe even for the malformed 0.84 markup.
     *
     * @param {string} html HTML fragment.
     * @returns {string[]} Inner HTML of every row.
     */
    function rows(html) {
        const out = [];
        const re = /<tr\b[^>]*>([\s\S]*?)<\/tr>/gi;
        let match;
        while ((match = re.exec(html)) !== null) out.push(match[1]);
        return out;
    }

    /**
     * Split a row (or table) into cells.
     *
     * @param {string} rowHtml Row HTML.
     * @returns {Array<{tag: string, attrs: string, html: string, text: string}>} Cells.
     */
    function cells(rowHtml) {
        const out = [];
        const re = /<(th|td)\b([^>]*)>([\s\S]*?)<\/\1>/gi;
        let match;
        while ((match = re.exec(rowHtml)) !== null) {
            out.push({
                tag: match[1].toLowerCase(),
                attrs: match[2],
                html: match[3],
                text: normalizeText(match[3]),
            });
        }
        return out;
    }

    /**
     * Find the first cell whose normalized text matches the wanted text (either
     * exactly or as a prefix, because 0.84 loves trailing spaces in headers).
     *
     * @param {string} html HTML fragment.
     * @param {string} wantedText Wanted cell text.
     * @returns {{start: number, end: number, html: string, text: string}|null} Cell location.
     */
    function findCell(html, wantedText) {
        const wanted = normalizeText(wantedText);
        if (wanted === '') return null;
        const re = /<(th|td)\b[^>]*>([\s\S]*?)<\/\1>/gi;
        let match;
        while ((match = re.exec(html)) !== null) {
            const text = normalizeText(match[2]);
            if (text === wanted || (text.length > wanted.length && text.indexOf(wanted) === 0)) {
                return {
                    start: match.index,
                    end: match.index + match[0].length,
                    html: match[2],
                    text: text,
                };
            }
        }
        return null;
    }

    /**
     * Collect <td>value</td><td>amount</td> pairs (the layout of every 0.84
     * report table) until the given stopping tag.
     *
     * @param {string} html HTML fragment that starts right after a table header cell.
     * @param {string} [stopTag] Tag that ends the scan, "</table>" by default.
     * @returns {Array<{name: string, value: string}>} Pairs.
     */
    function pairs(html, stopTag) {
        const stop = stopTag || '</table>';
        const stopIndex = html.toLowerCase().indexOf(stop.toLowerCase());
        const scope = stopIndex === -1 ? html : html.slice(0, stopIndex);
        const out = [];
        const re = /<td\b[^>]*>([\s\S]*?)<\/td>\s*<td\b[^>]*>([\s\S]*?)<\/td>/gi;
        let match;
        while ((match = re.exec(scope)) !== null) {
            out.push({
                name: normalizeText(match[1]),
                value: normalizeText(match[2]),
            });
        }
        return out;
    }

    /**
     * Extract every `showGalaxy(g,s,p)` coordinate triple of a fragment.
     *
     * @param {string} html HTML fragment.
     * @returns {Array<{g: number, s: number, p: number, text: string}>} Coordinates in order.
     */
    function coordinates(html) {
        const out = [];
        const re = /showGalaxy\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/gi;
        let match;
        while ((match = re.exec(html)) !== null) {
            out.push({
                g: parseInt(match[1], 10),
                s: parseInt(match[2], 10),
                p: parseInt(match[3], 10),
                text: match[1] + ':' + match[2] + ':' + match[3],
            });
        }
        return out;
    }

    /**
     * Extract the value of an attribute of a tag fragment.
     *
     * @param {string} tagHtml A tag (or any fragment containing one).
     * @param {string} name Attribute name.
     * @returns {string|null} Attribute value.
     */
    function attribute(tagHtml, name) {
        const re = new RegExp('\\b' + name + '\\s*=\\s*("([^"]*)"|\'([^\']*)\'|([^\\s>]+))', 'i');
        const match = re.exec(tagHtml || '');
        if (!match) return null;
        if (match[2] !== undefined) return decodeEntities(match[2]);
        if (match[3] !== undefined) return decodeEntities(match[3]);
        return decodeEntities(match[4]);
    }

    const api = {
        decodeEntities: decodeEntities,
        stripTags: stripTags,
        normalizeText: normalizeText,
        rows: rows,
        cells: cells,
        findCell: findCell,
        pairs: pairs,
        coordinates: coordinates,
        attribute: attribute,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
