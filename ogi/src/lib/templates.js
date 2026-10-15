/**
 * OGame Infinity 0.84 — localization templates and language detection.
 *
 * The 0.84 server renders every report from templates such as
 *
 *     $LOCA["en"]["SPY_PLAYER"] = "(Player '#1')<br /> at #2.";
 *     $LOCA["en"]["EXP_FOUND"]  = "<br>You got #1 units of #2.";
 *
 * (see game/loca/<lang>_<lang>/{espionage,expedition,fleetmsg,battlereport}.php).
 * The extension turns those templates back into regular expressions and reads the
 * amounts out of the page with them: this is exactly how the original OGame
 * Infinity extension classified messages of the pre-API OGame versions.
 */
(function (root) {
    'use strict';

    const html = root.OGI || (typeof require === 'function' ? require('./html.js') : null);

    /**
     * Escape a string for use inside a regular expression.
     *
     * @param {string} text Text.
     * @returns {string} Escaped text.
     */
    function escapeRegex(text) {
        return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /**
     * Build a regular expression from a normalized localization template. Every
     * `#N` placeholder becomes a capture group; the map tells which group holds
     * which placeholder. A placeholder that ends the template is greedy (nothing
     * follows it that could bound the match), all the others are lazy.
     *
     * @param {string} template Normalized template text (for example "Resources on #1").
     * @returns {{regex: RegExp, groups: Object<number, number>}} Regex and placeholder map.
     */
    function templateToRegex(template) {
        const groups = {};
        let source = '';
        let groupIndex = 0;
        let rest = String(template);
        const re = /#(\d)/;
        let match;
        while ((match = re.exec(rest)) !== null) {
            source += escapeRegex(rest.slice(0, match.index));
            groupIndex++;
            const isLast = rest.slice(match.index + match[0].length) === '';
            source += isLast ? '([\\s\\S]*)' : '([\\s\\S]*?)';
            if (!(match[1] in groups)) groups[Number(match[1])] = groupIndex;
            rest = rest.slice(match.index + match[0].length);
        }
        source += escapeRegex(rest);
        return { regex: new RegExp(source), groups: groups };
    }

    /**
     * Match a localization template against a text and return the placeholder
     * values.
     *
     * @param {string} template Template text (may contain HTML).
     * @param {string} target Target text (may contain HTML).
     * @returns {Object<number, string>|null} Values by placeholder number.
     */
    function matchTemplate(template, target) {
        if (template === null || template === undefined) return null;
        const normalizedTemplate = html.normalizeText(template);
        if (normalizedTemplate === '') return null;
        const { regex, groups } = templateToRegex(normalizedTemplate);
        const match = regex.exec(html.normalizeText(target));
        if (!match) return null;
        const values = {};
        for (const placeholder of Object.keys(groups)) {
            values[placeholder] = match[groups[placeholder]];
        }
        return values;
    }

    // Marker keys used to detect the language of a rendered page. They are short,
    // distinctive labels that appear on the messages page and in the reports.
    const LANGUAGE_MARKERS = [
        'MSG_ACTION', 'MSG_DATE', 'MSG_FROM', 'MSG_SUBJ',
        'SPY_M', 'SPY_K', 'SPY_FLEET', 'SPY_DEFENSE',
        'BATTLE_ATTACKER', 'BATTLE_DEFENDER', 'FLEET_MESSAGE_FROM',
    ];

    /**
     * Wrap the generated locale data (src/data/loca.js) into a small lookup API.
     *
     * @param {object} locaData Parsed locale data.
     * @returns {object} Locale API.
     */
    function createLoca(locaData) {
        if (!locaData || !locaData.languages) throw new Error('OGI: missing locale data');
        const languages = locaData.languages;
        const nameMaps = {};

        /**
         * Get a raw localization string.
         *
         * @param {string} lang Language code.
         * @param {string} key Key.
         * @returns {string|null} Value.
         */
        function get(lang, key) {
            const table = languages[lang] || languages.en;
            if (!table) return null;
            const value = table[key];
            return value === undefined ? null : value;
        }

        /**
         * Match a localization template of the given language against a text.
         *
         * @param {string} lang Language code.
         * @param {string} key Key.
         * @param {string} target Target text.
         * @returns {Object<number, string>|null} Placeholder values.
         */
        function match(lang, key, target) {
            const template = get(lang, key);
            if (template === null) return null;
            return matchTemplate(template, target);
        }

        /**
         * Find which of the given keys occurs in a text (used to classify
         * expedition outcomes and fleet messages).
         *
         * @param {string} lang Language code.
         * @param {string} text Target text.
         * @param {string[]} keys Candidate keys.
         * @returns {string|null} The first matching key.
         */
        function findKey(lang, text, keys) {
            const normalized = html.normalizeText(text);
            for (const key of keys) {
                const value = get(lang, key);
                if (value === null || value === '') continue;
                const needle = html.normalizeText(value);
                if (needle.length >= 4 && normalized.indexOf(needle) !== -1) return key;
            }
            return null;
        }

        /**
         * All keys of a language that start with a prefix.
         *
         * @param {string} lang Language code.
         * @param {string} prefix Key prefix.
         * @returns {string[]} Keys, in the order of the generated table.
         */
        function keysWithPrefix(lang, prefix) {
            const table = languages[lang] || languages.en || {};
            return Object.keys(table).filter(function (key) { return key.indexOf(prefix) === 0; });
        }

        /**
         * Reverse map of localized object names to their game ids: NAME_<gid> and
         * the short battle report names SNAME_<gid>.
         *
         * @param {string} lang Language code.
         * @returns {Object<string, number>} Name to id.
         */
        function nameToId(lang) {
            if (nameMaps[lang]) return nameMaps[lang];
            const table = languages[lang] || {};
            const map = {};
            for (const key of Object.keys(table)) {
                let id = null;
                if (key.indexOf('NAME_') === 0 || key.indexOf('SNAME_') === 0) {
                    const suffix = key.slice(key.indexOf('_') + 1);
                    if (/^\d+$/.test(suffix)) id = parseInt(suffix, 10);
                }
                if (id === null) continue;
                const name = html.normalizeText(table[key]);
                if (name === '' || name in map) continue;
                map[name] = id;
            }
            nameMaps[lang] = map;
            return map;
        }

        /**
         * Detect the language of a rendered page by counting how many marker
         * strings of every supported language appear in it.
         *
         * @param {string} pageHtml Page source.
         * @returns {{lang: string, score: number, scores: Object<string, number>}} Result.
         */
        function detectLanguage(pageHtml) {
            const normalized = html.normalizeText(pageHtml);
            const scores = {};
            let best = 'en';
            let bestScore = -1;
            for (const lang of Object.keys(languages)) {
                let score = 0;
                for (const key of LANGUAGE_MARKERS) {
                    const value = get(lang, key);
                    if (value === null) continue;
                    const needle = html.normalizeText(value);
                    if (needle.length >= 4 && normalized.indexOf(needle) !== -1) score++;
                }
                scores[lang] = score;
                if (score > bestScore) {
                    bestScore = score;
                    best = lang;
                }
            }
            return { lang: best, score: bestScore, scores: scores };
        }

        return {
            data: locaData,
            languages: Object.keys(languages),
            get: get,
            match: match,
            findKey: findKey,
            keysWithPrefix: keysWithPrefix,
            nameToId: nameToId,
            detectLanguage: detectLanguage,
        };
    }

    const api = {
        escapeRegex: escapeRegex,
        templateToRegex: templateToRegex,
        matchTemplate: matchTemplate,
        createLoca: createLoca,
        LANGUAGE_MARKERS: LANGUAGE_MARKERS,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
