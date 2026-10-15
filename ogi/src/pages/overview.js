/**
 * OGame Infinity 0.84 — overview page.
 *
 * Shows the fleets in the air with exact arrival times and warns about hostile
 * ones. The countdowns are updated in the browser every second from the arrival
 * timestamps the game itself put into the event rows (`star` attribute), so no
 * request is made while the timer runs.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Update every countdown of a container.
     *
     * @param {Element} container Container.
     */
    function tick(container) {
        const cells = container.querySelectorAll('[data-ogi-countdown]');
        const now = Math.floor(Date.now() / 1000);
        for (let i = 0; i < cells.length; i++) {
            const endsAt = parseInt(cells[i].getAttribute('data-ogi-countdown'), 10);
            if (!isFinite(endsAt)) continue;
            const text = OGI.formatCountdown(endsAt, now);
            if (cells[i].textContent !== text) cells[i].textContent = text;
        }
    }

    /**
     * Show a browser notification for a hostile fleet, once per event.
     *
     * The notification is only used when the page is in the background (a fleet
     * arriving at a tab nobody is looking at is exactly the case worth a desktop
     * notification); everything is wrapped, because browsers may deny the API
     * entirely and that must never break the game page.
     *
     * @param {object} ctx Extension context.
     * @param {Array} events Fleet events.
     */
    function notifyHostiles(ctx, events) {
        const settings = OGI.read(ctx.store);
        if (!settings.notifyHostile) return;
        if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
        if (typeof document.hasFocus === 'function' && document.hasFocus()) return;

        const data = ctx.store.data();
        if (!data.meta.notified) data.meta.notified = {};
        let sent = 0;
        for (const event of events) {
            if (!event.hostile) continue;
            if (data.meta.notified[event.id]) continue;
            const target = event.coords && event.coords.length ? event.coords[event.coords.length - 1].text : '';
            try {
                new Notification('OGame Infinity 0.84 — ' + ctx.i18n('OV_HOSTILE'), {
                    body: (event.missionText || event.mission || '?') + ' ' + target + ' — ' +
                        ctx.i18n('OV_LEFT') + ' ' + OGI.formatCountdown(event.endsAt),
                    tag: 'ogi-hostile-' + event.id,
                });
                data.meta.notified[event.id] = true;
                sent++;
            } catch (error) {
                console.warn('[OGI] notification failed:', error);
                return;
            }
        }
        if (sent) ctx.store.save();
    }

    /**
     * Handle the overview page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountOverview = function (ctx) {
        const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: ctx.store });
        const parsed = OGI.parseEvents(ctx.source, { loca: ctx.loca, lang: ctx.lang });

        // Record what the event list says about the activity of foreign planets:
        // a fleet flying to a planet is activity the galaxy page cannot show.
        for (const event of parsed.events) {
            if (event.hostile && event.coords && event.coords.length) {
                const target = event.coords[event.coords.length - 1];
                ctx.store.observeActivity(target.g, target.s, target.p, '*', Math.floor(Date.now() / 1000));
            }
        }
        ctx.store.save();
        collector.setNow(Math.floor(Date.now() / 1000));

        const host = document.createElement('div');
        host.id = 'ogi-overview-host';
        const content = document.getElementById('content') || document.body;
        if (content.firstChild) content.insertBefore(host, content.firstChild);
        else content.appendChild(host);

        function render(status) {
            host.innerHTML = OGI.fleetPanel(ctx.store.data(), {
                i18n: ctx.i18n,
                events: parsed.events,
                loca: ctx.loca,
                lang: ctx.lang,
                status: status || '',
            });
        }

        render();

        // Warn about hostile fleets: a banner in the panel is always there, the
        // desktop notification only when the page is in the background.
        notifyHostiles(ctx, parsed.events);

        // A light ticker: it stops by itself when the panel is gone (the game
        // reloads parts of the page, and the player may navigate away).
        const timer = setInterval(function () {
            if (!document.body || !document.body.contains(host)) {
                clearInterval(timer);
                return;
            }
            tick(host);
        }, 1000);
        tick(host);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
