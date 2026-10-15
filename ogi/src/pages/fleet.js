/**
 * OGame Infinity 0.84 — fleet dispatcher helpers.
 *
 * The original OGame Infinity improves the fleet pages (fill the cargo, pick a
 * target, presets for a fleet save). The 0.84 dispatcher is a three step wizard with
 * plain inputs, so the helpers are small and purely local — they only change the
 * form the player is looking at, no request is made:
 *
 *   flotten1  ship<gid> / maxship<gid> / capacity<gid> / consumption<gid>
 *   flotten2  galaxy/system/planet, speedfactor (1..10), the carried ships
 *   flotten3  resource1..3 (cargo to take) and thisresource1..3 (available)
 *
 * On every step the panel also lists the best targets of the farm list, so a target
 * found in the spy database is two clicks away.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * All ship amount inputs of a fleet page.
     *
     * @returns {Array<{gid: number, input: Element}>} Ship inputs with their object id.
     */
    function shipInputs() {
        const out = [];
        const inputs = document.querySelectorAll('input[name^="ship"]');
        for (let i = 0; i < inputs.length; i++) {
            const match = /^ship(\d+)$/.exec(inputs[i].getAttribute('name') || '');
            if (!match) continue;
            out.push({ gid: parseInt(match[1], 10), input: inputs[i] });
        }
        return out;
    }

    /**
     * Value of a page input.
     *
     * @param {string} name Input name.
     * @returns {number} Number value (0 when the input is missing).
     */
    function inputValue(name) {
        const field = document.querySelector('[name="' + name + '"]');
        if (!field) return 0;
        const value = parseFloat(String(field.value).replace(/[^\d.-]/g, ''));
        return isFinite(value) ? value : 0;
    }

    /**
     * Write a value into an input and let the game's own scripts see it.
     *
     * @param {Element} field Input.
     * @param {number|string} value Value.
     */
    function setValue(field, value) {
        if (!field) return;
        field.value = String(value);
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.dispatchEvent(new Event('keyup', { bubbles: true }));
    }

    /**
     * Fill every ship input with its maximum, or empty the form.
     *
     * @param {boolean} all True to select everything, false to clear.
     */
    function selectShips(all) {
        for (const entry of shipInputs()) {
            const max = inputValue('maxship' + entry.gid);
            setValue(entry.input, all ? max : 0);
        }
    }

    /**
     * Total cargo capacity, fuel consumption and ship count of the current selection.
     *
     * @returns {{capacity: number, consumption: number, ships: number}} Summary.
     */
    function selectionSummary() {
        const summary = { capacity: 0, consumption: 0, ships: 0 };
        for (const entry of shipInputs()) {
            const amount = parseFloat(entry.input.value) || 0;
            if (!(amount > 0)) continue;
            summary.ships += amount;
            summary.capacity += amount * inputValue('capacity' + entry.gid);
            summary.consumption += amount * inputValue('consumption' + entry.gid);
        }
        return summary;
    }

    /**
     * Distribute the available resources into the cargo fields of step three,
     * limited by the cargo capacity of the selected fleet.
     */
    function fillCargo() {
        const summary = selectionSummary();
        let remaining = summary.capacity;
        const fields = ['resource1', 'resource2', 'resource3'];
        for (let i = 0; i < fields.length; i++) {
            const field = document.querySelector('[name="' + fields[i] + '"]');
            if (!field) continue;
            const available = inputValue('this' + fields[i]);
            const amount = Math.max(0, Math.min(available, remaining));
            remaining -= amount;
            setValue(field, amount);
        }
    }

    /**
     * Set the speed of the flight (the game's speedfactor, 1 = 10%, 10 = 100%).
     *
     * @param {number} factor Speed factor.
     */
    function setSpeed(factor) {
        const field = document.querySelector('[name="speedfactor"]');
        if (!field) return;
        setValue(field, factor);
    }

    /**
     * Handle a fleet page (flotten1, flotten2, flotten3).
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountFleet = function (ctx) {
        const i18n = ctx.i18n;
        const t = i18n;
        const content = document.getElementById('content') || document.body;
        const host = OGI.mountHost(content, 'ogi-fleet-host');

        function actions() {
            const buttons = [];
            if (ctx.page === 'flotten1') {
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="all">' + OGI.esc(t('FLEET_ALL')) + '</a>');
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="none">' + OGI.esc(t('FLEET_NONE')) + '</a>');
            }
            if (ctx.page === 'flotten3') {
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="cargo">' + OGI.esc(t('FLEET_FILL_CARGO')) + '</a>');
            }
            if (ctx.page === 'flotten2' || ctx.page === 'flotten3') {
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="speed10">100%</a>');
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="speed5">50%</a>');
                buttons.push('<a href="#" class="ogi-action" data-ogi-fleet="speed1">10%</a>');
            }
            return buttons.join(' | ');
        }

        function render(status) {
            const body = [];
            if (status) body.push('<div class="ogi-status">' + OGI.esc(status) + '</div>');
            if (ctx.page === 'flotten1') {
                const summary = selectionSummary();
                body.push(OGI.keyValue([
                    [OGI.esc(t('FLEET_SHIPS')), OGI.num(summary.ships)],
                    [OGI.esc(t('FLEET_CAPACITY')), OGI.num(summary.capacity)],
                    [OGI.esc(t('FLEET_CONSUMPTION')), OGI.num(summary.consumption)],
                ]));
            }
            body.push(targetList());
            host.innerHTML = OGI.panel(t('TAB_FLEET'), body.join(''), {
                id: 'ogi-fleet',
                actions: actions(),
            });
            wire();
        }

        /**
         * The best targets of the farm list, as shortcuts into the dispatcher.
         *
         * @returns {string} HTML.
         */
        function targetList() {
            const settings = OGI.read(ctx.store);
            const farms = OGI.farmList(ctx.store.data(), {
                settings: settings,
                unitsData: OGI.UNITS,
                limit: 5,
            });
            if (!farms.length) return '<div class="ogi-note">' + OGI.esc(t('FARM_NO_TARGETS')) + '</div>';

            const rows = farms.map(function (row) {
                const url = OGI.targetLinks(row, ctx.session);
                return [
                    OGI.esc(row.key),
                    OGI.esc(row.player || '-'),
                    OGI.num(row.loot.total),
                    OGI.num(row.needed.smallCargo) + ' / ' + OGI.num(row.needed.largeCargo),
                    row.active ? OGI.tag(t('ACTIVE'), 'bad') : OGI.tag(t('INACTIVE'), 'good'),
                    '<a href="' + OGI.esc(url.attack) + '">' + OGI.esc(t('FARM_ATTACK')) + '</a> ' +
                        '<a href="' + OGI.esc(url.transport) + '">' + OGI.esc(t('FARM_TRANSPORT')) + '</a> ' +
                        '<a href="' + OGI.esc(url.spy) + '">' + OGI.esc(t('FARM_SPY')) + '</a>',
                ];
            });
            return OGI.table([t('COL_COORDS'), t('COL_PLAYER'), t('COL_LOOT'), 'SC / LC',
                t('COL_ACTIVITY'), t('FARM_TARGET')], rows);
        }

        function wire() {
            const links = host.querySelectorAll('[data-ogi-fleet]');
            for (let i = 0; i < links.length; i++) {
                links[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const action = this.getAttribute('data-ogi-fleet');
                    let status = '';
                    if (action === 'all') {
                        selectShips(true);
                        status = t('FLEET_ALL_DONE');
                    } else if (action === 'none') {
                        selectShips(false);
                        status = t('FLEET_NONE_DONE');
                    } else if (action === 'cargo') {
                        fillCargo();
                        status = t('FLEET_CARGO_DONE');
                    } else if (action.indexOf('speed') === 0) {
                        const factor = parseInt(action.slice(5), 10);
                        setSpeed(factor);
                        status = t('FLEET_SPEED_DONE', { P: factor * 10 });
                    }
                    render(status);
                });
            }
        }

        render();
        // Keep the capacity summary in step with what the player types.
        if (ctx.page === 'flotten1') {
            for (const entry of shipInputs()) {
                entry.input.addEventListener('change', function () {
                    const summary = selectionSummary();
                    const cells = host.querySelectorAll('.ogi-kv td');
                    if (cells.length >= 6) {
                        cells[1].textContent = OGI.num(summary.ships);
                        cells[3].textContent = OGI.num(summary.capacity);
                        cells[5].textContent = OGI.num(summary.consumption);
                    }
                });
            }
        }
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
