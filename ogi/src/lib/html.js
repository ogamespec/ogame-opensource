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

    // A tag longer than this is not a tag but broken markup (see eachTag).
    const TAG_LIMIT = 400;

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
     * Walk the tags of a fragment, ignoring anything inside attribute values.
     *
     * 0.84 puts whole HTML documents into `onmouseover` attributes (the galaxy
     * tooltips contain nested tables with their own </tr>), so a plain regular
     * expression cannot tell a real closing tag from one inside a tooltip.
     *
     * @param {string} html HTML fragment.
     * @param {function(string, boolean, string, number, number): void} onTag
     *        Callback: tag name (lower case), is it a closing tag, the attribute
     *        text, the tag start index and the index after the tag.
     */
    function eachTag(html, onTag) {
        const source = String(html === null || html === undefined ? '' : html);
        let i = 0;
        while (i < source.length) {
            const lt = source.indexOf('<', i);
            if (lt === -1) return;
            const next = source[lt + 1];
            if (next === '!' || next === '?') {
                const end = source.indexOf('>', lt + 2);
                i = end === -1 ? source.length : end + 1;
                continue;
            }
            let j = lt + 1;
            let closing = false;
            if (source[j] === '/') { closing = true; j++; }
            if (!/[a-zA-Z]/.test(source[j] || '')) { i = lt + 1; continue; }
            const nameStart = j;
            while (j < source.length && /[a-zA-Z0-9:_-]/.test(source[j])) j++;
            const name = source.slice(nameStart, j).toLowerCase();
            // Read the rest of the tag respecting quoted attribute values. The
            // 0.84 markup breaks its own quoting (an apostrophe inside a
            // single-quoted onmouseover, see the activity tooltip of a spy
            // report), so the scan recovers the way a browser does: it stops at a
            // "<" outside quotes and gives up on absurdly long "tags".
            let quote = null;
            let end = -1;
            let nextTag = -1;
            for (let k = j; k < source.length && k - j < TAG_LIMIT; k++) {
                const ch = source[k];
                if (quote) {
                    if (ch === quote) quote = null;
                    continue;
                }
                if (ch === '"' || ch === "'") { quote = ch; continue; }
                if (ch === '<') { nextTag = k; break; }
                if (ch === '>') { end = k; break; }
            }
            if (end === -1) {
                // Not a usable tag: treat the "<" as text and keep scanning.
                i = nextTag === -1 ? lt + 1 : nextTag;
                continue;
            }
            onTag(name, closing, source.slice(j, end), lt, end + 1);
            i = end + 1;
        }
    }

    /**
     * Split a fragment into <tr> blocks, keeping the attributes of every row.
     *
     * A row ends at the first real `</tr>`: 0.84 emits its report rows with an
     * unbalanced structure (a nested table or a stray `</tr>`), so nesting cannot
     * be used, but tags inside attributes are ignored thanks to `eachTag`.
     *
     * @param {string} html HTML fragment.
     * @returns {Array<{attrs: string, html: string}>} Rows.
     */
    function rowsEx(html) {
        const source = String(html === null || html === undefined ? '' : html);
        const out = [];
        let depth = 0;
        let start = -1;
        let attrs = '';
        eachTag(source, function (name, closing, tagAttrs, tagStart, tagEnd) {
            if (name !== 'tr') return;
            if (!closing) {
                if (depth === 0) {
                    start = tagEnd;
                    attrs = tagAttrs;
                }
                depth++;
                return;
            }
            if (depth === 0) return;
            depth--;
            if (depth === 0 && start !== -1) {
                out.push({ attrs: attrs, html: source.slice(start, tagStart) });
                start = -1;
                attrs = '';
            }
        });
        return out;
    }

    /**
     * Inner HTML of every <tr> block.
     *
     * @param {string} html HTML fragment.
     * @returns {string[]} Inner HTML of every row.
     */
    function rows(html) {
        return rowsEx(html).map(function (row) { return row.html; });
    }

    /**
     * Split a row (or a fragment) into its cells. Cells are matched with the
     * tag scanner, so a `<td>` containing a nested table stays one cell and a
     * `</td>` inside a tooltip attribute is ignored.
     *
     * @param {string} rowHtml Row HTML.
     * @returns {Array<{tag: string, attrs: string, html: string, text: string, start: number, end: number}>} Cells.
     */
    function cells(rowHtml) {
        const source = String(rowHtml === null || rowHtml === undefined ? '' : rowHtml);
        const out = [];
        let depth = 0;
        let current = null;
        eachTag(source, function (name, closing, attrs, tagStart, tagEnd) {
            if (name !== 'td' && name !== 'th') return;
            if (!closing) {
                if (depth === 0) current = { tag: name, attrs: attrs, start: tagStart, contentStart: tagEnd };
                depth++;
                return;
            }
            if (depth === 0) return;
            depth--;
            if (depth === 0 && current) {
                // The content runs from the end of the opening tag: searching for
                // the first ">" would stop inside a tooltip attribute.
                current.html = source.slice(current.contentStart, tagStart);
                current.text = normalizeText(current.html);
                current.tagStart = current.start;
                current.end = tagEnd;
                out.push(current);
                current = null;
            }
        });
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
        const list = cells(html);
        for (const cell of list) {
            if (cell.text === wanted || (cell.text.length > wanted.length && cell.text.indexOf(wanted) === 0)) {
                return { start: cell.tagStart, end: cell.end, html: cell.html, text: cell.text };
            }
        }
        return null;
    }

    /**
     * Collect <td>name</td><td>amount</td> pairs (the layout of every 0.84 report
     * table) until the given stopping tag.
     *
     * @param {string} html HTML fragment that starts right after a table header cell.
     * @param {string} [stopTag] Tag that ends the scan, "</table>" by default.
     * @returns {Array<{name: string, value: string}>} Pairs.
     */
    function pairs(html, stopTag) {
        const source = String(html === null || html === undefined ? '' : html);
        const stop = (stopTag || '</table>').toLowerCase();
        const stopIndex = source.toLowerCase().indexOf(stop);
        const scope = stopIndex === -1 ? source : source.slice(0, stopIndex);
        const list = cells(scope);
        const out = [];
        for (let i = 0; i + 1 < list.length; i += 2) {
            out.push({ name: list[i].text, value: list[i + 1].text });
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

    /**
     * Slice the content of a cell that may contain nested tables (a message body
     * with an inlined spy report, for example). The cell ends at the first </td>
     * that is not nested inside a table.
     *
     * @param {string} source Page or fragment source.
     * @param {number} start Index of the first character after the opening tag.
     * @returns {string} Cell content.
     */
    function cellContent(source, start) {
        const re = /<(\/?)(table|td)\b[^>]*>/gi;
        re.lastIndex = start;
        let depth = 0;
        let match;
        while ((match = re.exec(source)) !== null) {
            const closing = match[1] === '/';
            const tag = match[2].toLowerCase();
            if (tag === 'table') {
                depth += closing ? -1 : 1;
            } else if (closing && depth <= 0) {
                return source.slice(start, match.index);
            }
        }
        return source.slice(start);
    }

    const api = {
        decodeEntities: decodeEntities,
        stripTags: stripTags,
        normalizeText: normalizeText,
        rows: rows,
        rowsEx: rowsEx,
        eachTag: eachTag,
        cells: cells,
        findCell: findCell,
        pairs: pairs,
        coordinates: coordinates,
        attribute: attribute,
        cellContent: cellContent,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
