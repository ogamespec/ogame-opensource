/**
 * OGame Infinity 0.84 — icon set.
 *
 * The original OGame Infinity draws its panels with its own PNG artwork. That
 * artwork is not reused here: the extension ships its own small inline SVG icons,
 * which have several advantages on a 0.84 server — no binary assets to load, they
 * scale with the game's font size, they take the colour of the surrounding text
 * (`currentColor`) and they work in every browser without a request.
 *
 * Resource icons follow the colours the game itself uses for the resource bar.
 */
(function (root) {
    'use strict';

    // 16x16 view box. Stroke icons use currentColor, filled icons use the resource
    // colours of the resource bar.
    const ICONS = {
        // Panel tabs.
        spy: '<circle cx="8" cy="8" r="2.6" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
            '<path d="M1.4 8C3.3 4.6 5.6 3 8 3s4.7 1.6 6.6 5C12.7 11.4 10.4 13 8 13S3.3 11.4 1.4 8z" fill="none" stroke="currentColor" stroke-width="1.2"/>',
        expedition: '<path d="M8 1.2c2.5 1.6 3.5 4.5 3.5 7L8 11 4.5 8.2C4.5 5.7 5.5 2.8 8 1.2z" fill="currentColor"/>' +
            '<path d="M6.4 12 8 14.8 9.6 12" fill="none" stroke="currentColor" stroke-width="1.2"/>',
        combat: '<path d="M2.2 2.2l9 9M13.8 2.2l-9 9" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
            '<path d="M3 12l2 2M13 12l-2 2" fill="none" stroke="currentColor" stroke-width="1.5"/>',
        recycle: '<path d="M13 8a5 5 0 1 1-1.7-3.8" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
            '<path d="M13 2.6v2.8h-2.8" fill="none" stroke="currentColor" stroke-width="1.4"/>',
        transport: '<rect x="1.4" y="5" width="7" height="5.4" fill="none" stroke="currentColor" stroke-width="1.2"/>' +
            '<path d="M8.4 6.8h3l2.2 2.2v1.4h-5.2z" fill="none" stroke="currentColor" stroke-width="1.2"/>' +
            '<circle cx="4" cy="12" r="1.3" fill="currentColor"/><circle cx="11" cy="12" r="1.3" fill="currentColor"/>',
        planets: '<circle cx="8" cy="8" r="3.8" fill="currentColor"/>' +
            '<ellipse cx="8" cy="8" rx="7" ry="2.6" fill="none" stroke="currentColor" stroke-width="1.1" transform="rotate(-20 8 8)"/>',
        totals: '<path d="M2 13.4h12" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<rect x="3" y="8" width="2.6" height="4.2" fill="currentColor"/>' +
            '<rect x="6.7" y="5" width="2.6" height="7.2" fill="currentColor"/>' +
            '<rect x="10.4" y="2.6" width="2.6" height="9.6" fill="currentColor"/>',
        stats: '<path d="M2 13.4h12" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<path d="M2.6 10.4 6 6.6l2.6 2.4 4.4-5" fill="none" stroke="currentColor" stroke-width="1.4"/>',
        needs: '<rect x="3" y="1.6" width="10" height="12.8" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<rect x="5" y="3.6" width="6" height="2.2" fill="currentColor"/>' +
            '<circle cx="5.7" cy="8.3" r=".85" fill="currentColor"/><circle cx="8" cy="8.3" r=".85" fill="currentColor"/>' +
            '<circle cx="10.3" cy="8.3" r=".85" fill="currentColor"/><circle cx="5.7" cy="11.2" r=".85" fill="currentColor"/>' +
            '<circle cx="8" cy="11.2" r=".85" fill="currentColor"/><circle cx="10.3" cy="11.2" r=".85" fill="currentColor"/>',
        settings: '<circle cx="8" cy="8" r="2.4" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
            '<path d="M8 1.4v2.2M8 12.4v2.2M1.4 8h2.2M12.4 8h2.2M3.4 3.4l1.5 1.5M11.1 11.1l1.5 1.5M12.6 3.4l-1.5 1.5M4.9 11.1l-1.5 1.5" fill="none" stroke="currentColor" stroke-width="1.3"/>',
        empire: '<rect x="2" y="2" width="5" height="5" fill="currentColor"/>' +
            '<rect x="9" y="2" width="5" height="5" fill="currentColor" opacity=".7"/>' +
            '<rect x="2" y="9" width="5" height="5" fill="currentColor" opacity=".7"/>' +
            '<rect x="9" y="9" width="5" height="5" fill="currentColor" opacity=".45"/>',
        farms: '<circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<circle cx="8" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<circle cx="8" cy="8" r="1.1" fill="currentColor"/>',

        // Markers and states.
        favorite: '<path d="M8 1.6l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.4l-3.8 2 .7-4.3-3.1-3 4.3-.6z" fill="currentColor"/>',
        favoriteOff: '<path d="M8 1.6l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.4l-3.8 2 .7-4.3-3.1-3 4.3-.6z" fill="none" stroke="currentColor" stroke-width="1.1"/>',
        warning: '<path d="M8 1.6l6.4 11.8H1.6z" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<path d="M8 6v3.6" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
            '<circle cx="8" cy="11.6" r=".9" fill="currentColor"/>',
        clock: '<circle cx="8" cy="8" r="6.2" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<path d="M8 4.4V8l2.6 1.8" fill="none" stroke="currentColor" stroke-width="1.3"/>',
        moon: '<path d="M12.6 9.9A5.6 5.6 0 0 1 6.1 3.4a5.6 5.6 0 1 0 6.5 6.5z" fill="currentColor"/>',
        ship: '<path d="M3 10.5 8 2l5 8.5-5 2.8z" fill="currentColor"/>',
        recycler: '<rect x="2.4" y="6.4" width="11.2" height="6" rx="1" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<path d="M6 4.4h4l1.6 2H4.4z" fill="currentColor"/>',
        attack: '<path d="M2.2 2.2l9 9M13.8 2.2l-9 9" fill="none" stroke="currentColor" stroke-width="1.6"/>',
        transportSmall: '<rect x="1.6" y="5" width="7" height="5" fill="none" stroke="currentColor" stroke-width="1.2"/>' +
            '<path d="M8.6 6.6h2.8l2 2v1.4H8.6z" fill="none" stroke="currentColor" stroke-width="1.2"/>',
        spySmall: '<circle cx="8" cy="8" r="2.4" fill="none" stroke="currentColor" stroke-width="1.3"/>' +
            '<path d="M1.6 8C3.4 4.8 5.6 3.2 8 3.2s4.6 1.6 6.4 4.8c-1.8 3.2-4 4.8-6.4 4.8S3.4 11.2 1.6 8z" fill="none" stroke="currentColor" stroke-width="1.1"/>',
        galaxy: '<circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.2"/>' +
            '<path d="M2 8h12M8 2c2 2 2 10 0 12M8 2C6 4 6 12 8 14" fill="none" stroke="currentColor" stroke-width="1"/>',
        logout: '<path d="M6.4 2.4H3.2v11.2h3.2" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
            '<path d="M7 8h7M11.4 5.6 13.8 8l-2.4 2.4" fill="none" stroke="currentColor" stroke-width="1.4"/>',

        // Resources (the colours of the 0.84 resource bar).
        metal: '<path d="M8 1.8 14 8l-6 6.2L2 8z" fill="#b9b9b9"/>',
        crystal: '<path d="M8 1.8 14 8l-6 6.2L2 8z" fill="#8fd3ff"/>',
        deuterium: '<path d="M8 1.6c2.6 3.2 4.2 5.4 4.2 7.4A4.2 4.2 0 0 1 8 14.4a4.2 4.2 0 0 1-4.2-5.4c0-2 1.6-4.2 4.2-7.4z" fill="#6fe0a8"/>',
        darkmatter: '<circle cx="8" cy="8" r="6" fill="#b07fe0"/>',
        energy: '<path d="M9.4 1.4 3.6 9h3.2l-.8 5.6L12.4 7H9z" fill="#ffd84d"/>',
        none: '<circle cx="8" cy="8" r="5.4" fill="none" stroke="currentColor" stroke-width="1.2"/>',
    };

    const RESOURCE_ICONS = { 700: 'metal', 701: 'crystal', 702: 'deuterium', 703: 'energy', 704: 'darkmatter' };

    /**
     * Render an icon.
     *
     * @param {string} name Icon name.
     * @param {object} [options] Options: size, className, title.
     * @returns {string} Inline SVG, or an empty string for an unknown icon.
     */
    function icon(name, options) {
        const body = ICONS[name];
        if (!body) return '';
        const opt = options || {};
        const size = opt.size || 14;
        const title = opt.title ? '<title>' + escapeXml(opt.title) + '</title>' : '';
        return '<svg class="ogi-icon' + (opt.className ? ' ' + opt.className : '') + '" width="' + size +
            '" height="' + size + '" viewBox="0 0 16 16" aria-hidden="' + (opt.title ? 'false' : 'true') + '"' +
            (opt.title ? ' role="img"' : '') + '>' + title + body + '</svg>';
    }

    /**
     * Icon of a resource id.
     *
     * @param {number} gid Resource id.
     * @param {object} [options] Icon options.
     * @returns {string} Inline SVG.
     */
    function resourceIcon(gid, options) {
        const name = RESOURCE_ICONS[gid];
        return icon(name || 'none', options);
    }

    /**
     * Icon followed by text.
     *
     * @param {string} name Icon name.
     * @param {string} text Text (already escaped or trusted HTML).
     * @param {object} [options] Icon options.
     * @returns {string} HTML.
     */
    function withIcon(name, text, options) {
        return icon(name, options) + (options && options.space === false ? '' : ' ') + text;
    }

    /**
     * Escape text for an XML/SVG context.
     *
     * @param {string} text Text.
     * @returns {string} Escaped text.
     */
    function escapeXml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    const api = {
        ICONS: ICONS,
        RESOURCE_ICONS: RESOURCE_ICONS,
        icon: icon,
        resourceIcon: resourceIcon,
        withIcon: withIcon,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
