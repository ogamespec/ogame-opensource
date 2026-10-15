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

    // The pages the extension knows how to read.
    const HANDLERS = ['messages', 'bericht', 'galaxy'];

    /**
     * Version of the loaded extension (works in Chrome and Firefox; the test
     * sandbox has no extension API and gets "dev").
     *
     * @returns {string} Version.
     */
    function extensionVersion() {
        try {
            const runtime = typeof chrome !== 'undefined' && chrome.runtime ? chrome.runtime
                : (typeof browser !== 'undefined' && browser.runtime ? browser.runtime : null);
            const manifest = runtime && runtime.getManifest ? runtime.getManifest() : null;
            return manifest && manifest.version ? manifest.version : 'dev';
        } catch (error) {
            return 'dev';
        }
    }

    /**
     * The browser storage, or null when it is blocked (a sandboxed frame, blocked
     * third-party storage, "block all cookies"). Reading `window.localStorage` can
     * throw by itself, so even the property access is guarded.
     *
     * @returns {object|null} Usable storage.
     */
    function browserStorage() {
        try {
            return OGI.safeStorage(window.localStorage);
        } catch (error) {
            console.warn('[OGI] localStorage is not available, data will not persist:', error);
            return null;
        }
    }

    /**
     * Record what the extension decided about this page, so that the console shows
     * whether it ran at all (an empty console is the only symptom of a content
     * script that was never injected).
     *
     * @param {object} info Decision.
     */
    function report(info) {
        const text = '[OGI ' + info.version + '] ' + info.href +
            ' · page=' + (info.page || '-') + ' · game=' + (info.game ? 'yes' : 'no') +
            ' · universe=' + (info.universe || '-') + ' · lang=' + (info.lang || '-') +
            ' · storage=' + (info.storage ? 'ok' : 'memory') +
            ' · handler=' + (info.handler || 'none');
        if (info.game) console.info(text);
        else console.debug(text);
    }

    function boot() {
        const version = extensionVersion();
        const source = document.documentElement ? document.documentElement.outerHTML : '';
        const page = OGI.currentPage(window.location.search, window.location.pathname);
        const info = {
            version: version,
            href: window.location.href,
            page: page,
            game: false,
            universe: null,
            lang: null,
            storage: false,
            handler: null,
        };
        OGI.lastBoot = info;

        info.game = OGI.looksLikeGamePage(source, page);
        if (!info.game) {
            // Not a game page: nothing to do, but the console still shows that the
            // content script ran (visible with the "Verbose" log level).
            report(info);
            return;
        }

        const storage = browserStorage();
        info.storage = !!storage;
        let universe = OGI.detectUniverse(source, document.title);
        if (universe) {
            // The report popup has no left menu: remember the universe for it.
            OGI.saveUniverseHint(storage, window.location.host, universe);
        } else {
            universe = OGI.loadUniverseHint(storage, window.location.host);
        }
        info.universe = universe;

        const loca = OGI.createLoca(OGI.LOCA);
        const detected = loca.detectLanguage(source);
        const store = OGI.createStore({
            universe: universe,
            host: window.location.host,
            storage: storage,
        });
        store.load();
        const lang = store.data().lang || detected.lang || 'en';
        if (!store.data().lang) {
            store.data().lang = lang;
            store.save();
        }
        info.lang = lang;

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
        info.handler = typeof handler === 'function' ? page : null;
        report(info);
        if (typeof handler !== 'function') {
            if (page) {
                console.info('[OGI] page "' + page + '" is a game page but has no handler (supported: ' +
                    HANDLERS.join(', ') + ')');
            }
            return;
        }

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
