/**
 * OGame Infinity 0.84 — local storage for the collected data.
 *
 * The original OGame Infinity extension keeps everything in the browser
 * (`localStorage["ogk-data"]` plus a per-universe galaxy database). This port does
 * the same: the 0.84 server has no API for historical data, so the extension
 * builds its own database from the pages the player opens.
 *
 * The store is deliberately storage-agnostic: the browser passes `localStorage`,
 * the Node test suite passes a tiny in-memory object, and the analyzers stay
 * testable without a DOM.
 */
(function (root) {
    'use strict';

    const VERSION = 1;

    // Caps that keep the browser storage small. The message list of 0.84 shows at
    // most 50 messages, so these limits cover months of play.
    const LIMITS = {
        messages: 2000,
        activity: 500,
        planets: 5000,
        players: 1000,
    };

    /**
     * An in-memory storage adapter (used by the tests and as a fallback when
     * localStorage is unavailable).
     *
     * @returns {object} Storage adapter.
     */
    function memoryStorage() {
        const map = {};
        return {
            getItem: function (key) { return Object.prototype.hasOwnProperty.call(map, key) ? map[key] : null; },
            setItem: function (key, value) { map[key] = String(value); },
            removeItem: function (key) { delete map[key]; },
        };
    }

    /**
     * Check that a Web Storage object is really usable.
     *
     * Browsers deny storage in several situations (a sandboxed frame, blocked
     * third-party storage, "block all cookies"): merely reading
     * `window.localStorage` can throw a SecurityError, and the first write can
     * throw a QuotaExceededError. The extension must degrade to an in-memory
     * database instead of breaking the game page, so the candidate is probed.
     *
     * @param {object} candidate Storage object (may be undefined).
     * @returns {object|null} The storage when it works, null otherwise.
     */
    function safeStorage(candidate) {
        try {
            if (!candidate) return null;
            const probe = '__ogi_probe__';
            candidate.setItem(probe, '1');
            if (candidate.getItem(probe) !== '1') return null;
            candidate.removeItem(probe);
            return candidate;
        } catch (error) {
            return null;
        }
    }

    /**
     * Build the storage key of a universe.
     *
     * @param {string} universe Universe label ("Uni1").
     * @param {string} [host] Game host, so two servers never share a database.
     * @returns {string} Storage key.
     */
    function storageKey(universe, host) {
        return 'ogi084:' + (host || 'localhost') + ':' + (universe || 'uni');
    }

    /**
     * Fresh, empty database.
     *
     * @param {string} universe Universe label.
     * @returns {object} Database.
     */
    function emptyData(universe) {
        return {
            version: VERSION,
            universe: universe || null,
            lang: null,
            spies: [],
            expeditions: [],
            combats: [],
            harvests: [],
            trades: [],
            messages: {},   // "kind:id" -> timestamp, for deduplication
            planets: {},    // "g:s:p" -> planet history
            players: {},    // player name -> player history
            activity: {},   // "g:s:p" -> observations
            meta: { lastCrawl: null, lastScan: null, spiesSeen: 0, battlesSeen: 0 },
        };
    }

    /**
     * Create a store.
     *
     * @param {object} options Options: key, storage, universe.
     * @returns {object} Store API.
     */
    function createStore(options) {
        const opt = options || {};
        const storage = opt.storage || memoryStorage();
        const key = opt.key || storageKey(opt.universe);
        let data = null;

        function load() {
            let raw = null;
            try {
                raw = storage.getItem(key);
            } catch (error) {
                // Blocked storage: keep the data of this page only.
                raw = null;
            }
            if (raw) {
                try {
                    const parsed = JSON.parse(raw);
                    if (parsed && parsed.version === VERSION) {
                        data = parsed;
                        return data;
                    }
                } catch (e) {
                    // Corrupted data: start over instead of breaking the page.
                }
            }
            data = emptyData(opt.universe);
            return data;
        }

        function save() {
            if (!data) return;
            try {
                storage.setItem(key, JSON.stringify(data));
            } catch (e) {
                // Quota exceeded: drop the oldest half of the biggest lists.
                data.spies = data.spies.slice(0, Math.floor(data.spies.length / 2));
                data.combats = data.combats.slice(0, Math.floor(data.combats.length / 2));
                data.expeditions = data.expeditions.slice(0, Math.floor(data.expeditions.length / 2));
                try {
                    storage.setItem(key, JSON.stringify(data));
                } catch (e2) {
                    // Give up silently: the extension must never break the game.
                }
            }
        }

        function db() {
            if (!data) load();
            return data;
        }

        /**
         * Remember a message so it is not stored twice (the extension re-reads
         * message folders on every visit).
         *
         * @param {string} kind Message kind.
         * @param {number} id Message id.
         * @returns {boolean} True when the message is new.
         */
        function isNewMessage(kind, id) {
            return !Object.prototype.hasOwnProperty.call(db().messages, kind + ':' + id);
        }

        /**
         * Mark a message as stored.
         *
         * @param {string} kind Message kind.
         * @param {number} id Message id.
         * @param {number} timestamp Message timestamp.
         */
        function markMessage(kind, id, timestamp) {
            const messages = db().messages;
            messages[kind + ':' + id] = timestamp || Math.floor(Date.now() / 1000);
            const keys = Object.keys(messages);
            if (keys.length > LIMITS.messages) {
                keys.sort(function (a, b) { return messages[a] - messages[b]; });
                for (let i = 0; i < keys.length - LIMITS.messages; i++) delete messages[keys[i]];
            }
        }

        /**
         * Append a record to one of the collections, newest first.
         *
         * @param {string} bucket Bucket name (spies, combats, expeditions, harvests, trades).
         * @param {object} record Record.
         */
        function push(bucket, record) {
            const list = db()[bucket];
            if (!list) return;
            list.push(record);
            list.sort(function (a, b) { return (b.date || 0) - (a.date || 0); });
            if (list.length > LIMITS.messages) list.length = LIMITS.messages;
        }

        /**
         * Find a stored record by the message it came from.
         *
         * @param {string} bucket Bucket name.
         * @param {number} msgId Message id.
         * @returns {object|null} Record.
         */
        function findRecord(bucket, msgId) {
            const list = db()[bucket] || [];
            for (const record of list) {
                if (record.msgId === msgId) return record;
            }
            return null;
        }

        /**
         * Merge data into a stored record (a battle report link is stored before
         * its report body is known).
         *
         * @param {string} bucket Bucket name.
         * @param {number} msgId Message id.
         * @param {object} patch Fields to merge.
         * @returns {object|null} Updated record.
         */
        function updateRecord(bucket, msgId, patch) {
            const record = findRecord(bucket, msgId);
            if (!record) return null;
            Object.assign(record, patch);
            save();
            return record;
        }

        /**
         * Update the history of a planet (from a spy report or a galaxy scan).
         *
         * @param {object} observed Observed planet: coords, name, owner, type.
         */
        function observePlanet(observed) {
            if (!observed || !observed.coords) return;
            const planets = db().planets;
            const coords = observed.coords.g + ':' + observed.coords.s + ':' + observed.coords.p;
            const entry = planets[coords] || {
                coords: { g: observed.coords.g, s: observed.coords.s, p: observed.coords.p },
                names: [],
                owners: [],
                firstSeen: observed.date || Math.floor(Date.now() / 1000),
                lastSeen: 0,
                reports: 0,
                type: null,
            };
            entry.lastSeen = Math.max(entry.lastSeen, observed.date || Math.floor(Date.now() / 1000));
            if (observed.name && entry.names.indexOf(observed.name) === -1) entry.names.push(observed.name);
            if (observed.owner && entry.owners.indexOf(observed.owner) === -1) entry.owners.push(observed.owner);
            if (observed.type) entry.type = observed.type;
            if (observed.report) entry.reports++;
            planets[coords] = entry;
            const keys = Object.keys(planets);
            if (keys.length > LIMITS.planets) {
                keys.sort(function (a, b) { return planets[a].lastSeen - planets[b].lastSeen; });
                for (let i = 0; i < keys.length - LIMITS.planets; i++) delete planets[keys[i]];
            }
        }

        /**
         * Update the history of a player.
         *
         * @param {object} observed Observed player: name, points, status, planets.
         */
        function observePlayer(observed) {
            if (!observed || !observed.name) return;
            const players = db().players;
            const entry = players[observed.name] || {
                name: observed.name,
                firstSeen: observed.date || Math.floor(Date.now() / 1000),
                lastSeen: 0,
                points: null,
                status: null,
                coords: [],
            };
            entry.lastSeen = Math.max(entry.lastSeen, observed.date || Math.floor(Date.now() / 1000));
            if (observed.points !== undefined && observed.points !== null) entry.points = observed.points;
            if (observed.status) entry.status = observed.status;
            if (observed.coords) {
                const text = observed.coords.g + ':' + observed.coords.s + ':' + observed.coords.p;
                if (entry.coords.indexOf(text) === -1) entry.coords.push(text);
            }
            players[observed.name] = entry;
        }

        /**
         * Record an activity observation of a planet or moon.
         *
         * @param {number} g Galaxy.
         * @param {number} s System.
         * @param {number} p Position.
         * @param {string} marker Activity marker ("*" for <15 min, "42" minutes, ...).
         * @param {number} now Timestamp.
         */
        function observeActivity(g, s, p, marker, now) {
            const coords = g + ':' + s + ':' + p;
            const activity = db().activity;
            const list = activity[coords] || [];
            const stamp = now || Math.floor(Date.now() / 1000);
            const last = list[list.length - 1];
            if (!last || last.marker !== marker || stamp - last.t > 60) {
                list.push({ t: stamp, marker: marker });
            } else {
                last.t = stamp;
            }
            if (list.length > LIMITS.activity) list.splice(0, list.length - LIMITS.activity);
            activity[coords] = list;
        }

        function reset() {
            data = emptyData(opt.universe);
            save();
        }

        return {
            VERSION: VERSION,
            LIMITS: LIMITS,
            key: key,
            storage: storage,
            load: load,
            save: save,
            data: db,
            isNewMessage: isNewMessage,
            markMessage: markMessage,
            push: push,
            findRecord: findRecord,
            updateRecord: updateRecord,
            observePlanet: observePlanet,
            observePlayer: observePlayer,
            observeActivity: observeActivity,
            reset: reset,
        };
    }

    const api = {
        VERSION: VERSION,
        LIMITS: LIMITS,
        memoryStorage: memoryStorage,
        safeStorage: safeStorage,
        storageKey: storageKey,
        emptyData: emptyData,
        createStore: createStore,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
