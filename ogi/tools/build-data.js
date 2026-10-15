#!/usr/bin/env node
/**
 * OGame Infinity 0.84 — locale data generator.
 *
 * The extension is a client-side port of the OGame Infinity browser extension to
 * the OGame 0.84 server (this repository). OGame 0.84 stores no machine-readable
 * data inside its pages: spy reports, battle reports and expedition results are
 * rendered as localized HTML/prose (see game/core/fleet.php, game/core/battle.php,
 * game/core/expedition.php and the matching game/loca/<lang>/ files).
 *
 * To parse those pages the extension needs the very same localization strings the
 * server used to render them, plus the localized object names (ships, defence,
 * buildings, research). This script extracts them from game/loca/<lang>_<lang>/
 * and writes src/data/loca.json.
 *
 * Usage (from the repository root or from the ogi/ folder):
 *
 *     node ogi/tools/build-loca.js
 *
 * The output is committed to the repository so the extension works when loaded
 * unpacked without running the generator.
 */

'use strict';

const fs = require('fs');
const path = require('path');

// Sections of game/loca that carry the strings the extension has to recognize.
// The order is the lookup priority: the first occurrence of a key wins.
const SECTIONS = [
    'common',       // NAME_<gid> — full object names, resource names
    'technames',    // NAME_<gid> — object names used on the pages
    'menu',         // MENU_* — left menu labels (also used for language detection)
    'espionage',    // SPY_* — spy report layout
    'battlereport', // BATTLE_*, SNAME_* — battle report layout
    'expedition',   // EXP_* — expedition result texts
    'fleetmsg',     // FLEET_* — fleet messages (transport, recycle, deploy, return)
    'messages',     // MSG_* — message list
    'events',       // EVENT_* — overview event list
    'galaxy',       // GALAXY_* — galaxy tooltips (planet, moon, debris field)
    'build',        // BUILD_* — the build pages (level, price, duration, buttons)
];

// Languages supported by the game core (see game/core/loca.php, game/loca/).
const LANGUAGES = ['de', 'en', 'es', 'fr', 'it', 'jp', 'ru'];

const REPO_ROOT = path.resolve(__dirname, '..', '..');
const LOCA_ROOT = path.join(REPO_ROOT, 'game', 'loca');
const OUT_FILE = path.resolve(__dirname, '..', 'src', 'data', 'loca.js');
const UNITS_FILE = path.resolve(__dirname, '..', 'src', 'data', 'units.js');

// Sources of the game's global constants. Locale keys and values are built from
// them (for example $LOCA["en"]["NAME_".GID_RC_METAL] = "Metal";), so without the
// constants table those strings cannot be resolved outside of PHP.
const CONST_SOURCES = [
    path.join(REPO_ROOT, 'game', 'core', 'techs.php'),
    path.join(REPO_ROOT, 'game', 'core', 'defs.php'),
];

/**
 * Parse `const NAME = <scalar>;` declarations of the given PHP sources.
 *
 * @param {string[]} files PHP file paths.
 * @returns {Object<string, string|number>} Constants table.
 */
function parseConstants(files) {
    const table = {};
    for (const file of files) {
        if (!fs.existsSync(file)) continue;
        const source = fs.readFileSync(file, 'utf8');
        const re = /\bconst\s+([A-Za-z_][A-Za-z0-9_]*)\s*=\s*([^;]+);/g;
        let match;
        while ((match = re.exec(source)) !== null) {
            const name = match[1];
            const expr = match[2].trim();
            const asString = evalPhpString(expr);
            if (asString !== null && expr.indexOf('"') !== -1) {
                table[name] = asString;
                continue;
            }
            if (/^-?\d+$/.test(expr)) {
                table[name] = parseInt(expr, 10);
                continue;
            }
            if (/^[A-Za-z_][A-Za-z0-9_]*$/.test(expr) && expr in table) {
                table[name] = table[expr];
            }
        }
    }
    return table;
}

/**
 * Decode a PHP double-quoted string literal body (without the quotes).
 *
 * @param {string} body Raw literal body.
 * @returns {string} Decoded value.
 */
function decodeDoubleQuoted(body) {
    let out = '';
    for (let i = 0; i < body.length; i++) {
        const ch = body[i];
        if (ch !== '\\') {
            out += ch;
            continue;
        }
        const next = body[i + 1];
        i++;
        switch (next) {
            case 'n': out += '\n'; break;
            case 'r': out += '\r'; break;
            case 't': out += '\t'; break;
            case '"': out += '"'; break;
            case '\\': out += '\\'; break;
            case '$': out += '$'; break;
            case '0': out += '\0'; break;
            default:
                // Unknown escape: PHP keeps the backslash and the character.
                out += '\\' + (next === undefined ? '' : next);
                break;
        }
    }
    return out;
}

/**
 * Read the PHP expression that starts at $start and ends with the statement
 * terminating semicolon (a semicolon outside of a string literal).
 *
 * @param {string} source PHP file source.
 * @param {number} start Index of the first character of the expression.
 * @returns {string} Expression source (without the semicolon).
 */
function readExpression(source, start) {
    let i = start;
    let inDouble = false;
    let inSingle = false;
    while (i < source.length) {
        const ch = source[i];
        if (inDouble) {
            if (ch === '\\') { i += 2; continue; }
            if (ch === '"') inDouble = false;
        } else if (inSingle) {
            if (ch === '\\') { i += 2; continue; }
            if (ch === "'") inSingle = false;
        } else if (ch === '"') {
            inDouble = true;
        } else if (ch === "'") {
            inSingle = true;
        } else if (ch === ';') {
            return source.slice(start, i);
        }
        i++;
    }
    return source.slice(start);
}

/**
 * Read a PHP bracket expression (the contents of `[ ... ]`) that starts at
 * $start (the index right after the opening bracket).
 *
 * @param {string} source PHP source.
 * @param {number} start Index of the first character inside the brackets.
 * @returns {string} Expression source (without the closing bracket).
 */
function readBracketExpression(source, start) {
    let i = start;
    let inDouble = false;
    let inSingle = false;
    while (i < source.length) {
        const ch = source[i];
        if (inDouble) {
            if (ch === '\\') { i += 2; continue; }
            if (ch === '"') inDouble = false;
        } else if (inSingle) {
            if (ch === '\\') { i += 2; continue; }
            if (ch === "'") inSingle = false;
        } else if (ch === '"') {
            inDouble = true;
        } else if (ch === "'") {
            inSingle = true;
        } else if (ch === ']') {
            return source.slice(start, i);
        }
        i++;
    }
    return source.slice(start);
}

/**
 * Evaluate a PHP string expression that consists of quoted literals, integers
 * and constants of the constants table joined by the concatenation operator.
 * Returns null when the expression contains anything else (variables, function
 * calls, unknown constants), because such strings cannot be reproduced without
 * running the game itself.
 *
 * @param {string} expr PHP expression source.
 * @param {Object<string, string|number>} [constants] Constants table.
 * @returns {string|null} Evaluated string or null.
 */
function evalPhpString(expr, constants) {
    const consts = constants || {};
    let out = '';
    let i = 0;
    let sawLiteral = false;
    while (i < expr.length) {
        const ch = expr[i];
        if (ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r' || ch === '.' || ch === '(' || ch === ')') {
            i++;
            continue;
        }
        if (ch === '"') {
            let j = i + 1;
            let raw = '';
            while (j < expr.length) {
                if (expr[j] === '\\') { raw += expr[j] + (expr[j + 1] || ''); j += 2; continue; }
                if (expr[j] === '"') break;
                raw += expr[j];
                j++;
            }
            if (j >= expr.length) return null; // unterminated literal
            out += decodeDoubleQuoted(raw);
            sawLiteral = true;
            i = j + 1;
            continue;
        }
        if (ch === "'") {
            let j = i + 1;
            let raw = '';
            while (j < expr.length) {
                if (expr[j] === '\\') { raw += expr[j] + (expr[j + 1] || ''); j += 2; continue; }
                if (expr[j] === "'") break;
                raw += expr[j];
                j++;
            }
            if (j >= expr.length) return null;
            // In single quotes only \\ and \' are escapes.
            out += raw.replace(/\\\\/g, '\\').replace(/\\'/g, "'");
            sawLiteral = true;
            i = j + 1;
            continue;
        }
        if (/[0-9]/.test(ch)) {
            let j = i;
            while (j < expr.length && /[0-9]/.test(expr[j])) j++;
            out += expr.slice(i, j);
            sawLiteral = true;
            i = j;
            continue;
        }
        if (/[A-Za-z_]/.test(ch)) {
            let j = i;
            while (j < expr.length && /[A-Za-z0-9_]/.test(expr[j])) j++;
            const name = expr.slice(i, j);
            if (!(name in consts)) return null; // unknown constant or a variable
            out += String(consts[name]);
            sawLiteral = true;
            i = j;
            continue;
        }
        // Comment or something non-literal: give up on this key.
        return null;
    }
    return sawLiteral ? out : null;
}

/**
 * Parse a single locale file into a key/value map.
 *
 * @param {string} source PHP source of the file.
 * @param {Object<string, string|number>} [constants] Constants table.
 * @returns {{ values: Object<string,string>, unresolved: string[] }}
 */
function parseLocaFile(source, constants) {
    const values = {};
    const unresolved = [];
    const re = /\$LOCA\s*\[\s*"([A-Za-z_][A-Za-z0-9_]*)"\s*\]\s*\[/g;
    let match;
    while ((match = re.exec(source)) !== null) {
        const keyExprStart = re.lastIndex;
        const keyExpr = readBracketExpression(source, keyExprStart);
        const afterKey = keyExprStart + keyExpr.length;
        const assign = /^\s*\]\s*=\s*/.exec(source.slice(afterKey));
        if (!assign) continue;
        const exprStart = afterKey + assign[0].length;
        const key = evalPhpString(keyExpr, constants);
        if (key === null || key === '') {
            re.lastIndex = exprStart;
            continue;
        }
        const expr = readExpression(source, exprStart);
        const value = evalPhpString(expr, constants);
        if (value === null) {
            if (!unresolved.includes(key)) unresolved.push(key);
        } else if (!(key in values)) {
            values[key] = value;
        }
        re.lastIndex = exprStart + expr.length;
    }
    return { values, unresolved };
}

/**
 * Read the body of a PHP `array ( ... )` assignment of a global variable.
 *
 * @param {string} source PHP source.
 * @param {string} varName Variable name without the dollar sign.
 * @returns {string|null} Body between the outermost parentheses.
 */
function readArrayBody(source, varName) {
    const re = new RegExp('\\$' + varName + '\\s*=\\s*array\\s*\\(');
    const match = re.exec(source);
    if (!match) return null;
    const bodyStart = match.index + match[0].length;
    let depth = 0;
    for (let i = match.index; i < source.length; i++) {
        const ch = source[i];
        if (ch === '(') depth++;
        else if (ch === ')') {
            depth--;
            if (depth === 0) return source.slice(bodyStart, i);
        }
    }
    return null;
}

/**
 * Split a PHP array body into its top level entries.
 *
 * @param {string} body Array body.
 * @returns {string[]} Entries.
 */
function splitTopLevel(body) {
    const out = [];
    // Comments inside the arrays ("// Ships", "/* Buildings */") would otherwise
    // end up glued to the first entry after them.
    const source = String(body).replace(/\/\/[^\n\r]*/g, '').replace(/\/\*[\s\S]*?\*\//g, '');
    let depth = 0;
    let inDouble = false;
    let inSingle = false;
    let current = '';
    for (let i = 0; i < source.length; i++) {
        const ch = source[i];
        if (inDouble) {
            current += ch;
            if (ch === '\\') { current += body[i + 1] || ''; i++; continue; }
            if (ch === '"') inDouble = false;
            continue;
        }
        if (inSingle) {
            current += ch;
            if (ch === '\\') { current += body[i + 1] || ''; i++; continue; }
            if (ch === "'") inSingle = false;
            continue;
        }
        if (ch === '"') { inDouble = true; current += ch; continue; }
        if (ch === "'") { inSingle = true; current += ch; continue; }
        if (ch === '(' || ch === '[') depth++;
        if (ch === ')' || ch === ']') depth--;
        if (ch === ',' && depth === 0) {
            out.push(current.trim());
            current = '';
            continue;
        }
        current += ch;
    }
    if (current.trim() !== '') out.push(current.trim());
    return out;
}

/**
 * Resolve a PHP constant name (or literal) into a string.
 *
 * @param {string} token Token.
 * @param {Object<string, string|number>} constants Constants table.
 * @returns {string|null} Value.
 */
function resolveToken(token, constants) {
    const text = String(token).trim();
    if (/^-?\d+$/.test(text)) return text;
    const quoted = /^'([^']*)'$/.exec(text) || /^"([^"]*)"$/.exec(text);
    if (quoted) return quoted[1];
    if (Object.prototype.hasOwnProperty.call(constants, text)) return String(constants[text]);
    return null;
}

/**
 * Extract the game's object tables (unit lists, costs and parameters) from the
 * engine sources, so the extension can value a scanned fleet or defence without
 * duplicating any numbers by hand.
 *
 * @param {Object<string, string|number>} constants Constants table.
 * @returns {object} Object tables.
 */
function buildUnits(constants) {
    const techs = path.join(REPO_ROOT, 'game', 'core', 'techs.php');
    const source = fs.readFileSync(techs, 'utf8');
    const out = { costs: {}, params: {}, factor: {}, lists: {} };

    // Object lists: $fleetmap = array ( GID_F_SC, ... );
    const lists = {
        fleet: 'fleetmap',
        defence: 'defmap',
        missiles: 'rakmap',
        buildings: 'buildmap',
        research: 'resmap',
        resources: 'resourcemap',
    };
    for (const name of Object.keys(lists)) {
        const body = readArrayBody(source, lists[name]);
        if (!body) continue;
        const ids = [];
        for (const entry of splitTopLevel(body)) {
            const id = resolveToken(entry, constants);
            if (id !== null && /^\d+$/.test(id)) ids.push(parseInt(id, 10));
        }
        out.lists[name] = ids;
    }

    // Costs: $initial = array ( GID_F_SC => array (GID_RC_METAL=>2000, ...), ... );
    const initialBody = readArrayBody(source, 'initial');
    if (initialBody) {
        for (const entry of splitTopLevel(initialBody)) {
            const match = /^([A-Za-z_][A-Za-z0-9_]*|\d+)\s*=>\s*array\s*\(([\s\S]*)\)$/.exec(entry);
            if (!match) continue;
            const id = resolveToken(match[1], constants);
            if (id === null) continue;
            const cost = { metal: 0, crystal: 0, deuterium: 0, energy: 0 };
            let factor = null;
            for (const inner of splitTopLevel(match[2])) {
                const pair = /^('?[A-Za-z_][A-Za-z0-9_]*'?|"[^"]*"|\d+)\s*=>\s*(-?\d+(?:\.\d+)?)$/.exec(inner);
                if (!pair) continue;
                const key = resolveToken(pair[1], constants);
                const amount = parseFloat(pair[2]);
                if (key === 'factor') { factor = amount; continue; }
                if (key === String(constants.GID_RC_METAL)) cost.metal = amount;
                else if (key === String(constants.GID_RC_CRYSTAL)) cost.crystal = amount;
                else if (key === String(constants.GID_RC_DEUTERIUM)) cost.deuterium = amount;
                else if (key === String(constants.GID_RC_ENERGY)) cost.energy = amount;
            }
            out.costs[id] = cost;
            if (factor !== null) out.factor[id] = factor;
        }
    }

    // Parameters: $UnitParam = array ( GID_F_SC => array ( 4000, 10, 5, 5000, 5000, 10 ), ... );
    const paramsBody = readArrayBody(source, 'UnitParam');
    if (paramsBody) {
        for (const entry of splitTopLevel(paramsBody)) {
            const match = /^([A-Za-z_][A-Za-z0-9_]*|\d+)\s*=>\s*array\s*\(([\s\S]*)\)$/.exec(entry);
            if (!match) continue;
            const id = resolveToken(match[1], constants);
            if (id === null) continue;
            const values = splitTopLevel(match[2]).map(function (value) { return parseInt(value, 10); });
            if (values.length < 6) continue;
            out.params[id] = {
                structure: values[0],
                shield: values[1],
                attack: values[2],
                cargo: values[3],
                speed: values[4],
                cons: values[5],
            };
        }
    }

    return out;
}

/**
 * Build the whole data file.
 *
 * @returns {{ data: object, stats: object }}
 */
function build() {
    const constants = parseConstants(CONST_SOURCES);
    const data = { generator: 'ogi/tools/build-loca.js', sections: SECTIONS, languages: {}, };
    const stats = { languages: {}, unresolved: {}, collisions: [] };

    for (const lang of LANGUAGES) {
        const dir = path.join(LOCA_ROOT, lang + '_' + lang);
        if (!fs.existsSync(dir)) {
            throw new Error('Missing locale folder: ' + dir);
        }
        const table = {};
        stats.languages[lang] = 0;
        stats.unresolved[lang] = [];
        for (const section of SECTIONS) {
            const file = path.join(dir, section + '.php');
            if (!fs.existsSync(file)) continue;
            const { values, unresolved } = parseLocaFile(fs.readFileSync(file, 'utf8'), constants);
            for (const key of Object.keys(values)) {
                if (key in table) {
                    if (table[key] !== values[key]) {
                        stats.collisions.push(lang + '.' + key + ' (' + section + ')');
                    }
                    continue;
                }
                table[key] = values[key];
                stats.languages[lang]++;
            }
            for (const key of unresolved) {
                if (!stats.unresolved[lang].includes(key)) stats.unresolved[lang].push(key);
            }
        }
        data.languages[lang] = table;
    }

    return { data, stats };
}

function main() {
    const { data, stats } = build();
    fs.mkdirSync(path.dirname(OUT_FILE), { recursive: true });
    const banner = [
        '/**',
        ' * OGame Infinity 0.84 — generated locale data. DO NOT EDIT.',
        ' *',
        ' * Extracted from game/loca/<lang>_<lang>/ by ogi/tools/build-data.js.',
        ' * Regenerate with:  node ogi/tools/build-data.js',
        ' */',
        '(function (root) {',
        "    'use strict';",
        '    const LOCA = ' + JSON.stringify(data, null, 1).replace(/\n/g, '\n    ') + ';',
        "    root.OGI = Object.assign(root.OGI || {}, { LOCA: LOCA });",
        "    if (typeof module === 'object' && module.exports) module.exports = LOCA;",
        "})(typeof globalThis !== 'undefined' ? globalThis : this);",
        '',
    ].join('\n');
    fs.writeFileSync(OUT_FILE, banner, 'utf8');

    // Object tables (costs, cargo capacities, object lists) of the engine.
    const units = buildUnits(parseConstants(CONST_SOURCES));
    const unitsBanner = [
        '/**',
        ' * OGame Infinity 0.84 — generated object tables. DO NOT EDIT.',
        ' *',
        ' * Costs and parameters of ships, defence, buildings and research, extracted',
        ' * from game/core/techs.php by ogi/tools/build-data.js.',
        ' * Regenerate with:  node ogi/tools/build-data.js',
        ' */',
        '(function (root) {',
        "    'use strict';",
        '    const UNITS = ' + JSON.stringify(units, null, 1).replace(/\n/g, '\n    ') + ';',
        "    root.OGI = Object.assign(root.OGI || {}, { UNITS: UNITS });",
        "    if (typeof module === 'object' && module.exports) module.exports = UNITS;",
        "})(typeof globalThis !== 'undefined' ? globalThis : this);",
        '',
    ].join('\n');
    fs.mkdirSync(path.dirname(UNITS_FILE), { recursive: true });
    fs.writeFileSync(UNITS_FILE, unitsBanner, 'utf8');

    const rel = path.relative(REPO_ROOT, OUT_FILE).replace(/\\/g, '/');
    console.log('Wrote ' + rel);
    for (const lang of Object.keys(stats.languages)) {
        console.log('  ' + lang + ': ' + stats.languages[lang] + ' keys' +
            (stats.unresolved[lang].length ? ', ' + stats.unresolved[lang].length + ' unresolved (skipped)' : ''));
    }
    if (stats.collisions.length) {
        console.log('  key collisions with different values: ' + stats.collisions.length);
        for (const c of stats.collisions.slice(0, 20)) console.log('    ' + c);
    }
    console.log('Wrote ' + path.relative(REPO_ROOT, UNITS_FILE).replace(/\\/g, '/') +
        ' (' + Object.keys(units.costs).length + ' costs, ' + Object.keys(units.params).length + ' parameter sets, ' +
        (units.lists.fleet || []).length + ' ships, ' + (units.lists.defence || []).length + ' defence units)');
}

if (require.main === module) {
    main();
}

module.exports = {
    parseLocaFile,
    evalPhpString,
    decodeDoubleQuoted,
    build,
    buildUnits,
    SECTIONS,
    LANGUAGES,
    OUT_FILE,
    UNITS_FILE,
};
