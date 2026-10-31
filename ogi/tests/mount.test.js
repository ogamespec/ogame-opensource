/**
 * Tests of the panel mounting.
 *
 * The 0.84 pages center their tables inside `#content`, so the injected panels must
 * be centered the same way — a plain div stuck to the left edge is what made the
 * panels look "shifted to the left" of the game's own content.
 *
 * The test runs on a tiny DOM stub: enough of the element API for the mounting
 * helpers, no browser needed.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const path = require('path');

const mount = require('../src/lib/mount.js');

/**
 * A minimal element.
 *
 * @param {string} tag Tag name.
 * @param {string} [id] Element id.
 * @returns {object} Element.
 */
function element(tag, id) {
    const node = {
        tagName: tag.toUpperCase(),
        id: id || '',
        className: '',
        children: [],
        parentNode: null,
        firstChild: null,
        innerHTML: '',
        appendChild: function (child) {
            child.parentNode = node;
            node.children.push(child);
            node.firstChild = node.children[0];
            return child;
        },
        insertBefore: function (child, before) {
            child.parentNode = node;
            const index = node.children.indexOf(before);
            if (index === -1) node.children.push(child);
            else node.children.splice(index, 0, child);
            node.firstChild = node.children[0];
            return child;
        },
        removeChild: function (child) {
            const index = node.children.indexOf(child);
            if (index !== -1) node.children.splice(index, 1);
            child.parentNode = null;
            node.firstChild = node.children[0] || null;
            return child;
        },
        querySelector: function (selector) {
            const wanted = selector.replace('#', '');
            const stack = node.children.slice();
            while (stack.length) {
                const current = stack.shift();
                if (current.id === wanted) return current;
                stack.push.apply(stack, current.children);
            }
            return null;
        },
    };
    return node;
}

/**
 * A document stub that can create elements.
 *
 * @returns {object} Document.
 */
function documentStub() {
    return {
        createElement: function (tag) { return element(tag); },
    };
}

test('mount: the panel is centered like the tables of the game', function () {
    const doc = documentStub();
    const content = element('div', 'content');
    content.ownerDocument = doc;
    // The game's own content is already there.
    const ownTable = element('table');
    content.appendChild(ownTable);

    const host = mount.mountHost(content, 'ogi-host');
    assert.equal(host.id, 'ogi-host');
    assert.equal(content.children.length, 2, 'the wrapper was added');
    const wrapper = content.children[0];
    assert.equal(wrapper.tagName, 'CENTER', 'the wrapper is a <center>, as in the engine');
    assert.equal(wrapper.className, 'ogi-center');
    assert.equal(wrapper.children[0], host, 'the host lives inside the wrapper');
    assert.equal(content.children[1], ownTable, 'the game content follows the panel');
    assert.equal(content.firstChild, wrapper, 'the panel is the first element of the content area');
});

test('mount: mounting twice reuses the host instead of stacking panels', function () {
    const doc = documentStub();
    const content = element('div', 'content');
    content.ownerDocument = doc;
    const first = mount.mountHost(content, 'ogi-host');
    first.innerHTML = '<b>panel</b>';
    const second = mount.mountHost(content, 'ogi-host');
    assert.equal(second, first);
    assert.equal(second.innerHTML, '<b>panel</b>');
    assert.equal(content.children.length, 1, 'no second wrapper was added');
});

test('mount: first=false appends and unmount removes the wrapper', function () {
    const doc = documentStub();
    const content = element('div', 'content');
    content.ownerDocument = doc;
    const own = element('table');
    content.appendChild(own);

    const host = mount.mountHost(content, 'ogi-note', { first: false });
    assert.equal(content.children.length, 2);
    assert.equal(content.children[1].tagName, 'CENTER', 'the wrapper went after the game content');
    assert.deepEqual(content.children[1].children.map(function (child) { return child.id; }), ['ogi-note']);
    assert.equal(host.id, 'ogi-note');

    mount.unmountHost(host);
    assert.equal(content.children.length, 1, 'the wrapper is gone');
    assert.equal(content.children[0].tagName, 'TABLE', 'only the game content is left');
    assert.equal(host.parentNode, null);
});

test('mount: without a parent the host is still usable', function () {
    const previous = globalThis.document;
    globalThis.document = documentStub();
    try {
        const host = mount.mountHost(null, 'ogi-host');
        assert.equal(host.tagName, 'DIV');
        assert.equal(host.id, 'ogi-host');
        assert.doesNotThrow(function () { mount.unmountHost(host); });
        assert.doesNotThrow(function () { mount.unmountHost(null); });
    } finally {
        globalThis.document = previous;
    }
});

test('mount: the stylesheet keeps the centering and re-aligns the text', function () {
    const css = fs.readFileSync(path.resolve(__dirname, '..', 'src', 'style.css'), 'utf8');
    assert.ok(css.indexOf('.ogi-center') !== -1, 'the wrapper is styled');
    assert.ok(/\.ogi-center\s*\{[^}]*text-align:\s*center/.test(css), 'the wrapper centers its content');
    assert.ok(/\.ogi-center\s*>\s*div\s*\{[^}]*text-align:\s*left/.test(css),
        'the host resets the alignment the <center> element would pass down');
    // The widths follow the game's own tables: 519 on most pages, 525 on statistics,
    // 750 on the empire page.
    assert.ok(/#ogi-host[\s\S]{0,120}width:\s*519px/.test(css), 'the analyzer panel is 519 wide');
    assert.ok(/#ogi-stats-host\s*\{[^}]*width:\s*525px/.test(css), 'the statistics panel is 525 wide');
    assert.ok(/#ogi-empire-host\s*\{[^}]*width:\s*750px/.test(css), 'the empire panel is 750 wide');
});

test('mount: the panel shell follows the requested width', function () {
    const ui = require('../src/lib/ui.js');
    assert.ok(ui.panel('Test', 'body').indexOf('width="519"') !== -1, 'the default width');
    assert.ok(ui.panel('Test', 'body', { width: 750 }).indexOf('width="750"') !== -1);
    const empire = fs.readFileSync(path.resolve(__dirname, '..', 'src', 'pages', 'imperium.js'), 'utf8');
    const stats = fs.readFileSync(path.resolve(__dirname, '..', 'src', 'pages', 'statistics.js'), 'utf8');
    assert.ok(/width:\s*750/.test(empire), 'the empire handler asks for the wide frame');
    assert.ok(/width:\s*525/.test(stats), 'the statistics handler matches its page');
});

test('mount: every page handler uses the shared mounting helper', function () {
    const pages = fs.readdirSync(path.resolve(__dirname, '..', 'src', 'pages'));
    const offenders = [];
    for (const file of pages) {
        // bericht.js renders inside the report popup window, which has no content
        // pane of its own: its box goes directly above the report table.
        if (file === 'bericht.js') continue;
        const source = fs.readFileSync(path.resolve(__dirname, '..', 'src', 'pages', file), 'utf8');
        if (/insertBefore\(\s*host/.test(source) || /appendChild\(\s*host/.test(source)) offenders.push(file);
    }
    assert.deepEqual(offenders, [], 'page handlers must mount through OGI.mountHost');
});
