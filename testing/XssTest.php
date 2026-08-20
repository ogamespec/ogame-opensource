<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * XSS regression tests for issue #165 ("XSS in alliance name").
 *
 * These tests inject malicious alliance names/tags and note subjects/texts
 * into the fixture database and render the affected game pages, asserting
 * that the output HTML escapes the payload (htmlspecialchars) instead of
 * emitting it as raw markup.
 *
 * Each test runs in a separate process (like GoldenPagesTest/NotesTest) with
 * a fresh in-memory SQLite database created by FixtureBuilder.
 */
#[RunTestsInSeparateProcesses]
class XssTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        $this->fixture = new FixtureBuilder();
        $this->fixture->createTestUniverse();
    }

    /**
     * Inject a malicious name/tag into the test alliance (ally_id = 1).
     */
    private function makeAllianceMalicious(): void
    {
        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $payload = '<script>alert(123);</script>';
        $homepage = 'https://example.com/" onmouseover="alert(456)';
        $pdo->exec(
            "UPDATE {$prefix}ally SET " .
            "tag = '" . $payload . "', " .
            "name = '" . $payload . "', " .
            "homepage = '" . $homepage . "', " .
            "old_tag = '" . $payload . "', " .
            "old_name = '" . $payload . "', " .
            "tag_until = " . (time() + 3600) . ", " .
            "name_until = " . (time() + 3600) . " " .
            "WHERE ally_id = 1"
        );
    }

    /**
     * Add a note with a malicious subject and text for a player (bypassing
     * AddNote, like FixtureBuilder does) and return its note_id.
     */
    private function addMaliciousNote(int $playerId): int
    {
        return AddDBRow (array (
            'owner_id' => $playerId,
            'subj' => '<script>alert(777);</script>',
            'text' => '</textarea><script>alert(888);</script>',
            'textsize' => 10,
            'prio' => 0,
            'date' => time(),
        ), "notes");
    }

    private function renderPage(string $page, array $params = [], int $playerIndex = 0): string
    {
        $renderer = new PageRenderer($this->fixture);
        $renderer->asPlayer($playerIndex);
        if ($params) {
            $renderer->withParams($params);
        }
        return $renderer->render($page);
    }

    private function renderPageWithPost(string $page, array $params, array $post, int $playerIndex = 0): string
    {
        $renderer = new PageRenderer($this->fixture);
        $renderer->asPlayer($playerIndex);
        if ($params) {
            $renderer->withParams($params);
        }
        $renderer->withPost($post);
        return $renderer->render($page);
    }

    // ========================================================================
    // Alliance name / tag
    // ========================================================================

    /**
     * The alliance home page (allianzen) escapes the alliance name, tag and
     * their "previous" values (issue #165).
     */
    public function testAllianzenHomeEscapesAllianceNameAndTag(): void
    {
        $this->makeAllianceMalicious();
        $html = $this->renderPage('allianzen', [], 0);

        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
        // The "previous tag/name" block (ALLY_MAIN_PREV) is shown because
        // tag_until/name_until are in the future.
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
    }

    /**
     * The change-name and change-tag forms (allianzen a=10 / a=9) escape the
     * current name/tag in the confirmation and error texts.
     */
    public function testAllianzenChangeFormsEscapeAllianceValues(): void
    {
        $this->makeAllianceMalicious();

        $html = $this->renderPage('allianzen', ['a' => '10'], 0);   // change name form
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);

        $html = $this->renderPage('allianzen', ['a' => '9'], 0);    // change tag form
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
    }

    /**
     * The alliance info page (ainfo) escapes tag, name and homepage.
     */
    public function testAinfoEscapesAllianceNameAndTag(): void
    {
        $this->makeAllianceMalicious();
        $html = $this->renderPage('ainfo', ['allyid' => 1], 0);

        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
        // Homepage quote-breakout payload is neutralized.
        $this->assertStringNotContainsString('" onmouseover="alert(456)', $html);
        $this->assertStringContainsString('&quot; onmouseover=&quot;alert(456)', $html);
    }

    /**
     * The statistics page escapes the alliance tag in the player and the
     * alliance tables.
     */
    public function testStatisticsEscapesAllianceTag(): void
    {
        $this->makeAllianceMalicious();

        $html = $this->renderPage('statistics', [], 0);            // player table (own row)
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);

        $html = $this->renderPage('statistics', ['who' => 'ally'], 0);  // alliance table
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
    }

    /**
     * The alliance search results (allianzen a=2 and suche) escape the tag
     * and the name (issue #165).
     */
    public function testAllianceSearchEscapesTagAndName(): void
    {
        $this->makeAllianceMalicious();

        // The allianzen a=2 search only runs for players without an alliance,
        // so remove PlayerOne from the alliance first.
        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $pdo->exec("UPDATE {$prefix}users SET ally_id = 0, allyrank = 0 WHERE player_id = 1");

        $html = $this->renderPageWithPost('allianzen', ['a' => '2'], ['suchtext' => 'script'], 0);
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);

        $html = $this->renderPageWithPost('suche', [], ['type' => 'allyname', 'searchtext' => 'script'], 0);
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
    }

    /**
     * The apply (bewerben) and applications (bewerbungen) pages escape the
     * alliance tag.
     */
    public function testApplyPagesEscapeAllianceTag(): void
    {
        $this->makeAllianceMalicious();

        $html = $this->renderPage('bewerben', ['allyid' => 1], 0);
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);

        $html = $this->renderPage('bewerbungen', [], 0);
        $this->assertStringNotContainsString('<script>alert(123);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $html);
    }

    /**
     * Messages that embed the alliance tag/name (dismiss flow) store an
     * escaped value, so the inbox never renders raw markup (issue #165).
     */
    public function testAllianceMessagesStoreEscapedValues(): void
    {
        $this->makeAllianceMalicious();

        // The dismiss flow (a=12) sends a message to every member with
        // $from = alliance name and the tag in subject/text. All three
        // fixture players are members, so exactly 3 new messages are created
        // on top of the fixture messages.
        $this->renderPageWithPost('allianzen', ['a' => '12', 'weiter' => '1'], ['sure' => '1'], 0);

        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $stmt = $pdo->query("SELECT * FROM {$prefix}messages ORDER BY msg_id DESC LIMIT 3");
        $rows = $stmt->fetchAll();
        $this->assertCount(3, $rows, 'Dismiss messages must have been sent to all 3 members');

        foreach ($rows as $row) {
            $joined = $row['msgfrom'] . ' ' . $row['subj'] . ' ' . $row['text'];
            $this->assertStringNotContainsString('<script>alert(123);</script>', $joined);
            $this->assertStringContainsString('&lt;script&gt;alert(123);&lt;/script&gt;', $joined);
        }
    }

    // ========================================================================
    // Notes
    // ========================================================================

    /**
     * The notes list and the note edit form escape the subject and the text
     * (issue #165: "Аналогично для заметок").
     */
    public function testNotesEscapedOnListAndEditForm(): void
    {
        $noteId = $this->addMaliciousNote(1);

        // Notes list (subject column).
        $html = $this->renderPage('notizen', [], 0);
        $this->assertStringNotContainsString('<script>alert(777);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(777);&lt;/script&gt;', $html);

        // Edit form (subject input + text textarea; the text payload breaks
        // out of the <textarea> only when unescaped).
        $html = $this->renderPage('notizen', ['a' => '2', 'n' => (string)$noteId], 0);
        $this->assertStringNotContainsString('<script>alert(777);</script>', $html);
        $this->assertStringNotContainsString('</textarea><script>alert(888);</script>', $html);
        $this->assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;alert(888);&lt;/script&gt;', $html);
    }
}
