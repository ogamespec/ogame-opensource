/**
 * OGame Infinity 0.84 — test helpers.
 *
 * The test suite runs on plain Node (`node --test ogi/tests`) without any
 * dependency and checks the extension against the golden snapshots of the real
 * 0.84 pages (testing/golden/<lang>/), which are the same fixtures the project's
 * own PHPUnit page tests use.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const GOLDEN_ROOT = path.join(REPO_ROOT, 'testing', 'golden');

/**
 * Path of a golden snapshot.
 *
 * @param {string} lang Language code.
 * @param {string} file File name.
 * @returns {string} Absolute path.
 */
function goldenPath(lang, file) {
    return path.join(GOLDEN_ROOT, lang, file);
}

/**
 * Read a golden snapshot.
 *
 * @param {string} lang Language code.
 * @param {string} file File name.
 * @returns {string} File contents.
 */
function readGolden(lang, file) {
    return fs.readFileSync(goldenPath(lang, file), 'utf8');
}

/**
 * Timestamp used by the tests instead of "now" (2026-09-01 12:00:00 UTC), which
 * matches the fixture universe the golden snapshots were generated with.
 */
const NOW = 1788264000;

module.exports = {
    REPO_ROOT,
    GOLDEN_ROOT,
    NOW,
    goldenPath,
    readGolden,
};
