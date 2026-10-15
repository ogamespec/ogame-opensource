/**
 * Tests of the extension packaging: the manifest must list files that exist, and
 * the scripts must load in exactly the order the manifest declares — in a browser
 * they are plain classic scripts sharing the `window.OGI` namespace, so a missing
 * file or a wrong order is only visible at runtime otherwise.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const helpers = require('./helpers.js');

const OGI_ROOT = path.resolve(__dirname, '..');
const manifest = JSON.parse(fs.readFileSync(path.join(OGI_ROOT, 'manifest.json'), 'utf8'));

test('manifest: every declared file exists', function () {
    assert.equal(manifest.manifest_version, 3);
    assert.ok(manifest.content_scripts.length >= 1);
    for (const entry of manifest.content_scripts) {
        for (const file of entry.js || []) {
            assert.ok(fs.existsSync(path.join(OGI_ROOT, file)), 'missing script ' + file);
        }
        for (const file of entry.css || []) {
            assert.ok(fs.existsSync(path.join(OGI_ROOT, file)), 'missing stylesheet ' + file);
        }
    }
});

test('scripts: load in manifest order and expose the extension API', function () {
    const scripts = manifest.content_scripts[0].js;
    const storage = {};
    const sandbox = {
        console: console,
        setTimeout: setTimeout,
        fetch: function () { return Promise.reject(new Error('no network in this test')); },
        localStorage: {
            getItem: function (key) { return storage[key] === undefined ? null : storage[key]; },
            setItem: function (key, value) { storage[key] = String(value); },
            removeItem: function (key) { delete storage[key]; },
        },
        location: { search: '', pathname: '/', host: 'localhost' },
        document: {
            readyState: 'complete',
            title: 'Uni1 OGame',
            documentElement: { outerHTML: '<html><head><title>Uni1 OGame</title></head><body></body></html>' },
            addEventListener: function () {},
            getElementById: function () { return null; },
            querySelector: function () { return null; },
            querySelectorAll: function () { return []; },
            createElement: function () {
                return {
                    id: '',
                    className: '',
                    innerHTML: '',
                    appendChild: function () {},
                    addEventListener: function () {},
                    querySelectorAll: function () { return []; },
                    setAttribute: function () {},
                };
            },
            body: { appendChild: function () {} },
        },
    };
    sandbox.window = sandbox;
    sandbox.globalThis = sandbox;
    vm.createContext(sandbox);

    for (const file of scripts) {
        const code = fs.readFileSync(path.join(OGI_ROOT, file), 'utf8');
        vm.runInContext(code, sandbox, { filename: file });
    }

    const OGI = sandbox.window.OGI;
    assert.ok(OGI, 'the OGI namespace is missing');
    for (const name of [
        'LOCA', 'UNITS', 'parseSpyReport', 'parseBattleReport', 'parseExpedition', 'parseGalaxyPage',
        'parseMessagesPage', 'createLoca', 'createStore', 'createCollector', 'spyAnalysis',
        'renderAnalyzer', 'createI18n', 'mountMessages', 'mountBericht', 'mountGalaxy',
    ]) {
        assert.ok(OGI[name], 'OGI.' + name + ' is missing');
    }
    assert.ok(Object.keys(OGI.LOCA.languages).length === 7);
});

test('scripts: the extension stores data of the visited universe', function () {
    const store = require('../src/lib/store.js').createStore({
        universe: 'Uni1',
        host: 'localhost',
        storage: require('../src/lib/store.js').memoryStorage(),
    });
    store.load();
    store.push('spies', { date: 1, coords: { g: 1, s: 1, p: 1 } });
    store.save();
    assert.ok(store.key.indexOf('Uni1') !== -1);
    assert.equal(JSON.parse(store.storage.getItem(store.key)).spies.length, 1);
});

test('repository: the extension does not modify the game itself', function () {
    // The port is a client-side extension: it must not change any file of the
    // 0.84 engine, and it must not be part of the game's own test suites.
    const phpunit = fs.readFileSync(path.join(helpers.REPO_ROOT, 'phpunit.xml'), 'utf8');
    assert.equal(phpunit.indexOf('ogi'), -1, 'phpunit.xml must not include the extension');
    assert.ok(fs.existsSync(path.join(helpers.REPO_ROOT, 'game', 'loca', 'en_en', 'fleetmsg.php')));
});
