<?php

// Tests for two core modules of the OGame Open Source engine:
//
//  - game/core/bbcode.php: the xBB BBCode parser (the bbcode class, its tag
//    handler subclasses and the bb() helper). These are pure string
//    transformations and need no database at all; the expected output was
//    read off the real parser.
//  - game/core/msg.php: the in-game messaging module (SendMessage,
//    DeleteMessage, EnumMessages, UnreadMessages, ReportMessage,
//    BroadcastMessage, ...). These tests run against the real database layer
//    with the in-memory SQLite backend (DB_CONNECTION=sqlite,
//    DB_DATABASE=:memory:, see phpunit.xml and testing/bootstrap.php), so no
//    MySQL server and no DB mocks are needed. The schema and the seeded
//    3-player universe come from FixtureBuilder.
//
// Every test method runs in a separate PHP process, so each test starts with a
// fresh in-memory database and a fresh game core. Neither module uses
// randomness or the wall clock for the values asserted here (the only clock
// use is SendMessage's default $when, which is checked as a range).
//
// Deliberately NOT covered (unreachable in a unit test):
//  - ReportMessage() calls Error() (game/core/debug.php) and therefore exits
//    the process when the reported message is not a private message, or when
//    it belongs to another player. Those two branches cannot be asserted
//    without killing the test runner.
//  - ReportMessage()'s MSG_REPORT_DB_ERROR branch requires dbquery() to return
//    a falsey value; the SQLite backend always returns a result object, so the
//    branch is unreachable here.
//
// Two suspected production bugs found while writing these tests are pinned
// down by explicit "current behaviour" tests below (they are NOT fixed here):
//  - bb_img::get_html() ignores the width/height/border attributes (the $attr
//    string it builds is dead code);
//  - the [img=path] form documented in game/core/msg.php renders an empty URL.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class BbcodeMsgCoreTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        // loca_add() resolves the locale files relative to the game directory.
        chdir(__DIR__ . '/../game');

        // The msg.php tests need the real schema and the seeded universe
        // (otherwise the SQLite connection is never initialized); the bbcode
        // tests simply ignore it (the fixture costs ~0.1 s).
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function prefix(): string
    {
        return $this->fixture->getDbPrefix();
    }

    // Read a single message row, or null when there is none.
    private function getMessage(int $msgId): ?array
    {
        $row = dbarray(dbquery("SELECT * FROM {$this->prefix()}messages WHERE msg_id = $msgId"));
        return $row === false ? null : $row;
    }

    private function countMessages(int $ownerId): int
    {
        $row = dbarray(dbquery("SELECT COUNT(*) AS cnt FROM {$this->prefix()}messages WHERE owner_id = $ownerId"));
        return (int) $row['cnt'];
    }

    private function countMessagesWithType(int $ownerId, int $pm): int
    {
        $row = dbarray(dbquery("SELECT COUNT(*) AS cnt FROM {$this->prefix()}messages WHERE owner_id = $ownerId AND pm = $pm"));
        return (int) $row['cnt'];
    }

    private function countMessagesAtDate(int $ownerId, int $date): int
    {
        $row = dbarray(dbquery("SELECT COUNT(*) AS cnt FROM {$this->prefix()}messages WHERE owner_id = $ownerId AND date = $date"));
        return (int) $row['cnt'];
    }

    // Drain a DB result into the list of message subjects, in query order.
    private function subjects(mixed $result): array
    {
        $subjects = array();
        while (($row = dbarray($result)) !== false) {
            $subjects[] = $row['subj'];
        }
        return $subjects;
    }

    // Insert a minimal but complete user row and return the player id.
    private function addUser(int $playerId, array $overrides = array()): int
    {
        $row = array_merge(array(
            'player_id' => $playerId,
            'name' => 'Player' . $playerId,
            'oname' => 'Player' . $playerId,
            'lang' => 'en',
            'admin' => USER_TYPE_PLAYER,
            'validated' => 1,
            'score1' => 0,
            'place1' => 0,
        ), $overrides);

        return AddDBRow($row, 'users');
    }

    // ========================================================================
    // bbcode.php: the bb() helper and the plain tag handlers
    // ========================================================================

    /**
     * Text without tags, and the empty string, pass through unchanged.
     */
    public function testPlainTextIsReturnedUnchanged(): void
    {
        $this->assertSame('plain text', bb('plain text'));
        $this->assertSame('', bb(''));
        $this->assertSame('a [ b ] c', bb('a [ b ] c'));
    }

    /**
     * The simple inline tags map to their HTML counterparts.
     */
    public function testSimpleFormattingTagsBecomeHtml(): void
    {
        $this->assertSame('<strong>bold</strong>', bb('[b]bold[/b]'));
        $this->assertSame('<i>it</i>', bb('[i]it[/i]'));
        $this->assertSame('<u>un</u>', bb('[u]un[/u]'));
        $this->assertSame('<del>del</del>', bb('[s]del[/s]'));
        $this->assertSame('<sub>2</sub>', bb('[sub]2[/sub]'));
        $this->assertSame('<sup>2</sup>', bb('[sup]2[/sup]'));
    }

    /**
     * Tag names are matched case-insensitively.
     */
    public function testTagNamesAreCaseInsensitive(): void
    {
        $this->assertSame('<strong>x</strong>', bb('[B]x[/B]'));
        $this->assertSame('<i>x</i>', bb('[I]x[/i]'));
    }

    /**
     * Nested tags keep their nesting order.
     */
    public function testNestedTagsPreserveNestingOrder(): void
    {
        $this->assertSame('<strong><i>both</i></strong>', bb('[b][i]both[/i][/b]'));
        $this->assertSame('<strong>a<strong>b</strong>c</strong>', bb('[b]a[b]b[/b]c[/b]'));
    }

    /**
     * An opening tag that is never closed is closed implicitly at the end.
     */
    public function testUnclosedTagIsImplicitlyClosed(): void
    {
        $this->assertSame('<strong>oops</strong>', bb('[b]oops'));
    }

    /**
     * A closing tag for a different (open) tag stays literal text, and the
     * open tag is closed implicitly.
     */
    public function testMismatchedClosingTagStaysLiteralText(): void
    {
        $this->assertSame('<strong>a[/i]b</strong>', bb('[b]a[/i]b'));
    }

    /**
     * Tags that are not in the parser's tag map are never interpreted.
     */
    public function testUnknownTagsStayLiteralText(): void
    {
        $this->assertSame('[foo]bar[/foo]', bb('[foo]bar[/foo]'));
        $this->assertSame('[justify]x[/justify]', bb('[justify]x[/justify]'));
        $this->assertSame('[anchor=Top]x[/anchor]', bb('[anchor=Top]x[/anchor]'));
        $this->assertSame('[ b]x[/b]', bb('[ b]x[/b]'));
        $this->assertSame('[]', bb('[]'));
        $this->assertSame('[/b]text', bb('[/b]text'));
    }

    /**
     * [hr] is a self-closing element with no content.
     */
    public function testHrIsSelfClosing(): void
    {
        $this->assertSame('a<hr class="bb" />b', bb('a[hr]b'));
        $this->assertSame('<hr class="bb" />', bb('[hr /]'));
    }

    /**
     * [color] renders a font tag and escapes its attribute value.
     */
    public function testColorTagEscapesAttribute(): void
    {
        $this->assertSame('<font color="red">x</font>', bb('[color=red]x[/color]'));
        $this->assertSame('<font color="&lt;b&gt;">x</font>', bb('[color=<b>]x[/color]'));
        $this->assertSame('<font color="x&quot;y">z</font>', bb('[color=x"y]z[/color]'));
    }

    /**
     * [font] renders the face, plus the optional color and size attributes.
     */
    public function testFontTagRendersFaceColorAndSize(): void
    {
        $this->assertSame('<font face="Arial">x</font>', bb('[font=Arial]x[/font]'));
        $this->assertSame('<font face="Arial" color="red" size="4">x</font>',
            bb('[font=Arial color=red size=4]x[/font]'));
    }

    /**
     * [size] clamps to 7/-6, keeps a leading "+" and turns 0 (or a
     * non-numeric value) into the default size 3.
     */
    public function testSizeTagClampsAndNormalizes(): void
    {
        $this->assertSame('<font size="5">x</font>', bb('[size=5]x[/size]'));
        $this->assertSame('<font size="7">x</font>', bb('[size=9]x[/size]'));
        $this->assertSame('<font size="-6">x</font>', bb('[size=-9]x[/size]'));
        $this->assertSame('<font size="-1">x</font>', bb('[size=-1]x[/size]'));
        $this->assertSame('<font size="+2">x</font>', bb('[size=+2]x[/size]'));
        $this->assertSame('<font size="3">x</font>', bb('[size=0]x[/size]'));
        $this->assertSame('<font size="3">x</font>', bb('[size=abc]x[/size]'));
    }

    /**
     * [align] maps the known directions onto the HTML align attribute and
     * falls back to an empty value for anything else.
     */
    public function testAlignTag(): void
    {
        $this->assertSame('<div class="bb" align="center">x</div>', bb('[align=center]x[/align]'));
        $this->assertSame('<div class="bb" align="left">x</div>', bb('[align=left]x[/align]'));
        $this->assertSame('<div class="bb" align="right">x</div>', bb('[align=right]x[/align]'));
        $this->assertSame('<div class="bb" align="justify">x</div>', bb('[align=justify]x[/align]'));
        $this->assertSame('<div class="bb" align="">x</div>', bb('[align=bogus]x[/align]'));
    }

    /**
     * [quote] renders the frame, the escaped author (when given) and the
     * nested content.
     */
    public function testQuoteTagIncludesAuthorAndContent(): void
    {
        $html = bb('[quote=Bob]see [b]this[/b][/quote]');
        $this->assertStringContainsString('<b style="color: white;">Bob</b>', $html);
        $this->assertStringContainsString('see <strong>this</strong>', $html);

        // The author is escaped, so it cannot inject markup.
        $escaped = bb('[quote=<script>]x[/quote]');
        $this->assertStringContainsString('<b style="color: white;">&lt;script&gt;</b>', $escaped);
        $this->assertStringNotContainsString('<script>', $escaped);

        // Without an author there is no author element at all.
        $noAuthor = bb('[quote]hi[/quote]');
        $this->assertStringNotContainsString('color: white', $noAuthor);
        $this->assertStringContainsString('>hi</div>', $noAuthor);
    }

    // ========================================================================
    // bbcode.php: links, images and text escaping
    // ========================================================================

    /**
     * [url] takes its target from the attribute or from the element text, and
     * leaves absolute targets alone.
     */
    public function testUrlTagVariants(): void
    {
        $this->assertSame('<a class="bb" href="http://example.com">click</a>',
            bb('[url=http://example.com]click[/url]'));
        $this->assertSame('<a class="bb" href="http://example.com">http://example.com</a>',
            bb('[url]http://example.com[/url]'));
        $this->assertSame('<a class="bb" href="https://e.com">t</a>', bb('[url=https://e.com]t[/url]'));
        $this->assertSame('<a class="bb" href="mailto:a@b.com">m</a>', bb('[url=mailto:a@b.com]m[/url]'));
    }

    /**
     * A target without a recognised protocol prefix gets "http://" prepended;
     * the protocol-less prefixes (#, /, ?, ./, ../) are kept as-is.
     */
    public function testUrlWithoutKnownProtocolGetsHttpPrefix(): void
    {
        $this->assertSame('<a class="bb" href="http://foo/bar">foo/bar</a>', bb('[url]foo/bar[/url]'));
        $this->assertSame('<a class="bb" href="#top">t</a>', bb('[url=#top]t[/url]'));
        $this->assertSame('<a class="bb" href="/page">t</a>', bb('[url=/page]t[/url]'));
        $this->assertSame('<a class="bb" href="?page=1">t</a>', bb('[url=?page=1]t[/url]'));
        $this->assertSame('<a class="bb" href="../x">t</a>', bb('[url=../x]t[/url]'));
    }

    /**
     * [url] passes the title attribute through (escaped).
     */
    public function testUrlTagRendersTitleAttribute(): void
    {
        $this->assertSame('<a class="bb" href="http://e.com" title="Hi">x</a>',
            bb('[url=http://e.com title=Hi]x[/url]'));
    }

    /**
     * [email] uses the attribute or the element text and adds the mailto:
     * prefix only when it is missing.
     */
    public function testEmailTag(): void
    {
        $this->assertSame('<a class="bb_email" href="mailto:a@b.com">mail</a>',
            bb('[email=a@b.com]mail[/email]'));
        $this->assertSame('<a class="bb_email" href="mailto:a@b.com">a@b.com</a>',
            bb('[email]a@b.com[/email]'));
        $this->assertSame('<a class="bb_email" href="mailto:a@b.com">x</a>',
            bb('[email=mailto:a@b.com]x[/email]'));
        $this->assertSame('<a class="bb_email" href="mailto:a@b.com" title="Hi">x</a>',
            bb('[email=a@b.com title=Hi]x[/email]'));
    }

    /**
     * [img] rewrites the URL into the pic.php proxy and HTML-encodes the
     * punctuation of the URL.
     */
    public function testImgTagEncodesUrl(): void
    {
        $this->assertSame(
            '<img class="reloadimage" title="http&#58;//a&#46;com/b&#46;png" src="pic.php?url=http&#58;//a&#46;com/b&#46;png" />',
            bb('[img]http://a.com/b.png[/img]')
        );
    }

    /**
     * Whitespace inside an [img] URL is stripped, so a URL cannot break out of
     * the quoted attribute and inject another one (e.g. onerror=).
     */
    public function testImgTagStripsWhitespaceFromUrl(): void
    {
        $html = bb('[img]http://x onerror=alert(1)[/img]');

        $this->assertSame(
            '<img class="reloadimage" title="http&#58;//xonerror=alert&#40;1&#41;" src="pic.php?url=http&#58;//xonerror=alert&#40;1&#41;" />',
            $html
        );
        $this->assertStringNotContainsString(' onerror=', $html);
    }

    /**
     * CURRENT BEHAVIOUR (suspected production bug, see the report):
     * [img] parses width/height/border, but bb_img::get_html() accumulates
     * them into a local $attr string that is never used, so the attributes
     * have no effect on the rendered tag.
     */
    public function testImgTagIgnoresWidthHeightAndBorder(): void
    {
        $plain = bb('[img]http://a.com/b.png[/img]');
        $withAttributes = bb('[img width=10 height=20 border=1]http://a.com/b.png[/img]');

        $this->assertSame($plain, $withAttributes);
        $this->assertStringNotContainsString('width=', $withAttributes);
        $this->assertStringNotContainsString('height=', $withAttributes);
        $this->assertStringNotContainsString('border=', $withAttributes);
    }

    /**
     * CURRENT BEHAVIOUR (suspected production bug, see the report): the module
     * documentation in game/core/msg.php advertises the "[img=path][/img]"
     * form, but bb_img reads the URL from the element text only, so the
     * attribute form renders an empty image URL.
     */
    public function testImgTagAttributeFormRendersEmptyUrl(): void
    {
        $this->assertSame(
            '<img class="reloadimage" title="" src="pic.php?url=" />',
            bb('[img=http://a.com/b.png][/img]')
        );
    }

    /**
     * Text without tags is HTML-escaped; existing entities are escaped again
     * (the parser does not try to be entity-aware).
     */
    public function testTextIsHtmlEscaped(): void
    {
        $this->assertSame('5 &lt; 6 &amp; 7 &gt; 4', bb('5 < 6 & 7 > 4'));
        $this->assertSame('&amp;amp; &amp;#65;', bb('&amp; &#65;'));
    }

    /**
     * Newlines become <br /> and runs of two spaces become &nbsp;&nbsp;.
     */
    public function testNewlinesAndDoubleSpacesBecomeHtml(): void
    {
        $this->assertSame("l1<br />\nl2", bb("l1\nl2"));
        $this->assertSame('a&nbsp;&nbsp;b', bb('a  b'));
    }

    /**
     * With autolinks enabled, bare http/www URLs and e-mail addresses are
     * turned into links; trailing sentence punctuation stays outside the link.
     */
    public function testAutolinksDetectHttpWwwAndEmail(): void
    {
        $this->assertSame('see <a href="http://example.com" target="_blank">http://example.com</a> now',
            bb('see http://example.com now'));
        $this->assertSame('see <a href="http://www.example.com" target="_blank">www.example.com</a> now',
            bb('see www.example.com now'));
        $this->assertSame('mail me at <a href="mailto:a@b.com">a@b.com</a> now',
            bb('mail me at a@b.com now'));
        $this->assertSame('go to <a href="http://example.com" target="_blank">http://example.com</a>.',
            bb('go to http://example.com.'));
    }

    /**
     * autolinks = false suppresses link detection but keeps the escaping.
     */
    public function testAutolinksCanBeDisabled(): void
    {
        $bb = new bbcode('see http://example.com <b>');
        $bb->autolinks = false;

        $this->assertSame('see http://example.com &lt;b&gt;', $bb->get_html());
    }

    /**
     * replace_links() does the whole text pipeline in one step: escaping,
     * newline/double-space conversion and autolinking.
     */
    public function testReplaceLinksCombinesEscapingNewlinesAndAutolinks(): void
    {
        $bb = new bbcode();

        $this->assertSame(
            'a&nbsp;&nbsp;b<br />' . "\n" . '<a href="http://x.com" target="_blank">http://x.com</a>',
            $bb->replace_links("a  b\nhttp://x.com")
        );
    }

    /**
     * The mnemonics map is applied to the text of every element on output.
     * (Nothing in the game currently populates it, but it is part of the
     * public contract of the class.)
     */
    public function testMnemonicsAreReplacedInOutput(): void
    {
        $bb = new bbcode('hello {PUBLIC_SESSION} and [b]{PUBLIC_SESSION}[/b]');
        $bb->mnemonics = array('{PUBLIC_SESSION}' => 'abc123');

        $this->assertSame('hello abc123 and <strong>abc123</strong>', $bb->get_html());
    }

    // ========================================================================
    // bbcode.php: the parser internals
    // ========================================================================

    /**
     * specialchars() and unspecialchars() are exact inverses.
     */
    public function testSpecialcharsAndUnspecialcharsAreInverses(): void
    {
        $bb = new bbcode();

        $this->assertSame('a@l;b@r;c@q;d@a;e@at;f', $bb->specialchars("a[b]c\"d'e@f"));
        $this->assertSame('a[b]c"d\'e@f', $bb->unspecialchars('a@l;b@r;c@q;d@a;e@at;f'));
        $this->assertSame('[]"\'@x', $bb->unspecialchars($bb->specialchars('[]"\'@x')));
    }

    /**
     * get_tokens() splits the raw text into tagged words: 0 '[', 1 ']', 5 '/',
     * 7 word, 8 known tag name.
     */
    public function testGetTokensSplitsTagsAndText(): void
    {
        $bb = new bbcode();
        $bb->text = '[b]bold[/b]';

        $this->assertSame(array(
            array(0, '['),
            array(8, 'b'),
            array(1, ']'),
            array(7, 'bold'),
            array(0, '['),
            array(5, '/'),
            array(8, 'b'),
            array(1, ']'),
        ), $bb->get_tokens());
    }

    /**
     * parse() turns text into the flat syntax structure (open/text/close).
     */
    public function testParseReturnsFlatSyntaxStructure(): void
    {
        $bb = new bbcode();
        $syntax = $bb->parse('[b]x[/b]');

        $this->assertCount(3, $syntax);
        $this->assertSame('open', $syntax[0]['type']);
        $this->assertSame('b', $syntax[0]['name']);
        $this->assertSame('[b]', $syntax[0]['str']);
        $this->assertSame('text', $syntax[1]['type']);
        $this->assertSame('x', $syntax[1]['str']);
        $this->assertSame('close', $syntax[2]['type']);
        $this->assertSame('[/b]', $syntax[2]['str']);

        // A tag name is lower-cased and registered as an empty attribute.
        $upper = $bb->parse('[URL=http://e.com]z[/URL]');
        $this->assertSame('url', $upper[0]['name']);
        $this->assertSame(array('url' => 'http://e.com'), $upper[0]['attrib']);
    }

    /**
     * get_tree() nests the children under their opening item.
     */
    public function testGetTreeBuildsNestedItems(): void
    {
        $bb = new bbcode('[b]bold[/b] text');

        $this->assertSame(array(
            array(
                'type' => 'item',
                'name' => 'b',
                'attrib' => array('b' => ''),
                'val' => array(array('type' => 'text', 'str' => 'bold')),
            ),
            array('type' => 'text', 'str' => ' text'),
        ), $bb->tree);
    }

    /**
     * normalize_bracket() assigns nesting levels and closes open tags at the
     * end; stray closing tags degrade to text.
     */
    public function testNormalizeBracketBalancesTags(): void
    {
        $bb = new bbcode();
        $syntax = array(
            array('type' => 'text', 'str' => 'a'),
            array('type' => 'open', 'name' => 'b', 'str' => '[b]', 'attrib' => array('b' => ''), 'layout' => array()),
            array('type' => 'text', 'str' => 'c'),
            array('type' => 'close', 'name' => 'b', 'str' => '[/b]', 'layout' => array()),
        );

        $structure = $bb->normalize_bracket($syntax);

        $this->assertCount(4, $structure);
        $this->assertSame(array('text', 'open', 'text', 'close'), array_column($structure, 'type'));
        $this->assertSame(array(0, 0, 1, 0), array_column($structure, 'level'));
        $this->assertSame('c', $structure[2]['str']);

        // An unclosed [b] gets an implicit close with the same level.
        $unclosed = $bb->normalize_bracket(array(
            array('type' => 'text', 'str' => 'a'),
            array('type' => 'open', 'name' => 'b', 'str' => '[b]', 'attrib' => array('b' => ''), 'layout' => array()),
        ));
        $this->assertCount(3, $unclosed);
        $this->assertSame('close', $unclosed[2]['type']);
        $this->assertSame('b', $unclosed[2]['name']);
        $this->assertSame(0, $unclosed[2]['level']);

        // A closing tag without a matching open tag is never a tag.
        $stray = $bb->normalize_bracket(array(
            array('type' => 'text', 'str' => 'a'),
            array('type' => 'close', 'name' => 'b', 'str' => '[/b]', 'layout' => array()),
        ));
        $this->assertCount(1, $stray);
        $this->assertSame('text', $stray[0]['type']);
        $this->assertSame('a[/b]', $stray[0]['str']);
    }

    /**
     * get_syntax() rebuilds tag strings from the tree; attribute values are
     * quoted again and text is re-encoded with specialchars().
     */
    public function testGetSyntaxRebuildsTagStrings(): void
    {
        $bb = new bbcode('[url=http://x.y]z[/url]');
        $syntax = $bb->get_syntax();

        $this->assertCount(3, $syntax);
        $this->assertSame('open', $syntax[0]['type']);
        $this->assertSame('url', $syntax[0]['name']);
        $this->assertSame('[url="http://x.y"]', $syntax[0]['str']);
        $this->assertSame('z', $syntax[1]['str']);
        $this->assertSame('close', $syntax[2]['type']);
        $this->assertSame('[/url]', $syntax[2]['str']);

        // The tree can also be passed explicitly.
        $this->assertSame($syntax, $bb->get_syntax($bb->tree));
    }

    /**
     * do_bbcode() accepts both a syntax array and a tree array and yields the
     * same HTML as parsing the original text.
     */
    public function testDoBbcodeAcceptsSyntaxAndTreeArrays(): void
    {
        $source = new bbcode('[b]x[/b]');

        $fromSyntax = new bbcode();
        $fromSyntax->do_bbcode($source->get_syntax());
        $this->assertSame('<strong>x</strong>', $fromSyntax->get_html());
        $this->assertSame('[b]x[/b]', $fromSyntax->text);

        $fromTree = new bbcode();
        $fromTree->do_bbcode($source->tree);
        $this->assertSame('<strong>x</strong>', $fromTree->get_html());
    }

    /**
     * must_close_tag() consults the "ends" and "stop" lists of the handler
     * classes.
     */
    public function testMustCloseTag(): void
    {
        $bb = new bbcode();

        // bb_a::$ends contains "quote" and "align", so a link cannot contain
        // them; bb_strong does not end [i].
        $this->assertTrue($bb->must_close_tag('b', 'quote'));
        $this->assertTrue($bb->must_close_tag('url', 'align'));
        $this->assertFalse($bb->must_close_tag('quote', 'b'));
        $this->assertFalse($bb->must_close_tag('b', 'i'));
    }

    // ========================================================================
    // msg.php: sending, loading and deleting messages
    // ========================================================================

    /**
     * SendMessage() stores every field it is given and returns the new id.
     */
    public function testSendMessageStoresAllFields(): void
    {
        $id = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_PM, 1700000000, 7);

        $this->assertGreaterThan(0, $id);

        $row = $this->getMessage($id);
        $this->assertNotNull($row);
        $this->assertSame(10, (int) $row['owner_id']);
        $this->assertSame(MTYP_PM, (int) $row['pm']);
        $this->assertSame('Tester', $row['msgfrom']);
        $this->assertSame('Subject', $row['subj']);
        $this->assertSame('Body', $row['text']);
        $this->assertSame(0, (int) $row['shown']);
        $this->assertSame(1700000000, (int) $row['date']);
        $this->assertSame(7, (int) $row['planet_id']);
    }

    /**
     * A $when of 0 is replaced with the current time.
     */
    public function testSendMessageDefaultsToCurrentTime(): void
    {
        $before = time();
        $id = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_PM);
        $after = time();

        $date = (int) $this->getMessage($id)['date'];
        $this->assertGreaterThanOrEqual($before, $date);
        $this->assertLessThanOrEqual($after, $date);

        // An explicit timestamp is stored verbatim.
        $explicit = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_PM, 1234567890);
        $this->assertSame(1234567890, (int) $this->getMessage($explicit)['date']);
    }

    /**
     * Only private messages (pm = 0) are truncated to 2000 characters.
     */
    public function testSendMessageTruncatesPrivateMessagesOnly(): void
    {
        $long = str_repeat('x', 2500);

        $pmId = SendMessage(10, 'Tester', 'Subject', $long, MTYP_PM, 1000);
        $this->assertSame(2000, mb_strlen($this->getMessage($pmId)['text'], 'UTF-8'));

        $miscId = SendMessage(10, 'Tester', 'Subject', $long, MTYP_MISC, 1001);
        $this->assertSame(2500, mb_strlen($this->getMessage($miscId)['text'], 'UTF-8'));
    }

    /**
     * A player can hold at most 127 messages: the 128th send drops the oldest
     * one to make room.
     */
    public function testSendMessageKeepsMessageCountAtLimit(): void
    {
        for ($i = 0; $i < 127; $i++) {
            SendMessage(10, 'Tester', 'Subject ' . $i, 'Body', MTYP_PM, 1000 + $i);
        }
        $this->assertSame(127, $this->countMessages(10));

        $newId = SendMessage(10, 'Tester', 'Newest', 'Body', MTYP_PM, 2000);

        $this->assertSame(127, $this->countMessages(10));
        $this->assertSame(0, $this->countMessagesAtDate(10, 1000));   // the oldest was dropped
        $this->assertSame(1, $this->countMessagesAtDate(10, 2000));   // the new one is stored
        $this->assertNotNull($this->getMessage($newId));
        // Other players are not affected by the cleanup.
        $this->assertSame(0, $this->countMessages(11));
    }

    /**
     * DeleteMessage() removes only the message of the given owner.
     */
    public function testDeleteMessageOnlyRemovesOwnMessage(): void
    {
        $id = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_PM, 1000);

        DeleteMessage(11, $id);
        $this->assertSame(1, $this->countMessages(10));
        $this->assertNotNull($this->getMessage($id));

        DeleteMessage(10, $id);
        $this->assertSame(0, $this->countMessages(10));
        $this->assertNull($this->getMessage($id));
    }

    /**
     * DeleteOldestMessage() removes the row with the smallest date.
     */
    public function testDeleteOldestMessageRemovesEarliestMessage(): void
    {
        SendMessage(10, 'Tester', 'mid', 'Body', MTYP_PM, 500);
        SendMessage(10, 'Tester', 'old', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'new', 'Body', MTYP_PM, 900);

        DeleteOldestMessage(10);

        $this->assertSame(2, $this->countMessages(10));
        $this->assertSame(array('mid', 'new'),
            $this->subjects(dbquery("SELECT * FROM {$this->prefix()}messages WHERE owner_id = 10 ORDER BY date ASC")));
    }

    /**
     * DeleteAllMessages() empties one inbox and leaves the others alone.
     */
    public function testDeleteAllMessagesRemovesEveryMessageOfPlayer(): void
    {
        SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_MISC, 200);
        SendMessage(11, 'Tester', 'c', 'Body', MTYP_PM, 300);

        DeleteAllMessages(10);

        $this->assertSame(0, $this->countMessages(10));
        $this->assertSame(1, $this->countMessages(11));
    }

    /**
     * LoadMessage() returns the stored row; an unknown id yields false (the
     * docblock claims null, the SQLite backend returns the falsey dbarray()
     * result).
     */
    public function testLoadMessageReturnsRowAndFalseForUnknownId(): void
    {
        $id = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_MISC, 1000, 3);

        $row = LoadMessage($id);
        $this->assertIsArray($row);
        $this->assertSame($id, (int) $row['msg_id']);
        $this->assertSame('Subject', $row['subj']);
        $this->assertSame(3, (int) $row['planet_id']);

        $this->assertFalse(LoadMessage(999999));
    }

    // ========================================================================
    // msg.php: listing, counting and marking
    // ========================================================================

    /**
     * EnumMessages() returns the newest messages first and never returns the
     * battle report bodies (pm = MTYP_BATTLE_REPORT_TEXT).
     */
    public function testEnumMessagesExcludesBattleReportTextAndSortsNewestFirst(): void
    {
        SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_PM, 200);
        SendMessage(10, 'Tester', 'c', 'Body', MTYP_BATTLE_REPORT_TEXT, 300);
        SendMessage(10, 'Tester', 'd', 'Body', MTYP_PM, 400);

        $this->assertSame(array('d', 'b', 'a'), $this->subjects(EnumMessages(10, 100)));

        // The seeded universe contains exactly one battle report body for
        // player 1 and it is hidden from the message list.
        $this->assertSame(1, $this->countMessagesWithType(1, MTYP_BATTLE_REPORT_TEXT));
        $this->assertSame($this->countMessages(1) - 1, dbrows(EnumMessages(1, 1000)));
    }

    /**
     * EnumMessages() honours the LIMIT.
     */
    public function testEnumMessagesRespectsLimit(): void
    {
        SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_PM, 200);
        SendMessage(10, 'Tester', 'c', 'Body', MTYP_PM, 300);

        $this->assertSame(array('c', 'b'), $this->subjects(EnumMessages(10, 2)));
        $this->assertSame(array('c', 'b', 'a'), $this->subjects(EnumMessages(10, 10)));
    }

    /**
     * UnreadMessages() counts the shown = 0 rows of one player.
     */
    public function testUnreadMessagesCountsUnshownMessages(): void
    {
        $first = SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_PM, 200);

        $this->assertSame(2, UnreadMessages(10));

        MarkMessage(10, $first);

        $this->assertSame(1, UnreadMessages(10));
        $this->assertSame(0, UnreadMessages(11));
    }

    /**
     * UnreadMessages() can restrict the count to a single message type.
     */
    public function testUnreadMessagesCanFilterByType(): void
    {
        SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_MISC, 200);
        SendMessage(10, 'Tester', 'c', 'Body', MTYP_MISC, 300);

        $this->assertSame(3, UnreadMessages(10));
        $this->assertSame(2, UnreadMessages(10, true, MTYP_MISC));
        $this->assertSame(1, UnreadMessages(10, true, MTYP_PM));
        $this->assertSame(0, UnreadMessages(10, true, MTYP_ALLY));
    }

    /**
     * MarkMessage() sets shown = 1 for the owner only.
     */
    public function testMarkMessageMarksOnlyOwnMessage(): void
    {
        $id = SendMessage(10, 'Tester', 'Subject', 'Body', MTYP_PM, 1000);

        MarkMessage(11, $id);
        $this->assertSame(0, (int) $this->getMessage($id)['shown']);

        MarkMessage(10, $id);
        $this->assertSame(1, (int) $this->getMessage($id)['shown']);
    }

    /**
     * TotalMessages() counts all messages of a type, read or unread.
     */
    public function testTotalMessagesCountsByType(): void
    {
        $first = SendMessage(10, 'Tester', 'a', 'Body', MTYP_PM, 100);
        SendMessage(10, 'Tester', 'b', 'Body', MTYP_PM, 200);
        SendMessage(10, 'Tester', 'c', 'Body', MTYP_MISC, 300);
        MarkMessage(10, $first);

        $this->assertSame(2, TotalMessages(10, MTYP_PM));
        $this->assertSame(1, TotalMessages(10, MTYP_MISC));
        $this->assertSame(0, TotalMessages(10, MTYP_ALLY));
        $this->assertSame(0, TotalMessages(11, MTYP_PM));
    }

    /**
     * GetSharedSpyReport() finds the newest spy report of the given planet for
     * the player himself, and 0 when there is none.
     */
    public function testGetSharedSpyReportReturnsNewestOwnReport(): void
    {
        // The fixture grants player 1 a spy report for planet 4.
        $spyId = (int) dbarray(dbquery(
            "SELECT msg_id FROM {$this->prefix()}messages WHERE owner_id = 1 AND pm = " . MTYP_SPY_REPORT . " LIMIT 1"
        ))['msg_id'];

        $this->assertSame($spyId, GetSharedSpyReport(4, 1, 0));

        // Player 2 has no report for that planet, and nobody has one for 999.
        $this->assertSame(0, GetSharedSpyReport(4, 2, 0));
        $this->assertSame(0, GetSharedSpyReport(999, 1, 0));

        // A newer report wins (ORDER BY date DESC LIMIT 1).
        $newerId = SendMessage(1, 'Tester', 'Newer spy', 'Body', MTYP_SPY_REPORT, time(), 4);
        $this->assertSame($newerId, GetSharedSpyReport(4, 1, 0));
    }

    /**
     * With an alliance id, GetSharedSpyReport() also searches the reports of
     * the fellow members.
     */
    public function testGetSharedSpyReportFindsAllyReport(): void
    {
        $allyId = (int) dbarray(dbquery("SELECT ally_id FROM {$this->prefix()}users WHERE player_id = 1"))['ally_id'];
        $this->assertGreaterThan(0, $allyId);

        $spyId = (int) dbarray(dbquery(
            "SELECT msg_id FROM {$this->prefix()}messages WHERE owner_id = 1 AND pm = " . MTYP_SPY_REPORT . " LIMIT 1"
        ))['msg_id'];

        // Player 2 is a member of the same (fixture) alliance.
        $this->assertSame($spyId, GetSharedSpyReport(4, 2, $allyId));

        // Without the alliance (0) and with an unknown alliance there is
        // nothing to share.
        $this->assertSame(0, GetSharedSpyReport(4, 2, 0));
        $this->assertSame(0, GetSharedSpyReport(4, 2, 999999));
    }

    // ========================================================================
    // msg.php: reporting a private message
    // ========================================================================

    /**
     * ReportMessage() copies the reported private message into the reports
     * table and reports success through the output parameters.
     */
    public function testReportMessageCreatesReportAndSetsResultMessage(): void
    {
        loca_add('messages', 'en');

        $msgId = SendMessage(10, 'Sender', 'Subject', 'Body', MTYP_PM, 1700000000);
        $result = '';
        $error = '';

        $reportId = ReportMessage(10, $msgId, $result, $error);

        $this->assertGreaterThan(0, $reportId);
        $this->assertSame('Report sent!', $result);
        $this->assertSame('', $error);

        $report = dbarray(dbquery("SELECT * FROM {$this->prefix()}reports WHERE msg_id = $msgId"));
        $this->assertIsArray($report);
        $this->assertSame(10, (int) $report['owner_id']);
        $this->assertSame('Sender', $report['msgfrom']);
        $this->assertSame('Subject', $report['subj']);
        $this->assertSame('Body', $report['text']);
        $this->assertSame(1700000000, (int) $report['date']);
    }

    /**
     * The same message cannot be reported twice: the second call reports the
     * duplicate through $ResultError and returns 0.
     */
    public function testReportMessageRejectsDuplicateReport(): void
    {
        loca_add('messages', 'en');

        $msgId = SendMessage(10, 'Sender', 'Subject', 'Body', MTYP_PM, 1700000000);
        $first = ReportMessage(10, $msgId);

        $result = '';
        $error = '';
        $second = ReportMessage(10, $msgId, $result, $error);

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
        $this->assertSame('', $result);
        $this->assertSame('The report has already been sent earlier!', $error);
        $this->assertSame(1, dbrows(dbquery("SELECT * FROM {$this->prefix()}reports WHERE msg_id = $msgId")));
    }

    /**
     * An unknown message id is silently ignored (no report, no message).
     */
    public function testReportMessageIgnoresUnknownMessageId(): void
    {
        $result = 'untouched';
        $error = 'untouched';

        $reportId = ReportMessage(10, 999999, $result, $error);

        $this->assertSame(0, $reportId);
        $this->assertSame('untouched', $result);
        $this->assertSame('untouched', $error);
        $this->assertSame(0, dbrows(dbquery("SELECT * FROM {$this->prefix()}reports")));
    }

    // ========================================================================
    // msg.php: broadcast and expiry
    // ========================================================================

    /**
     * BroadcastMessage() selects the recipients per category: 1 = newbies
     * (score1 < USER_NOOB_LIMIT), 2 = top 100 (place1 < 100), 3 = operators
     * (admin = 1), anything else = everyone. It returns the number of users
     * the message was sent to.
     */
    public function testBroadcastMessageTargetsCategories(): void
    {
        // Fixture players 1-3: score1 50000/45000/40000, place1 1/2/3, admin 0.
        $this->addUser(30, array('name' => 'Noob', 'score1' => 100, 'place1' => 900));
        $this->addUser(31, array('name' => 'Operator', 'score1' => 90000, 'place1' => 5, 'admin' => USER_TYPE_GO));

        // Category 1: only the newbie.
        $this->assertSame(1, BroadcastMessage(1, 'Admin', 'News', 'Body'));
        $this->assertSame(1, TotalMessages(30, MTYP_MISC));
        $this->assertSame(0, TotalMessages(31, MTYP_MISC));

        // Category 3: only the operator.
        $this->assertSame(1, BroadcastMessage(3, 'Admin', 'News', 'Body'));
        $this->assertSame(1, TotalMessages(31, MTYP_MISC));

        // Category 2: players 1-3 plus the operator, but not the newbie.
        $this->assertSame(4, BroadcastMessage(2, 'Admin', 'News', 'Body'));

        // Anything else: everyone.
        $this->assertSame(5, BroadcastMessage(0, 'Admin', 'News', 'Body'));

        // Totals after all four broadcasts.
        $this->assertSame(2, TotalMessages(30, MTYP_MISC));   // categories 1 + 0
        $this->assertSame(3, TotalMessages(31, MTYP_MISC));   // categories 2 + 3 + 0
        $this->assertSame(3, TotalMessages(1, MTYP_MISC));    // categories 2 + 0 + the fixture welcome
    }

    /**
     * DeleteExpiredMessages() drops the messages older than the given number
     * of days and keeps the newer ones.
     */
    public function testDeleteExpiredMessagesDeletesOnlyOldMessages(): void
    {
        SendMessage(10, 'Tester', 'old', 'Body', MTYP_PM, time() - 10 * 86400);
        SendMessage(10, 'Tester', 'recent', 'Body', MTYP_PM, time() - 2 * 86400);

        DeleteExpiredMessages(10, 7);

        $this->assertSame(1, $this->countMessages(10));
        $this->assertSame(array('recent'),
            $this->subjects(dbquery("SELECT * FROM {$this->prefix()}messages WHERE owner_id = 10")));
    }

    /**
     * DeleteExpiredMessages() never touches the messages of an operator or an
     * administrator.
     */
    public function testDeleteExpiredMessagesSkipsAdministrators(): void
    {
        dbquery("UPDATE {$this->prefix()}users SET admin = " . USER_TYPE_ADMIN . " WHERE player_id = 3");

        SendMessage(3, 'Tester', 'old', 'Body', MTYP_PM, time() - 10 * 86400);

        DeleteExpiredMessages(3, 7);

        $this->assertSame(1, $this->countMessages(3));

        // The same message for a regular player would have been deleted.
        SendMessage(10, 'Tester', 'old', 'Body', MTYP_PM, time() - 10 * 86400);
        DeleteExpiredMessages(10, 7);
        $this->assertSame(0, $this->countMessages(10));
    }
}
