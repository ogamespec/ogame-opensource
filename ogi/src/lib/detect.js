/**
 * OGame Infinity 0.84 — game page detection.
 *
 * The extension is installed on every site (`<all_urls>`) and has to decide on its
 * own whether the current document is a 0.84 game page. That decision must not
 * depend on the page **title**: `PAGE_TITLE` is localized, so the title is
 * "Uni1 OGame" only in English and German —
 *
 *     en/de  Uni1 OGame          fr  Univers 1 OGame
 *     ru     Вселенная 1 ОГейм   es  Universo 1 OGame
 *     it     OGame Universo 1    jp  ユニバース 1 OGame
 *
 * — and the report popup (`page=bericht`) has no left menu at all, so the universe
 * number can only be recovered from a hint the messages page stored before.
 */
(function (root) {
    'use strict';

    // The version link of the left menu (game/core/page.php): "<localized
    // Universe> 1 (<a href='index.php?page=changelog&session=...'>v 0.84</a>)".
    // The string "v 0.84" itself is hard-coded in the engine, not localized.
    const VERSION = /v\s*0\.8\d/i;
    const GAME_LINK = /index\.php\?page=/i;
    const SESSION = /var\s+session\s*=\s*["']([^"']+)["']/;
    const CONTENT = /id\s*=\s*['"]content['"]/i;

    // Key of the per-host hint that remembers which universe the player is in.
    const HINT_PREFIX = 'ogi084:active:';

    /**
     * Does the document look like a page of a 0.84 server?
     *
     * The report popup (`page=bericht`) is a bare document without the left menu,
     * the content div or a version link — it only carries the session variable —
     * so the page name is taken into account as well.
     *
     * @param {string} source Page source.
     * @param {string} [page] Game page name from the URL.
     * @returns {boolean} True for a game page.
     */
    function looksLikeGamePage(source, page) {
        const html = String(source === null || source === undefined ? '' : source);
        const pageName = String(page || '').toLowerCase();
        if (SESSION.test(html) && (GAME_LINK.test(html) || CONTENT.test(html) || VERSION.test(html))) return true;
        if (pageName === 'bericht' && SESSION.test(html) && /overlib/i.test(html)) return true;
        return GAME_LINK.test(html) && (CONTENT.test(html) || VERSION.test(html));
    }

    /**
     * Universe label of the page ("uni1"), or null when it cannot be determined
     * (the report popup, for example).
     *
     * @param {string} source Page source.
     * @param {string} [title] Document title.
     * @returns {string|null} Universe label.
     */
    function detectUniverse(source, title) {
        const html = String(source === null || source === undefined ? '' : source);

        // 1. The left menu: "<Universe> 1 (<a ... page=changelog ...>v 0.84</a>)".
        let match = /([^\s<>()]{0,24})\s+(\d{1,3})\s*\(?\s*<a[^>]*page=changelog[^>]*>/i.exec(html);
        if (match) return 'uni' + match[2];

        // 2. Any number next to the changelog link.
        match = /(\d{1,3})\s*\(?\s*<a[^>]*page=changelog/i.exec(html);
        if (match) return 'uni' + match[1];

        // 3. The title, in any of the languages the game ships.
        match = /(\d{1,3})/.exec(String(title === null || title === undefined ? '' : title));
        if (match) return 'uni' + match[1];

        return null;
    }

    /**
     * Session id of the page (`var session="…";`).
     *
     * @param {string} source Page source.
     * @returns {string|null} Session id.
     */
    function detectSession(source) {
        const match = SESSION.exec(String(source === null || source === undefined ? '' : source));
        return match ? match[1] : null;
    }

    /**
     * Game page name from the URL.
     *
     * @param {string} search `location.search`.
     * @param {string} [pathname] `location.pathname`.
     * @returns {string|null} Page name.
     */
    function currentPage(search, pathname) {
        const match = /[?&]page=([a-z0-9_]+)/i.exec(String(search || ''));
        if (match) return match[1].toLowerCase();
        if (/index\.php\s*$/i.test(String(pathname || ''))) return 'overview';
        return null;
    }

    /**
     * Remember the universe of this host (the report popup has no left menu, so it
     * has to ask for it).
     *
     * @param {object} storage Storage (localStorage).
     * @param {string} host Host name.
     * @param {string} universe Universe label.
     */
    function saveUniverseHint(storage, host, universe) {
        if (!storage || !universe) return;
        try {
            storage.setItem(HINT_PREFIX + host, JSON.stringify({ universe: universe }));
        } catch (error) {
            // A full or unavailable storage must never break the game page.
        }
    }

    /**
     * Read back the universe hint of a host.
     *
     * @param {object} storage Storage (localStorage).
     * @param {string} host Host name.
     * @returns {string|null} Universe label.
     */
    function loadUniverseHint(storage, host) {
        if (!storage) return null;
        try {
            const raw = storage.getItem(HINT_PREFIX + host);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            return parsed && parsed.universe ? parsed.universe : null;
        } catch (error) {
            return null;
        }
    }

    const api = {
        VERSION: VERSION,
        HINT_PREFIX: HINT_PREFIX,
        looksLikeGamePage: looksLikeGamePage,
        detectUniverse: detectUniverse,
        detectSession: detectSession,
        currentPage: currentPage,
        saveUniverseHint: saveUniverseHint,
        loadUniverseHint: loadUniverseHint,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
