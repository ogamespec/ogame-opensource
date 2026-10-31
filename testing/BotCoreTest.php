<?php

// Tests for the bot core (game/core/bot.php) and the bot API
// (game/core/botapi.php).
//
// The tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php), so no MySQL server and no mock DB functions are
// required. The universe, players and planets come from FixtureBuilder
// (3 players, real game schema); the bot strategies are ordinary botstrat rows
// whose `source` holds the same go.GraphLinksModel JSON the admin strategy
// editor (pages_admin/admin_botedit.php) stores.
//
// A bot queue entry (type = QTYP_AI) carries the strategy id in sub_id and the
// block key in obj_id; Queue_Bot_End looks the block up in the strategy and
// hands it to ExecuteBlock, which interprets it and queues the successor
// block. ExecuteBlock reads the running bot from the globals $BotID/$BotNow.
//
// Each test method runs in a separate PHP process and starts with a fresh
// in-memory database.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class BotCoreTest extends TestCase
{
    private FixtureBuilder $fixture;
    private string $prefix = 'test_';

    protected function setUp(): void
    {
        // loca_add() resolves locale files relative to the game directory.
        chdir(__DIR__ . '/../game');

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        global $GlobalUni, $GlobalUser, $db_prefix, $from_cron, $UserCache;
        // GetUpdatePlanet / ProdResources read the universe from $GlobalUni.
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->fixture->getDbPrefix();
        $this->prefix = $db_prefix;
        // Debug() bails out on a null/empty current user, so the block trace of
        // ExecuteBlock never writes to the debug table during the tests.
        $GlobalUser = array ();
        $from_cron = false;
        $UserCache = array ();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // CreateUser() (used by AddBot) builds the skin path with hostname().
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    // Run a SELECT and return all rows as arrays.
    private function selectRows (string $sql) : array
    {
        $result = dbquery ($sql);
        $rows = array ();
        if ($result !== false) {
            while ( $row = dbarray ($result) ) $rows[] = $row;
        }
        return $rows;
    }

    // All queue tasks of a player of the given task type (AI by default).
    private function queueRows (int $ownerId, string $type = QTYP_AI) : array
    {
        return $this->selectRows ("SELECT * FROM {$this->prefix}queue WHERE type = '".$type
            ."' AND owner_id = $ownerId ORDER BY task_id ASC");
    }

    // Store a bot strategy the way the admin strategy editor does and return
    // its id. $nodes are nodeDataArray entries (key/category/text), $links are
    // linkDataArray entries (from/to/fromPort/text).
    private function addStrategy (string $name, array $nodes, array $links = array ()) : int
    {
        $source = json_encode (array (
            'class' => 'go.GraphLinksModel',
            'linkFromPortIdProperty' => 'fromPort',
            'linkToPortIdProperty' => 'toPort',
            'nodeDataArray' => $nodes,
            'linkDataArray' => $links,
        ));
        return AddDBRow (array ('name' => $name, 'source' => $source), 'botstrat');
    }

    // One linkDataArray entry, i.e. one outgoing link of a block.
    private function link (int $from, int $to, string $fromPort = '', string $text = '') : array
    {
        return array ('from' => $from, 'to' => $to, 'fromPort' => $fromPort, 'text' => $text);
    }

    // Add an AI block to the queue and return the real queue row that
    // ExecuteBlock / Queue_Bot_End work with.
    private function queueForBlock (int $playerId, int $stratId, int $blockId, int $when, int $seconds = 0) : array
    {
        $taskId = AddBotQueue ($playerId, $stratId, $blockId, $when, $seconds);
        $row = LoadQueue ($taskId);
        $this->assertIsArray ($row);
        return $row;
    }

    // The fixture fills every planet to its field limit, which blocks CanBuild
    // with BUILD_ERROR_NO_SPACE; free the fields of a planet to allow building.
    private function freeFields (int $planetId) : void
    {
        dbquery ("UPDATE {$this->prefix}planets SET fields = 0 WHERE planet_id = $planetId");
    }

    // Drop the constructions the fixture queued on a planet so the next
    // BuildEnque() starts from list_id 1 (which writes off the resources and
    // adds the Build task to the event queue).
    private function clearBuildQueue (int $planetId) : void
    {
        foreach ( $this->selectRows ("SELECT id FROM {$this->prefix}buildqueue WHERE planet_id = $planetId") as $row ) {
            dbquery ("DELETE FROM {$this->prefix}queue WHERE sub_id = ".(int)$row['id']
                ." AND (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."')");
        }
        dbquery ("DELETE FROM {$this->prefix}buildqueue WHERE planet_id = $planetId");
    }

    // Run $fn with PHP warnings/notices/deprecations suppressed. A few
    // production paths read array keys that the loaded rows do not carry (see
    // BotEnergyAbove, BotCanBuild with an unknown id); PHPUnit 10 reports those
    // as test warnings, so they are silenced while the behaviour is asserted.
    private function ignoringPhpErrors (callable $fn) : mixed
    {
        set_error_handler (function () { return true; });
        try {
            return $fn ();
        }
        finally {
            restore_error_handler ();
        }
    }

    private function planetRow (int $planetId) : ?array
    {
        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}planets WHERE planet_id = $planetId");
        return $rows ? $rows[0] : null;
    }

    private function userRow (int $playerId) : ?array
    {
        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}users WHERE player_id = $playerId");
        return $rows ? $rows[0] : null;
    }

    // ========================================================================
    // bot.php - queue plumbing
    // ========================================================================

    /**
     * AddBotQueue stores an AI task: sub_id is the strategy, obj_id the block,
     * the duration is added to the start time and the priority is the bot one.
     */
    public function testAddBotQueueStoresAiTaskWithBotPriority () : void
    {
        $taskId = AddBotQueue (1, 7, 42, 1000000, 300);

        $row = LoadQueue ($taskId);
        $this->assertIsArray ($row);
        $this->assertSame (QTYP_AI, $row['type']);
        $this->assertSame (1, (int)$row['owner_id']);
        $this->assertSame (7, (int)$row['sub_id']);
        $this->assertSame (42, (int)$row['obj_id']);
        $this->assertSame (0, (int)$row['level']);
        $this->assertSame (1000000, (int)$row['start']);
        $this->assertSame (1000300, (int)$row['end']);      // 7th AddQueue argument is a duration
        $this->assertSame (QUEUE_PRIO_BOT, (int)$row['prio']);
    }

    /**
     * Immediately executable blocks (Start/Label/Branch/Cond) use a duration of
     * zero, so they end exactly when they start.
     */
    public function testAddBotQueueWithZeroSecondsEndsImmediately () : void
    {
        $row = LoadQueue (AddBotQueue (1, 3, 1, 500, 0));

        $this->assertSame (500, (int)$row['start']);
        $this->assertSame (500, (int)$row['end']);
    }

    /**
     * IsBot is true only while the player has an AI task in the queue; other
     * task types do not make a player a bot.
     */
    public function testIsBotDetectsOnlyAiQueueTasks () : void
    {
        $this->assertFalse (IsBot (1));

        AddQueue (2, QTYP_BUILD, 0, 0, 0, 1000, 10);
        $this->assertFalse (IsBot (2));

        AddBotQueue (1, 5, 1, 1000, 0);
        $this->assertTrue (IsBot (1));
        $this->assertFalse (IsBot (2));
    }

    /**
     * StopBot deletes every AI task of the bot and leaves the other task types
     * untouched.
     */
    public function testStopBotRemovesOnlyAiTasks () : void
    {
        // QTYP_COUPON is not used by the fixture universe.
        AddQueue (1, QTYP_COUPON, 0, 0, 0, 1000, 10);       // stays
        AddBotQueue (1, 5, 1, 1000, 0);
        AddBotQueue (1, 5, 2, 1000, 0);
        $this->assertSame (2, count ($this->queueRows (1)));

        StopBot (1);

        $this->assertSame (0, count ($this->queueRows (1)));
        $this->assertSame (1, count ($this->queueRows (1, QTYP_COUPON)));
    }

    /**
     * StopBot only acts when IsBot() is true: a player without AI tasks (or
     * another player's tasks) is not touched.
     */
    public function testStopBotIgnoresPlayersWithoutAiTasks () : void
    {
        AddQueue (1, QTYP_COUPON, 0, 0, 0, 1000, 10);
        AddBotQueue (2, 5, 1, 1000, 0);

        StopBot (1);

        $this->assertSame (1, count ($this->queueRows (1, QTYP_COUPON)));
        $this->assertSame (1, count ($this->queueRows (2)));
    }

    /**
     * AddBot creates a validated account with a home planet, a password and the
     * plain-text password in the bot variables; the universe user count grows.
     */
    public function testAddBotCreatesAValidatedBotWithAPassword () : void
    {
        $this->assertTrue (AddBot ('TestBot'));

        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}users WHERE name = 'testbot'");
        $this->assertCount (1, $rows);
        $user = $rows[0];
        $playerId = (int)$user['player_id'];

        $this->assertSame (1, (int)$user['validated']);
        $this->assertSame ('', (string)$user['validatemd']);
        $this->assertSame (USER_TYPE_PLAYER, (int)$user['admin']);
        $this->assertSame ('en', (string)$user['lang']);
        $this->assertNotSame ('', (string)$user['password']);       // md5(password + secret)

        // A home planet was created and made active.
        $aktplanet = (int)$user['aktplanet'];
        $this->assertGreaterThan (0, $aktplanet);
        $this->assertSame ($aktplanet, (int)$user['hplanetid']);
        $planet = $this->planetRow ($aktplanet);
        $this->assertNotNull ($planet);
        $this->assertSame ($playerId, (int)$planet['owner_id']);

        // The plain-text password is stored as a bot variable.
        $vars = $this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = $playerId AND var = 'password'");
        $this->assertCount (1, $vars);
        $this->assertNotSame ('', (string)$vars[0]['value']);

        // The universe user count was incremented by CreateUser.
        $uni = $this->selectRows ("SELECT usercount FROM {$this->prefix}uni");
        $this->assertSame (4, (int)$uni[0]['usercount']);

        // The name is now taken (bot names are compared lower-cased).
        $this->assertTrue (IsUserExist ('TestBot'));
    }

    /**
     * AddBot refuses a name that is already taken and creates no second user.
     */
    public function testAddBotRefusesANameThatIsAlreadyTaken () : void
    {
        AddDBRow (array ('name' => 'dupebot', 'oname' => 'dupebot', 'lang' => 'en', 'validated' => 1), 'users');

        $this->assertFalse (AddBot ('dupebot'));
        $this->assertSame (1, count ($this->selectRows ("SELECT * FROM {$this->prefix}users WHERE name = 'dupebot'")));
    }

    /**
     * StartBot publishes the bot in $BotID/$BotNow and queues the Start block
     * of the "_start" strategy.
     */
    public function testStartBotQueuesTheStartStrategy () : void
    {
        $stratId = $this->addStrategy ('_start', array (
            array ('key' => 1, 'category' => 'Start', 'text' => 'start'),
            array ('key' => 2, 'category' => 'End', 'text' => 'end'),
        ), array ($this->link (1, 2)));

        $before = time ();
        StartBot (1);
        $after = time ();

        $this->assertSame (1, $GLOBALS['BotID']);
        $this->assertGreaterThanOrEqual ($before, $GLOBALS['BotNow']);
        $this->assertLessThanOrEqual ($after, $GLOBALS['BotNow']);

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame ($stratId, (int)$rows[0]['sub_id']);
        $this->assertSame (1, (int)$rows[0]['obj_id']);
        $this->assertSame ($GLOBALS['BotNow'], (int)$rows[0]['start']);
        $this->assertSame ($GLOBALS['BotNow'], (int)$rows[0]['end']);
    }

    /**
     * Without a "_start" strategy StartBot still sets $BotID but queues nothing.
     */
    public function testStartBotWithoutAStartStrategyQueuesNothing () : void
    {
        StartBot (1);

        $this->assertSame (1, $GLOBALS['BotID']);
        $this->assertSame (0, count ($this->queueRows (1)));
    }

    // ========================================================================
    // bot.php - ExecuteBlock
    // ========================================================================

    /**
     * An "End" block simply removes its own task.
     */
    public function testExecuteBlockEndJustRemovesTheTask () : void
    {
        $queue = $this->queueForBlock (1, 5, 9, 1000);
        $this->assertSame (1, count ($this->queueRows (1)));

        ExecuteBlock ($queue, array ('key' => 9, 'category' => 'End', 'text' => 'stop'), array ());

        $this->assertSame (0, count ($this->queueRows (1)));
        $this->assertFalse (LoadQueue ($queue['task_id']));
    }

    /**
     * A "Start" block queues its first outgoing link and removes its own task;
     * $BotID/$BotNow are taken from the completed task.
     */
    public function testExecuteBlockStartQueuesTheFirstChildAndRemovesTheTask () : void
    {
        $queue = $this->queueForBlock (1, 5, 1, 1000);

        ExecuteBlock ($queue, array ('key' => 1, 'category' => 'Start', 'text' => 'start'), array (
            $this->link (1, 2),
            $this->link (1, 3),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (2, (int)$rows[0]['obj_id']);
        $this->assertSame (5, (int)$rows[0]['sub_id']);
        $this->assertSame (1000, (int)$rows[0]['start']);   // $BotNow = the completed task's end
        $this->assertSame (1000, (int)$rows[0]['end']);
        $this->assertSame (QUEUE_PRIO_BOT, (int)$rows[0]['prio']);
        $this->assertFalse (LoadQueue ($queue['task_id']));

        $this->assertSame (1, $GLOBALS['BotID']);
        $this->assertSame (1000, $GLOBALS['BotNow']);
    }

    /**
     * A "Start" block without outgoing links is a dead end: it only removes
     * the task (and logs a debug message).
     */
    public function testExecuteBlockStartWithoutChildsJustRemovesTheTask () : void
    {
        $queue = $this->queueForBlock (1, 5, 1, 1000);

        ExecuteBlock ($queue, array ('key' => 1, 'category' => 'Start', 'text' => 'start'), array ());

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * A "Label" block follows the link that leaves the block from the bottom
     * port ("B"), not simply the first link.
     */
    public function testExecuteBlockLabelFollowsTheBottomPortChild () : void
    {
        $queue = $this->queueForBlock (1, 5, 1, 2000);

        ExecuteBlock ($queue, array ('key' => 1, 'category' => 'Label', 'text' => 'loop'), array (
            $this->link (1, 10, 'T'),
            $this->link (1, 11, 'B'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (11, (int)$rows[0]['obj_id']);
    }

    /**
     * Without a bottom port link the first outgoing link is used.
     */
    public function testExecuteBlockLabelWithoutBottomPortUsesTheFirstChild () : void
    {
        $queue = $this->queueForBlock (1, 5, 1, 2000);

        ExecuteBlock ($queue, array ('key' => 1, 'category' => 'Label', 'text' => 'loop'), array (
            $this->link (1, 10, 'T'),
            $this->link (1, 11, 'T'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (10, (int)$rows[0]['obj_id']);
    }

    /**
     * A "Label" block without outgoing links only removes its task.
     */
    public function testExecuteBlockLabelWithoutChildsJustRemovesTheTask () : void
    {
        $queue = $this->queueForBlock (1, 5, 1, 2000);

        ExecuteBlock ($queue, array ('key' => 1, 'category' => 'Label', 'text' => 'loop'), array ());

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * A "Branch" block jumps to the Label node of its own strategy whose text
     * matches the branch text.
     */
    public function testExecuteBlockBranchJumpsToTheLabelWithMatchingText () : void
    {
        $stratId = $this->addStrategy ('branch', array (
            array ('key' => 1, 'category' => 'Label', 'text' => 'Mine'),
            array ('key' => 2, 'category' => 'Label', 'text' => 'Other'),
        ));
        $queue = $this->queueForBlock (1, $stratId, 7, 3000);

        ExecuteBlock ($queue, array ('key' => 7, 'category' => 'Branch', 'text' => 'Mine'), array ());

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (1, (int)$rows[0]['obj_id']);
        $this->assertSame ($stratId, (int)$rows[0]['sub_id']);
        $this->assertSame (3000, (int)$rows[0]['start']);
    }

    /**
     * A branch text that matches no Label node of the strategy only removes the
     * task; nothing is queued.
     */
    public function testExecuteBlockBranchWithoutMatchingLabelJustRemovesTheTask () : void
    {
        $stratId = $this->addStrategy ('branch', array (
            array ('key' => 1, 'category' => 'Label', 'text' => 'Mine'),
        ));
        $queue = $this->queueForBlock (1, $stratId, 7, 3000);

        ExecuteBlock ($queue, array ('key' => 7, 'category' => 'Branch', 'text' => 'Missing'), array ());

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * A true condition takes the "yes" link.
     */
    public function testExecuteBlockCondTakesTheYesBranch () : void
    {
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => '1 == 1'), array (
            $this->link (3, 21, '', 'no'),
            $this->link (3, 22, '', 'yes'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (22, (int)$rows[0]['obj_id']);
    }

    /**
     * A false condition takes the "no" link.
     */
    public function testExecuteBlockCondTakesTheNoBranch () : void
    {
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => '1 == 2'), array (
            $this->link (3, 21, '', 'no'),
            $this->link (3, 22, '', 'yes'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (21, (int)$rows[0]['obj_id']);
    }

    /**
     * The condition text is evaluated in the scope of ExecuteBlock, so it can
     * call the bot API directly (here BotGetBuild on the running bot).
     */
    public function testExecuteBlockCondCanCallTheBotApi () : void
    {
        // Player 1's home planet (planet 1) has a metal mine of level 5.
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => 'BotGetBuild(GID_B_METAL_MINE) >= 5'), array (
            $this->link (3, 21, '', 'no'),
            $this->link (3, 22, '', 'yes'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (22, (int)$rows[0]['obj_id']);
    }

    /**
     * A percentage link jumps when the random roll is within the percentage.
     */
    public function testExecuteBlockCondRandomJumpWithinPercentage () : void
    {
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        mt_srand (1);      // mt_rand(1, 100) = 46 -> within 50%
        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => '1 == 1'), array (
            $this->link (3, 31, '', '50%'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (31, (int)$rows[0]['obj_id']);
    }

    /**
     * When the roll fails the condition falls back to the "no" link.
     */
    public function testExecuteBlockCondRandomJumpFallsBackToTheNoBranch () : void
    {
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        mt_srand (12345);  // mt_rand(1, 100) = 91 -> outside 50%
        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => '1 == 1'), array (
            $this->link (3, 21, '', 'no'),
            $this->link (3, 31, '', '50%'),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (21, (int)$rows[0]['obj_id']);
    }

    /**
     * When the roll fails and there is no "no" link either, no branch can be
     * chosen: the task is removed and nothing is queued.
     */
    public function testExecuteBlockCondRandomJumpWithoutANoBranchQueuesNothing () : void
    {
        $queue = $this->queueForBlock (1, 5, 3, 4000);

        mt_srand (12345);  // mt_rand(1, 100) = 91 -> outside 50%
        ExecuteBlock ($queue, array ('key' => 3, 'category' => 'Cond', 'text' => '1 == 1'), array (
            $this->link (3, 31, '', '50%'),
        ));

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * A regular block evaluates its text for a sleep duration and queues its
     * single successor with that delay.
     */
    public function testExecuteBlockDefaultQueuesTheChildWithTheEvaluatedSleep () : void
    {
        $queue = $this->queueForBlock (1, 5, 4, 5000);

        ExecuteBlock ($queue, array ('key' => 4, 'category' => 'Action', 'text' => 'return 30;'), array (
            $this->link (4, 41),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (41, (int)$rows[0]['obj_id']);
        $this->assertSame (5000, (int)$rows[0]['start']);
        $this->assertSame (5030, (int)$rows[0]['end']);
    }

    /**
     * A block text that evaluates to NULL means "no sleep": the successor is
     * queued immediately.
     */
    public function testExecuteBlockDefaultTreatsANonReturningBlockAsNoSleep () : void
    {
        $queue = $this->queueForBlock (1, 5, 4, 5000);

        ExecuteBlock ($queue, array ('key' => 4, 'category' => 'Action', 'text' => '1 + 1'), array (
            $this->link (4, 41),
        ));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (41, (int)$rows[0]['obj_id']);
        $this->assertSame (5000, (int)$rows[0]['end']);
    }

    /**
     * A regular block without outgoing links only removes its task.
     */
    public function testExecuteBlockDefaultWithoutChildsJustRemovesTheTask () : void
    {
        $queue = $this->queueForBlock (1, 5, 4, 5000);

        ExecuteBlock ($queue, array ('key' => 4, 'category' => 'Action', 'text' => 'return 30;'), array ());

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    // ========================================================================
    // bot.php - variables and task completion
    // ========================================================================

    /**
     * GetVar creates a missing variable with the default value (writing it) and
     * returns it.
     */
    public function testGetVarCreatesTheVariableWithTheDefaultValue () : void
    {
        $this->assertSame ('0', GetVar (1, 'counter', '0'));

        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 1 AND var = 'counter'");
        $this->assertCount (1, $rows);
        $this->assertSame ('0', $rows[0]['value']);
    }

    /**
     * GetVar returns the stored value and ignores the default.
     */
    public function testGetVarReturnsTheStoredValue () : void
    {
        SetVar (1, 'counter', '42');

        $this->assertSame ('42', GetVar (1, 'counter', '0'));
        $this->assertSame ('42', GetVar (1, 'counter'));
    }

    /**
     * Without a default value a new variable is stored (and returned) as NULL.
     */
    public function testGetVarWithoutDefaultStoresAndReturnsNull () : void
    {
        $this->assertNull (GetVar (1, 'empty'));

        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 1 AND var = 'empty'");
        $this->assertCount (1, $rows);
        $this->assertNull ($rows[0]['value']);
        $this->assertNull (GetVar (1, 'empty', null));
    }

    /**
     * SetVar inserts a new variable and updates an existing one in place.
     */
    public function testSetVarInsertsAndUpdates () : void
    {
        SetVar (1, 'counter', '1');
        SetVar (1, 'counter', '2');
        SetVar (1, 'other', 'x');

        $this->assertSame ('2', GetVar (1, 'counter'));
        $this->assertSame ('x', GetVar (1, 'other'));
        $this->assertSame (1, count ($this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 1 AND var = 'counter'")));
        $this->assertSame (2, count ($this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 1")));
    }

    /**
     * Bot variables are scoped per player: the same name holds a different
     * value for every bot.
     */
    public function testBotVariablesAreScopedPerPlayer () : void
    {
        SetVar (1, 'x', 'one');
        SetVar (2, 'x', 'two');

        $this->assertSame ('one', GetVar (1, 'x'));
        $this->assertSame ('two', GetVar (2, 'x'));
    }

    /**
     * SetVar's UPDATE path interpolates the value into raw SQL, so values with
     * a single quote are silently dropped. This documents the current
     * behaviour (see the bug report); the INSERT path (AddDBRow) is safe.
     */
    public function testSetVarSilentlyDropsValuesWithSingleQuotes () : void
    {
        SetVar (1, 'name', "O'Brien");                    // INSERT path: safe
        $this->assertSame ("O'Brien", GetVar (1, 'name'));

        ob_start ();                                      // the failing query echoes its error
        SetVar (1, 'name', "D'Artagnan");                 // UPDATE path: raw SQL
        ob_end_clean ();

        $this->assertSame ("O'Brien", GetVar (1, 'name'));
        $this->assertSame (1, count ($this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 1 AND var = 'name'")));
    }

    /**
     * Queue_Bot_End looks the completed block up in the strategy, collects its
     * outgoing links and lets ExecuteBlock queue the successor.
     */
    public function testQueueBotEndExecutesTheBlockOfTheCompletedTask () : void
    {
        $stratId = $this->addStrategy ('flow', array (
            array ('key' => 1, 'category' => 'Start', 'text' => 'start'),
            array ('key' => 2, 'category' => 'End', 'text' => 'end'),
        ), array ($this->link (1, 2)));

        $queue = $this->queueForBlock (1, $stratId, 1, 1000000);
        Queue_Bot_End ($queue);

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame (2, (int)$rows[0]['obj_id']);
        $this->assertSame ($stratId, (int)$rows[0]['sub_id']);
        $this->assertSame (1000000, (int)$rows[0]['start']);
    }

    /**
     * A task whose block key no longer exists in the strategy is removed.
     */
    public function testQueueBotEndRemovesTheTaskWhenTheBlockIsMissing () : void
    {
        $stratId = $this->addStrategy ('flow', array (
            array ('key' => 1, 'category' => 'Start', 'text' => 'start'),
        ));
        $queue = $this->queueForBlock (1, $stratId, 99, 1000000);

        Queue_Bot_End ($queue);

        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * A task referring to a deleted strategy is removed as well. Queue_Bot_End
     * treats the empty SELECT as a hit ($result is a result object, not false)
     * and then reads the false row, which raises PHP warnings before the
     * orphaned task is deleted; they are silenced here.
     */
    public function testQueueBotEndRemovesTheTaskWhenTheStrategyIsGone () : void
    {
        $queue = $this->queueForBlock (1, 9999, 1, 1000000);

        $this->ignoringPhpErrors (function () use ($queue) { Queue_Bot_End ($queue); });

        $this->assertSame (0, count ($this->queueRows (1)));
        $this->assertFalse (LoadQueue ($queue['task_id']));
    }

    // ========================================================================
    // botapi.php - variables and strategies
    // ========================================================================

    /**
     * BotIdle is the placeholder action of an idle bot: it does nothing.
     */
    public function testBotIdleDoesNothing () : void
    {
        $this->assertNull (BotIdle ());
        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * BotStrategyExists finds stored strategies by name and reports unknown
     * names as missing.
     */
    public function testBotStrategyExistsFindsStoredStrategies () : void
    {
        $this->addStrategy ('backup', array ());

        $this->assertTrue (BotStrategyExists ('backup'));
        $this->assertFalse (BotStrategyExists ('nope'));
    }

    /**
     * BotExec queues the Start block of the named strategy for the running bot.
     */
    public function testBotExecQueuesTheStartBlock () : void
    {
        $stratId = $this->addStrategy ('backup', array (
            array ('key' => 1, 'category' => 'Start', 'text' => 'start'),
            array ('key' => 2, 'category' => 'End', 'text' => 'end'),
        ), array ($this->link (1, 2)));

        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = 1234567;

        $this->assertTrue (BotExec ('backup'));

        $rows = $this->queueRows (1);
        $this->assertCount (1, $rows);
        $this->assertSame ($stratId, (int)$rows[0]['sub_id']);
        $this->assertSame (1, (int)$rows[0]['obj_id']);
        $this->assertSame (1234567, (int)$rows[0]['start']);
        $this->assertSame (1234567, (int)$rows[0]['end']);
        $this->assertSame (QUEUE_PRIO_BOT, (int)$rows[0]['prio']);
    }

    /**
     * A strategy without a Start block cannot be started.
     */
    public function testBotExecReturnsFalseWithoutAStartBlock () : void
    {
        $this->addStrategy ('no-start', array (
            array ('key' => 1, 'category' => 'End', 'text' => 'end'),
        ));

        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = 1234567;

        $this->assertFalse (BotExec ('no-start'));
        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * An unknown strategy name cannot be started.
     */
    public function testBotExecReturnsFalseForAnUnknownStrategy () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = 1234567;

        $this->assertFalse (BotExec ('missing'));
        $this->assertSame (0, count ($this->queueRows (1)));
    }

    /**
     * BotGetVar/BotSetVar read and write the variables of the running bot
     * ($BotID), including the create-with-default behaviour of GetVar.
     */
    public function testBotGetVarAndBotSetVarUseTheRunningBotId () : void
    {
        global $BotID, $BotNow;
        $BotID = 2;
        $BotNow = 1000;

        BotSetVar ('target', '15');
        $this->assertSame ('15', BotGetVar ('target'));

        $rows = $this->selectRows ("SELECT * FROM {$this->prefix}botvars WHERE owner_id = 2 AND var = 'target'");
        $this->assertCount (1, $rows);
        $this->assertSame ('15', $rows[0]['value']);

        // The default is returned and stored for a variable that does not exist.
        $this->assertSame ('7', BotGetVar ('missing', '7'));
        $this->assertSame ('7', BotGetVar ('missing', '1'));
        $this->assertNull (BotGetVar ('nodata'));
    }

    // ========================================================================
    // botapi.php - buildings
    // ========================================================================

    /**
     * BotCanBuild respects the building checks of CanBuild: the fixture planets
     * are full, so freeing the fields is what makes the mine buildable.
     */
    public function testBotCanBuildRequiresFreeFieldsOnTheActivePlanet () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $this->assertFalse (BotCanBuild (GID_B_METAL_MINE));

        $this->freeFields (1);

        $this->assertTrue (BotCanBuild (GID_B_METAL_MINE));
    }

    /**
     * Moon-only buildings are rejected on a planet, and an unknown object id is
     * rejected as an invalid building.
     */
    public function testBotCanBuildRejectsInvalidObjects () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();
        $this->freeFields (1);

        $this->assertTrue (BotCanBuild (GID_B_METAL_MINE));
        $this->assertFalse (BotCanBuild (GID_B_LUNAR_BASE));

        // An unknown gid has no level column on the planet row; the undefined
        // key warning is silenced, CanBuild then reports an invalid id.
        $this->assertFalse ($this->ignoringPhpErrors (function () { return BotCanBuild (999999); }));
    }

    /**
     * Without a valid user on the active planet BotCanBuild reports false.
     */
    public function testBotCanBuildReturnsFalseForAnUnknownBot () : void
    {
        global $BotID, $BotNow;
        $BotID = 9999;
        $BotNow = time ();

        $this->assertFalse (BotCanBuild (GID_B_METAL_MINE));
    }

    /**
     * BUG: BotBuild always fails. It passes the raw LoadPlanetById() row to
     * CanBuild(), and that row carries no virtual energy value
     * (GID_RC_ENERGY); TechPrice() lists every resource (with 0 for the ones a
     * building does not cost), so IsEnoughResources() runs into its "unknown
     * resource type" branch for the zero-cost energy entry and CanBuild()
     * answers "You don't have enough resources!" - even though BotCanBuild()
     * (which runs the same checks against GetUpdatePlanet()) accepts the very
     * same order. This test documents the current behaviour: the building is
     * never queued and no resources are written off.
     */
    public function testBotBuildAlwaysFailsOnItsRawPlanetRow () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();
        $this->freeFields (1);
        $this->clearBuildQueue (1);

        $this->assertTrue (BotCanBuild (GID_B_METAL_MINE));        // GetUpdatePlanet row: buildable

        $planet = LoadPlanetById (1);
        $this->assertArrayNotHasKey (GID_RC_ENERGY, $planet);      // LoadPlanetById row: no energy
        $this->assertFalse (IsEnoughResources (LoadUser (1), $planet,
            TechPrice (GID_B_METAL_MINE, (int)$planet[GID_B_METAL_MINE] + 1)));

        $this->assertSame (0, BotBuild (GID_B_METAL_MINE));
        $this->assertSame (0, count ($this->selectRows ("SELECT * FROM {$this->prefix}buildqueue WHERE planet_id = 1")));
        $this->assertSame (0, count ($this->queueRows (1, QTYP_BUILD)));

        // Nothing was written off either.
        $after = LoadPlanetById (1);
        $this->assertGreaterThanOrEqual ((float)$planet[GID_RC_METAL], (float)$after[GID_RC_METAL]);
    }

    /**
     * BotBuild returns 0 and queues nothing when the planet has no free field.
     */
    public function testBotBuildReturnsZeroWhenThePlanetIsFull () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        // The fixture planets are filled to their field limit.
        $before = count ($this->selectRows ("SELECT * FROM {$this->prefix}buildqueue"));

        $this->assertSame (0, BotBuild (GID_B_METAL_MINE));

        $this->assertSame ($before, count ($this->selectRows ("SELECT * FROM {$this->prefix}buildqueue")));
    }

    /**
     * BotBuild returns 0 when the active planet cannot be loaded.
     */
    public function testBotBuildReturnsZeroForAnUnknownBot () : void
    {
        global $BotID, $BotNow;
        $BotID = 9999;
        $BotNow = time ();

        $this->assertSame (0, BotBuild (GID_B_METAL_MINE));
    }

    /**
     * BotGetBuild returns the level of the requested building on the active
     * planet (0 for a building that was never built).
     */
    public function testBotGetBuildReturnsTheActivePlanetsLevel () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $this->assertSame (5, BotGetBuild (GID_B_METAL_MINE));
        $this->assertSame (3, BotGetBuild (GID_B_SHIPYARD));
        $this->assertSame (0, BotGetBuild (GID_B_NANITES));
    }

    /**
     * BotGetBuild returns 0 without a valid bot/planet.
     */
    public function testBotGetBuildReturnsZeroForAnUnknownBot () : void
    {
        global $BotID, $BotNow;
        $BotID = 9999;
        $BotNow = time ();

        $this->assertSame (0, BotGetBuild (GID_B_METAL_MINE));
    }

    // ========================================================================
    // botapi.php - resource settings
    // ========================================================================

    /**
     * BotResourceSettings clamps the percentages to 0..100, rounds them to
     * multiples of 10 and stores them as fractions (0..1) on the active planet.
     */
    public function testBotResourceSettingsClampsAndRoundsPercentages () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        BotResourceSettings (120, -20, 55, 44, 5, 100);

        $planet = $this->planetRow (1);
        $this->assertNotNull ($planet);
        // 120 -> 100% -> 1.0 ; -20 -> 0% -> 0.0 ; 55 -> 60% -> 0.6 ;
        // 44 -> 40% -> 0.4 ; 5 -> round(0.5)=1 -> 10% -> 0.1 ; 100 -> 1.0
        $this->assertEqualsWithDelta (1.0, (float)$planet['prod1'], 1e-9);
        $this->assertEqualsWithDelta (0.0, (float)$planet['prod2'], 1e-9);
        $this->assertEqualsWithDelta (0.6, (float)$planet['prod3'], 1e-9);
        $this->assertEqualsWithDelta (0.4, (float)$planet['prod4'], 1e-9);
        $this->assertEqualsWithDelta (0.1, (float)$planet['prod12'], 1e-9);
        $this->assertEqualsWithDelta (1.0, (float)$planet['prod212'], 1e-9);

        // The planet activity was refreshed at the supplied time.
        $this->assertSame ($BotNow, (int)$planet['lastakt']);
    }

    /**
     * All six percentage defaults are 100% (a production factor of 1.0).
     */
    public function testBotResourceSettingsDefaultsToAHundredPercent () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        BotResourceSettings ();

        $planet = $this->planetRow (1);
        foreach ( array ('prod1', 'prod2', 'prod3', 'prod4', 'prod12', 'prod212') as $column ) {
            $this->assertEqualsWithDelta (1.0, (float)$planet[$column], 1e-9, "setting $column");
        }
    }

    /**
     * Without a valid user BotResourceSettings changes nothing.
     */
    public function testBotResourceSettingsIgnoresAnUnknownBot () : void
    {
        global $BotID, $BotNow;
        $BotID = 9999;
        $BotNow = time ();

        BotResourceSettings (0, 0, 0, 0, 0, 0);

        $planet = $this->planetRow (1);
        $this->assertEqualsWithDelta (1.0, (float)$planet['prod1'], 1e-9);
    }

    /**
     * BotEnergyAbove compares the planet energy against a threshold. BUG: the
     * function reads $aktplanet['e'], a key GetUpdatePlanet never sets (it
     * publishes GID_RC_ENERGY = 703), so the comparison always runs against
     * null: any positive threshold is reported as "not reached" and 0 as
     * "reached", whatever the real energy is. This test documents that.
     */
    public function testBotEnergyAboveComparesAgainstAnUndefinedPlanetKey () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $planet = GetUpdatePlanet (1, $BotNow);
        $this->assertArrayHasKey (GID_RC_ENERGY, $planet);      // the real energy value
        $this->assertArrayNotHasKey ('e', $planet);             // what BotEnergyAbove reads

        $this->assertFalse ($this->ignoringPhpErrors (function () { return BotEnergyAbove (1); }));
        // null is compared as false, so only a threshold of exactly 0 is
        // "reached" - a negative threshold is not.
        $this->assertTrue ($this->ignoringPhpErrors (function () { return BotEnergyAbove (0); }));
        $this->assertFalse ($this->ignoringPhpErrors (function () { return BotEnergyAbove (-1); }));
    }

    // ========================================================================
    // botapi.php - fleet/defense construction
    // ========================================================================

    /**
     * BotBuildFleet places the whole shipyard order (once), returns the
     * per-unit duration and writes off the resources.
     */
    public function testBotBuildFleetOrdersShipsAndReturnsTheUnitDuration () : void
    {
        global $BotID, $BotNow, $GlobalUni;
        $BotID = 1;
        $BotNow = time ();

        $planet = LoadPlanetById (1);
        $metalBefore = (float)$planet[GID_RC_METAL];
        $expected = (int) TechDuration (GID_F_LF, 1, PROD_SHIPYARD_DURATION_FACTOR,
            (int)$planet[GID_B_SHIPYARD], (int)$planet[GID_B_NANITES], (float)$GlobalUni['speed']);

        $seconds = BotBuildFleet (GID_F_LF, 5);

        $this->assertSame ($expected, $seconds);
        $this->assertGreaterThan (0, $seconds);

        // The fixture queued one shipyard order; the fleet order is the second
        // one (AddShipyard is called once, so the order is not duplicated).
        $tasks = $this->queueRows (1, QTYP_SHIPYARD);
        $this->assertCount (2, $tasks);
        $order = $tasks[count ($tasks) - 1];
        $this->assertSame (GID_F_LF, (int)$order['obj_id']);
        $this->assertSame (5, (int)$order['level']);
        $this->assertSame (1, (int)$order['sub_id']);           // the active planet

        $after = LoadPlanetById (1);
        $this->assertLessThan ($metalBefore, (float)$after[GID_RC_METAL]);
    }

    /**
     * BotBuildFleet returns 0 without queuing anything when the requirements
     * are not met (a Deathstar needs a level 12 shipyard) or the object is not
     * a ship/defense unit.
     */
    public function testBotBuildFleetReturnsZeroWhenTheOrderIsRejected () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $this->assertSame (0, BotBuildFleet (GID_F_DEATHSTAR, 1));
        $this->assertSame (0, BotBuildFleet (999999, 1));

        // Only the fixture's own shipyard order is left.
        $this->assertSame (1, count ($this->queueRows (1, QTYP_SHIPYARD)));
    }

    /**
     * BotBuildFleet returns 0 without a valid bot.
     */
    public function testBotBuildFleetReturnsZeroForAnUnknownBot () : void
    {
        global $BotID, $BotNow;
        $BotID = 9999;
        $BotNow = time ();

        $this->assertSame (0, BotBuildFleet (GID_F_LF, 1));
    }

    // ========================================================================
    // botapi.php - research
    // ========================================================================

    /**
     * BotGetResearch returns the research levels stored on the user row, 0 for
     * an unknown bot (and for a technology that was never researched).
     */
    public function testBotGetResearchReturnsTheUsersTechnologyLevels () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $this->assertSame (5, BotGetResearch (GID_R_ESPIONAGE));
        $this->assertSame (6, BotGetResearch (GID_R_COMPUTER));
        $this->assertSame (0, BotGetResearch (GID_R_GRAVITON));

        $BotID = 9999;
        $this->assertSame (0, BotGetResearch (GID_R_ESPIONAGE));
    }

    /**
     * BotCanResearch refuses to start while another research of the player is
     * still running (the fixture has an active energy research for player 1).
     */
    public function testBotCanResearchRejectsWhileResearchIsRunning () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        $this->assertCount (1, $this->queueRows (1, QTYP_RESEARCH));
        $this->assertFalse (BotCanResearch (GID_R_ARMOUR));
    }

    /**
     * With the research queue free, BotCanResearch allows the next level of a
     * technology whose requirements and resources are met.
     */
    public function testBotCanResearchAllowsTheNextArmourLevel () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        dbquery ("DELETE FROM {$this->prefix}queue WHERE type = '".QTYP_RESEARCH."' AND owner_id = 1");

        $this->assertSame (4, BotGetResearch (GID_R_ARMOUR));   // armour tech 4 -> next level 5
        $this->assertTrue (BotCanResearch (GID_R_ARMOUR));
    }

    /**
     * BUG: BotResearch hands StartResearch a timestamp of 0. StartResearch runs
     * GetUpdatePlanet($planet, 0) before it falls back to time(), so the
     * production update is applied with a negative time delta: the planet's
     * stored resources are clamped to zero and lastpeek becomes 0, after which
     * the resource check of CanResearch fails. BotResearch therefore always
     * returns 0 instead of starting the research - and it damages the planet.
     * This test documents the current behaviour.
     */
    public function testBotResearchCannotStartAndWipesThePlanetState () : void
    {
        global $BotID, $BotNow;
        $BotID = 1;
        $BotNow = time ();

        // Free the research queue so that only the resource check can fail.
        dbquery ("DELETE FROM {$this->prefix}queue WHERE type = '".QTYP_RESEARCH."' AND owner_id = 1");

        $before = LoadPlanetById (1);
        $this->assertGreaterThan (0, (int)$before[GID_RC_METAL]);
        $this->assertGreaterThan (0, (int)$before['lastpeek']);

        $seconds = BotResearch (GID_R_ARMOUR);

        $this->assertSame (0, $seconds);
        $this->assertSame (0, count ($this->queueRows (1, QTYP_RESEARCH)));

        $after = LoadPlanetById (1);
        $this->assertSame (0, (int)$after[GID_RC_METAL]);
        $this->assertSame (0, (int)$after[GID_RC_CRYSTAL]);
        $this->assertSame (0, (int)$after['lastpeek']);

        // BotCanResearch (which is given a real timestamp) still reports the
        // research as possible - the two bot API calls contradict each other.
        $this->assertTrue (BotCanResearch (GID_R_ARMOUR));
    }
}
