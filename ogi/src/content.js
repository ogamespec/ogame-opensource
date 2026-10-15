/**
 * OGame Infinity 0.84 — content script entry point.
 *
 * Loaded on every page; it first makes sure the page really is an OGame 0.84
 * server (see lib/detect.js — the title is localized, so the detection uses the
 * engine's own markers instead) and then hands control to the handler of the
 * current page.
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

    function boot() {
        const source = document.documentElement ? document.documentElement.outerHTML : '';
        const page = OGI.currentPage(window.location.search, window.location.pathname);
        if (!OGI.looksLikeGamePage(source, page)) return;

        let universe = OGI.detectUniverse(source, document.title);
        if (universe) {
            // The report popup has no left menu: remember the universe for it.
            OGI.saveUniverseHint(window.localStorage, window.location.host, universe);
        } else {
            universe = OGI.loadUniverseHint(window.localStorage, window.location.host);
        }

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
            page: page,
            universe: universe,
            session: OGI.detectSession(source),
            source: source,
        };

        const handlers = {
            messages: OGI.mountMessages,
            bericht: OGI.mountBericht,
            galaxy: OGI.mountGalaxy,
        };
        const handler = handlers[page];
        if (typeof handler !== 'function') return;

        try {
            handler(ctx);
        } catch (error) {
            console.warn('[OGI] ' + page + ' handler failed:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
