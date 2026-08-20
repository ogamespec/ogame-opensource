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

    // ========================================================================
    // Player names (oname)
    // ========================================================================

    /**
     * Give PlayerTwo a malicious display name (bypassing the registration
     * restrictions to simulate a legacy/bot-created account).
     */
    private function makePlayerTwoNameMalicious(): void
    {
        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $pdo->exec("UPDATE {$prefix}users SET oname = '<script>alert(9);</script>' WHERE player_id = 2");
    }

    /**
     * The player name is escaped everywhere it is displayed to other players:
     * statistics, search, buddy list, galaxy, alliance member list, fleet
     * list, write-message form and the overview events.
     */
    public function testPlayerNameEscapedOnDisplayPages(): void
    {
        $this->makePlayerTwoNameMalicious();

        // Statistics: PlayerTwo's row in the player table.
        $html = $this->renderPage('statistics', [], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // In-game search by player name (searches the display name; the
        // payload contains "script").
        $html = $this->renderPageWithPost('suche', [], ['type' => 'playername', 'searchtext' => 'script'], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // Buddy list: PlayerTwo is PlayerOne's accepted buddy.
        $html = $this->renderPage('buddy', [], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // Galaxy: PlayerTwo lives at 1:3:4.
        $html = $this->renderPage('galaxy', ['galaxy' => 1, 'system' => 3], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // Alliance member list: PlayerTwo is a member of the Test Alliance.
        $html = $this->renderPage('allianzen', ['a' => '4'], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // Fleet list: PlayerOne's attack fleet targets PlayerTwo's home planet.
        $html = $this->renderPage('flotten1', ['galaxy' => 1, 'system' => 3, 'planet' => 4, 'planettype' => 1, 'target_mission' => 1], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);

        // Write-message form: recipient name in the input field.
        $html = $this->renderPage('writemessages', ['messageziel' => 2], 0);
        $this->assertStringNotContainsString('<script>alert(9);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $html);
    }

    /**
     * The player name is escaped when it is embedded into in-game messages
     * (ACS invitation, buddy request, fleet transport report).
     */
    public function testPlayerNameEscapedInSentMessages(): void
    {
        $this->makePlayerTwoNameMalicious();

        // ACS invitation message: sender name goes into msgfrom and the text.
        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $pdo->exec("INSERT INTO {$prefix}union (union_id, fleet_id, target_player, name, players) VALUES (1, 2, 2, 'KV2', '1')");
        $pdo->exec("UPDATE {$prefix}fleet SET union_id = 1 WHERE fleet_id = 2");
        // AddUnionMember looks players up by the lower-cased login name and
        // reads the sender from the $GlobalUser/$GlobalUni globals.
        $pdo->exec("UPDATE {$prefix}users SET name = 'playertwo' WHERE player_id = 2");
        $GLOBALS['GlobalUser'] = LoadUser (1);
        $GLOBALS['GlobalUni'] = LoadUniverse ();

        $err = AddUnionMember (1, 'playertwo');
        $this->assertSame ('', $err);

        $stmt = $pdo->query("SELECT msgfrom, text FROM {$prefix}messages WHERE pm = " . MTYP_MISC . " ORDER BY msg_id DESC LIMIT 1");
        $rows = $stmt->fetchAll();
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $joined = $row['msgfrom'] . ' ' . $row['text'];
            $this->assertStringNotContainsString('<script>alert(9);</script>', $joined);
            $this->assertStringContainsString('&lt;script&gt;alert(9);&lt;/script&gt;', $joined);
        }
    }

    // ========================================================================
    // ACS union names
    // ========================================================================

    /**
     * The ACS union name (created from the raw union_name form field) is
     * escaped on the fleet page and in the invitation message.
     */
    public function testAcsUnionNameEscaped(): void
    {
        $pdo = $this->fixture->getPDO();
        $prefix = $this->fixture->getDbPrefix();
        $malicious = '<script>alert(5);</script>';

        // Attach a maliciously named union to PlayerOne's attack fleet
        // (fleet_id 2) so the flotten1 ACS creation form loads it.
        $pdo->exec("INSERT INTO {$prefix}union (union_id, fleet_id, target_player, name, players) VALUES (1, 2, 2, '" . $malicious . "', '1')");
        $pdo->exec("UPDATE {$prefix}fleet SET union_id = 1 WHERE fleet_id = 2");

        $html = $this->renderPageWithPost('flotten1', [], ['order_union' => '2'], 0);
        $this->assertStringNotContainsString('<script>alert(5);</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(5);&lt;/script&gt;', $html);

        // The ACS invitation message embeds the union name -- it must be
        // stored escaped.
        $pdo->exec("UPDATE {$prefix}users SET name = 'playertwo' WHERE player_id = 2");
        $GLOBALS['GlobalUser'] = LoadUser (1);
        $GLOBALS['GlobalUni'] = LoadUniverse ();
        $err = AddUnionMember (1, 'playertwo');
        $this->assertSame ('', $err);

        $stmt = $pdo->query("SELECT msgfrom, text FROM {$prefix}messages WHERE pm = " . MTYP_MISC . " ORDER BY msg_id DESC LIMIT 1");
        $rows = $stmt->fetchAll();
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $joined = $row['msgfrom'] . ' ' . $row['text'];
            $this->assertStringNotContainsString('<script>alert(5);</script>', $joined);
            $this->assertStringContainsString('&lt;script&gt;alert(5);&lt;/script&gt;', $joined);
        }
    }
}
