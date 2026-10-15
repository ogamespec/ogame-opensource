/**
 * OGame Infinity 0.84 — content script entry point.
 *
 * Loaded on every page; it first makes sure the page really is an OGame 0.84
 * server (the title is "Uni<N> <game name>", see game/core/page.php) and then hands
 * control to the handler of the current page.
 *
 * The extension never talks to the server on its own: it reads the pages the
 * player opens and the message folders it is allowed to fetch, exactly like the
 * original OGame Infinity extension works on the live game.
 */
(function () {
    'use strict';

    const OGI = window.OGI;
    if (!OGI || !OGI.LOCA) {
        console.warn('[OGI] locale data missing');
        return;
    }

    /**
     * Universe label of the page ("Uni1"), taken from the page title.
     *
     * @param {string} title Document title.
     * @returns {string|null} Universe label.
     */
    function detectUniverse(title) {
        const match = /(Uni\s*\d+)/i.exec(String(title || ''));
        return match ? match[1].replace(/\s+/g, '') : null;
    }

    /**
     * Session id of the page: 0.84 puts it into a global script variable
     * (`var session="0000000003e9";`), which is not visible from the isolated
     * world of a content script, so it is read from the page source.
     *
     * @param {string} source Page source.
     * @returns {string|null} Session id.
     */
    function detectSession(source) {
        const match = /var\s+session\s*=\s*["']([^"']+)["']/.exec(source);
        return match ? match[1] : null;
    }

    /**
     * Current game page name (the `page=` parameter).
     *
     * @returns {string|null} Page name.
     */
    function currentPage() {
        const match = /[?&]page=([a-z0-9_]+)/i.exec(window.location.search);
        if (match) return match[1].toLowerCase();
        if (/index\.php\s*$/.test(window.location.pathname)) return 'overview';
        return null;
    }

    function boot() {
        const source = document.documentElement ? document.documentElement.outerHTML : '';
        const universe = detectUniverse(document.title) || detectUniverse(source);
        if (!universe) return;

        const loca = OGI.createLoca(OGI.LOCA);
        const detected = loca.detectLanguage(source);
        const store = OGI.createStore({
            universe: universe,
            host: window.location.host,
            storage: window.localStorage,
        });
        store.load();
        const lang = store.data().lang || detected.lang || 'en';
        if (!store.data().lang) {
            store.data().lang = lang;
            store.save();
        }

        const ctx = {
            OGI: OGI,
            loca: loca,
            lang: lang,
            i18n: OGI.createI18n(lang),
            store: store,
            page: currentPage(),
            session: detectSession(source),
            source: source,
        };

        const handlers = {
            messages: OGI.mountMessages,
            bericht: OGI.mountBericht,
            galaxy: OGI.mountGalaxy,
        };
        const handler = handlers[ctx.page];
        if (typeof handler !== 'function') return;

        try {
            handler(ctx);
        } catch (error) {
            console.warn('[OGI] ' + ctx.page + ' handler failed:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
