/**
 * Tests of the user interface strings.
 *
 * Two classes of mistakes are easy to make in a panel that renders from strings:
 * a language that lags behind the others, and a translator call that references a
 * key nobody ever defined (the header of the fleet table once printed the literal
 * "COL_CARGO" for exactly that reason). Both are checked here by scanning the
 * sources.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');

const i18n = require('../src/lib/i18n.js');

const SOURCE_ROOT = path.resolve(__dirname, '..', 'src');
const LANGUAGES = ['en', 'ru', 'de'];

/**
 * Collect every JavaScript file of the extension (the generated data files are
 * skipped: they are produced by the generator and carry no translator calls).
 *
 * @param {string} dir Directory.
 * @param {string[]} [out] Accumulator.
 * @returns {string[]} Files.
 */
function sourceFiles(dir, out) {
    const files = out || [];
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) sourceFiles(full, files);
        else if (entry.name.endsWith('.js') && entry.name !== 'loca.js' && entry.name !== 'units.js') files.push(full);
    }
    return files;
}

test('i18n: every language carries the same keys', function () {
    const reference = Object.keys(i18n.STRINGS.en).sort();
    assert.ok(reference.length > 150, 'the string table looks complete');
    for (const lang of LANGUAGES) {
        const keys = Object.keys(i18n.STRINGS[lang]).sort();
        const missing = reference.filter(function (key) { return keys.indexOf(key) === -1; });
        const extra = keys.filter(function (key) { return reference.indexOf(key) === -1; });
        assert.deepEqual(missing, [], lang + ' is missing keys');
        assert.deepEqual(extra, [], lang + ' has keys the reference language does not');
    }
});

test('i18n: no language leaves a string empty', function () {
    for (const lang of LANGUAGES) {
        for (const key of Object.keys(i18n.STRINGS[lang])) {
            const value = i18n.STRINGS[lang][key];
            assert.equal(typeof value, 'string', lang + '.' + key + ' is not a string');
            assert.notEqual(value.trim(), '', lang + '.' + key + ' is empty');
        }
    }
});

test('i18n: every key used in the code exists', function () {
    const known = new Set(Object.keys(i18n.STRINGS.en));
    const missing = [];
    for (const file of sourceFiles(SOURCE_ROOT)) {
        const source = fs.readFileSync(file, 'utf8');
        const relative = path.relative(SOURCE_ROOT, file);

        // t('KEY'), i18n('KEY'), ctx.i18n('KEY'), right after "OGI.".
        const calls = /(?:\bt|\bi18n|OGI\.i18n)\(\s*'([A-Z][A-Z0-9_]{2,})'/g;
        let match;
        while ((match = calls.exec(source)) !== null) {
            if (!known.has(match[1])) missing.push(relative + ': ' + match[1]);
        }
        // The ternary form: t(collapsed ? 'ACTION_EXPAND' : 'ACTION_COLLAPSE').
        const ternary = /\?\s*'([A-Z][A-Z0-9_]{2,})'\s*:\s*'([A-Z][A-Z0-9_]{2,})'/g;
        while ((match = ternary.exec(source)) !== null) {
            for (const key of [match[1], match[2]]) {
                if (!known.has(key)) missing.push(relative + ': ' + key + ' (ternary)');
            }
        }
    }
    assert.deepEqual(missing, [], 'unknown keys used in the code');
});

test('i18n: the translator falls back instead of printing nothing', function () {
    const t = i18n.createI18n('en');
    assert.equal(t('TAB_SPY'), i18n.STRINGS.en.TAB_SPY);
    assert.equal(t('THIS_KEY_DOES_NOT_EXIST'), 'THIS_KEY_DOES_NOT_EXIST', 'an unknown key returns itself');
    assert.equal(i18n.createI18n('xx').TITLE, undefined, 'createI18n returns a function');
    assert.equal(i18n.createI18n('xx')('TAB_SPY'), i18n.STRINGS.en.TAB_SPY, 'an unknown language falls back to English');

    const ru = i18n.createI18n('ru');
    assert.equal(ru('MSG_DONE', { N: 3, T: 4 }).indexOf('3') !== -1, true, 'placeholders are substituted');
    assert.equal(ru('MSG_DONE', { N: 3, T: 4 }).indexOf('{N}'), -1);
});
