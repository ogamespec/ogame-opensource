<?php

/**
 * MockDbResult wraps PDO query results for the mock DB functions.
 */
class MockDbResult
{
    public $data;
    public $rows;
    public $fetched;
    public $allRows;
    public $currentRow;

    public function __construct(array $allRows)
    {
        $this->allRows = $allRows;
        $this->rows = count($allRows);
        $this->fetched = false;
        $this->data = null;
        $this->currentRow = 0;
    }
}

/**
 * PageRenderer simulates rendering game pages for Golden Pages snapshot testing.
 * It sets up the game environment from a fixture and captures the HTML output.
 */
class PageRenderer
{
    private $gameRoot;
    private $fixture;
    private $playerIndex;
    private $queryParams;
    private $renderedHtml;
    private $currentPlanet;
    private $currentUser;
    private $currentUni;
    private $now;
    private $session;

    public function __construct(FixtureBuilder $fixture)
    {
        $this->gameRoot = dirname(__DIR__) . '/game/';
        $this->fixture = $fixture;
        $this->queryParams = [];
        $this->renderedHtml = '';
        $this->currentPlanet = null;
        $this->currentUser = null;
        $this->currentUni = null;
        $this->now = $fixture->getNow();
        $this->session = '';
        
        // Set up mock database functions
        $this->setupMockDbFunctions();
    }

    /**
     * Set up mock database functions that delegate to the fixture's PDO
     */
    private function setupMockDbFunctions(): void
    {
        // Mock dbquery - executes SQL against fixture's PDO
        if (!function_exists('mock_dbquery')) {
            function mock_dbquery(string $query, bool $mute = false) : mixed {
                global $_mockDbPDO, $_mockDbPrefix;
                
                // Handle INSERT queries - execute and return mock result
                if (stripos(trim($query), 'INSERT') === 0) {
                    try {
                        $_mockDbPDO->exec($query);
                        return new MockDbResult([]);
                    } catch (\PDOException $e) {
                        if (!$mute) echo "INSERT error: " . $e->getMessage();
                        return false;
                    }
                }
                
                // Handle UPDATE queries - execute and return mock result
                if (stripos(trim($query), 'UPDATE') === 0) {
                    try {
                        $_mockDbPDO->exec($query);
                        return new MockDbResult([]);
                    } catch (\PDOException $e) {
                        if (!$mute) echo "UPDATE error: " . $e->getMessage();
                        return false;
                    }
                }
                
                // Handle DELETE queries - execute and return mock result
                if (stripos(trim($query), 'DELETE') === 0) {
                    try {
                        $_mockDbPDO->exec($query);
                        return new MockDbResult([]);
                    } catch (\PDOException $e) {
                        if (!$mute) echo "DELETE error: " . $e->getMessage();
                        return false;
                    }
                }
                
                // Handle SELECT queries - execute and return cached result
                if (stripos(trim($query), 'SELECT') === 0) {
                    try {
                        $stmt = $_mockDbPDO->query($query);
                        $allRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                        return new MockDbResult($allRows);
                    } catch (\PDOException $e) {
                        if (!$mute) echo "SELECT error: " . $e->getMessage();
                        return false;
                    }
                }
                
                // For other queries (CREATE, ALTER, etc.)
                try {
                    $_mockDbPDO->exec($query);
                    return new MockDbResult(null, 0);
                } catch (\PDOException $e) {
                    if (!$mute) echo "Other error: " . $e->getMessage();
                    return false;
                }
            }
        }
        
        if (!function_exists('dbquery')) {
            function dbquery(string $query, bool $mute = false) : mixed {
                return mock_dbquery($query, $mute);
            }
        }
        
        if (!function_exists('mock_dbarray')) {
            function mock_dbarray(mixed $result) : mixed {
                if ($result && is_object($result) && $result instanceof MockDbResult) {
                    if ($result->currentRow < $result->rows) {
                        $result->data = $result->allRows[$result->currentRow];
                        $result->currentRow++;
                        return $result->data;
                    }
                    return false;
                }
                return false;
            }
        }
        
        if (!function_exists('dbarray')) {
            function dbarray(mixed $result) : mixed {
                return mock_dbarray($result);
            }
        }
        
        if (!function_exists('mock_dbrows')) {
            function mock_dbrows(mixed $result) : int {
                if ($result && is_object($result) && $result instanceof MockDbResult) {
                    return $result->rows;
                }
                return 0;
            }
        }
        
        if (!function_exists('dbrows')) {
            function dbrows(mixed $result) : int {
                return mock_dbrows($result);
            }
        }
        
        if (!function_exists('dbfree')) {
            function dbfree(mixed $result) : void {
                // No-op for MockDbResult
            }
        }
        
        if (!function_exists('mock_AddDBRow')) {
            function mock_AddDBRow(array $data, string $tabname) : int {
                global $_mockDbPDO, $_mockDbPrefix;
                $columns = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
                $placeholders = implode(',', array_fill(0, count($data), '?'));
                $values = array_values($data);
                $sql = "INSERT INTO `{$_mockDbPrefix}{$tabname}` ($columns) VALUES ($placeholders)";
                $_mockDbPDO->prepare($sql)->execute($values);
                return (int)$_mockDbPDO->lastInsertId();
            }
        }
        
        if (!function_exists('AddDBRow')) {
            function AddDBRow(array $data, string $tabname) : int {
                return mock_AddDBRow($data, $tabname);
            }
        }
    }

    /**
     * Set which player to render as (0-based index)
     */
    public function asPlayer(int $index): self
    {
        $this->playerIndex = $index;
        $player = $this->fixture->getPlayer($index);
        if (!$player) {
            throw new InvalidArgumentException("Player index $index not found in fixture");
        }
        $this->session = $player['session'];
        return $this;
    }

    /**
     * Set query parameters for the page (e.g., page=buildings&mode=Flotte)
     */
    public function withParams(array $params): self
    {
        $this->queryParams = $params;
        return $this;
    }

    /**
     * Render the page and return the HTML
     */
    public function render(string $page): string
    {
        // Set up super globals
        $_GET = array_merge(['page' => $page, 'session' => $this->session], $this->queryParams);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';

        // Load player data
        $player = $this->fixture->getPlayer($this->playerIndex);
        $this->loadCurrentUser($player['id']);
        $this->loadCurrentPlanet();
        $this->loadCurrentUni();

        // Change working directory to game root for relative paths (loca files, etc.)
        $originalDir = getcwd();
        chdir($this->gameRoot);

        // Capture output
        ob_start();

        try {
            $this->includePage($page);
            $output = ob_get_clean();
            
            // If output is empty, try to get any partial output
            if (empty($output)) {
                $output = ob_get_clean() ?: '';
            }
            
            $this->renderedHtml = $output;
        } catch (\Throwable $e) {
            ob_end_clean();
            chdir($originalDir);
            throw new RuntimeException("Failed to render page '$page': " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine(), 0, $e);
        }

        chdir($originalDir);
        return $this->renderedHtml;
    }

    /**
     * Get the rendered HTML
     */
    public function getHtml(): string
    {
        return $this->renderedHtml;
    }

    /**
     * Load current user from fixture database
     */
    private function loadCurrentUser(int $playerId): void
    {
        $stmt = $this->fixture->getPDO()->prepare("SELECT * FROM {$this->fixture->getDbPrefix()}users WHERE player_id = ?");
        $stmt->execute([$playerId]);
        $this->currentUser = $stmt->fetch();

        if (!$this->currentUser) {
            throw new RuntimeException("User not found: player_id=$playerId, db_prefix={$this->fixture->getDbPrefix()}");
        }

        // Set officer expiration times to far future
        if ($this->currentUser) {
            $farFuture = $this->now + 365 * 24 * 60 * 60;
            $this->currentUser['com_until'] = $farFuture;
            $this->currentUser['adm_until'] = $farFuture;
            $this->currentUser['eng_until'] = $farFuture;
            $this->currentUser['geo_until'] = $farFuture;
            $this->currentUser['tec_until'] = $farFuture;
        }
    }

    /**
     * Load current planet from fixture database
     */
    private function loadCurrentPlanet(): void
    {
        if (!$this->currentUser) return;

        $stmt = $this->fixture->getPDO()->prepare("SELECT * FROM {$this->fixture->getDbPrefix()}planets WHERE planet_id = ?");
        $stmt->execute([$this->currentUser['aktplanet']]);
        $this->currentPlanet = $stmt->fetch();
    }

    /**
     * Load universe settings
     */
    private function loadCurrentUni(): void
    {
        $this->currentUni = $this->fixture->getUniData();
    }

    /**
     * Include the page file with all necessary globals
     */
    private function includePage(string $page): void
    {
        // Set up mock DB context FIRST - before any core files are loaded
        global $_mockDbPDO, $_mockDbPrefix;
        $_mockDbPDO = $this->fixture->getPDO();
        $_mockDbPrefix = $this->fixture->getDbPrefix();
        
        global $GlobalUser, $GlobalUni, $aktplanet, $session, $now, $db_prefix, $pagetime;
        global $resourcemap, $fleetmap, $defmap, $rakmap, $buildmap, $resmap;
        global $UnitParam, $RapidFire, $transportableResources;
        global $LOCA, $loca_lang, $Languages, $DefaultLanguage, $from_reg;
        global $PageError, $PageMessage;

        // Initialize page error/message globals
        $PageError = '';
        $PageMessage = '';

        // Set globals
        $db_prefix = $this->fixture->getDbPrefix();
        $GlobalUser = $this->currentUser;
        $GlobalUni = $this->currentUni;
        $aktplanet = $this->currentPlanet;
        $session = $this->session;
        $now = $this->now;
        $pagetime = microtime(true);

        // Validate GlobalUser is set correctly
        if (!$GlobalUser || !isset($GlobalUser['aktplanet']) || !$GlobalUser['aktplanet']) {
            throw new RuntimeException("GlobalUser not set correctly. currentUser: " . json_encode($this->currentUser));
        }

        // Initialize localization globals
        $loca_lang = 'en';
        $from_reg = false;

        // Initialize resource maps that core modules depend on
        $resourcemap = [];
        $fleetmap = [];
        $defmap = [];
        $rakmap = [];
        $buildmap = [];
        $resmap = [];
        $UnitParam = [];
        $RapidFire = [];
        $transportableResources = [];
        $resourcesWithNonZeroDerivative = [];
        $query_counter = 0;

        // Load core definitions (defines constants like GID_B_METAL_MINE, Page class, etc.)
        require_once $this->gameRoot . 'core/defs.php';
        require_once $this->gameRoot . 'core/loca.php';
        require_once $this->gameRoot . 'core/page.php';
        require_once $this->gameRoot . 'core/utils.php';
        require_once $this->gameRoot . 'core/techs.php';
        require_once $this->gameRoot . 'core/prod.php';
        require_once $this->gameRoot . 'core/planet.php';
        require_once $this->gameRoot . 'core/user.php';
        require_once $this->gameRoot . 'core/uni.php';
        require_once $this->gameRoot . 'core/msg.php';
        require_once $this->gameRoot . 'core/buddy.php';
        require_once $this->gameRoot . 'core/fleet.php';
        require_once $this->gameRoot . 'core/queue.php';
        require_once $this->gameRoot . 'core/mods.php';
        require_once $this->gameRoot . 'core/debug.php';
        // Note: core.php includes db.php which defines real DB functions.
        // We skip it and rely on our mock DB functions defined in setupMockDbFunctions().
        require_once $this->gameRoot . 'core/bot.php';
        require_once $this->gameRoot . 'core/botapi.php';
        require_once $this->gameRoot . 'core/coupon.php';
        require_once $this->gameRoot . 'core/battle_engine.php';
        require_once $this->gameRoot . 'core/battle_report.php';
        require_once $this->gameRoot . 'core/battle.php';
        require_once $this->gameRoot . 'core/ally.php';
        require_once $this->gameRoot . 'core/allyranks.php';
        require_once $this->gameRoot . 'core/allyapps.php';
        require_once $this->gameRoot . 'core/acs.php';
        require_once $this->gameRoot . 'core/expedition.php';
        require_once $this->gameRoot . 'core/expedition_battle.php';
        require_once $this->gameRoot . 'core/raketen.php';
        require_once $this->gameRoot . 'core/graviton.php';
        require_once $this->gameRoot . 'core/bbcode.php';
        require_once $this->gameRoot . 'core/install_tabs.php';
        require_once $this->gameRoot . 'core/notes.php';

        // Load required locale modules for this page
        $localeModules = $this->getLocaleModules($page);
        foreach ($localeModules as $module) {
            loca_add($module, 'en');
        }

        // Load common and menu locales needed by PageHeader/PageFooter
        loca_add('common', 'en');
        loca_add('menu', 'en');

        // Load the page file
        $pageFile = $this->gameRoot . 'pages/' . $page . '.php';
        if (!file_exists($pageFile)) {
            throw new InvalidArgumentException("Page file not found: $pageFile");
        }

        // Load router to determine page configuration
        $routerFile = $this->gameRoot . 'router.json';
        $router = [];
        if (file_exists($routerFile)) {
            $router = json_decode(file_get_contents($routerFile), true) ?? [];
        }
        
        $bare = isset($router[$page]['bare']) && $router[$page]['bare'];
        $header = !isset($router[$page]['header']) || $router[$page]['header'] !== false;
        $menu = !isset($router[$page]['menu']) || $router[$page]['menu'] !== false;
        $mvc = isset($router[$page]['mvc']) && $router[$page]['mvc'];
        
        // Wrap with PageHeader/PageFooter unless bare mode
        if (!$bare) {
            PageHeader($page, !$header, $menu, '', 0);
            BeginContent();
        }

        // Include the page
        require_once $pageFile;

        // If the page defines a class that extends Page (MVC pattern), instantiate and render it
        if ($mvc) {
            $className = ucfirst($page);
            if (class_exists($className) && is_subclass_of($className, 'Page')) {
                $inst = new $className();
                $inst->controller();
                $inst->view();
            }
        }

        // Wrap with EndContent/PageFooter unless bare mode
        if (!$bare) {
            EndContent();
            PageFooter($PageMessage, $PageError, !$menu, $header ? 81 : 0, !$header);
        }
    }

    /**
     * Get the required locale modules for a page
     */
    private function getLocaleModules(string $page): array
    {
        $routerFile = $this->gameRoot . 'router.json';
        if (!file_exists($routerFile)) {
            return ['menu', 'common'];
        }
        $router = json_decode(file_get_contents($routerFile), true);
        if (!isset($router[$page]['loca'])) {
            return ['menu', 'common'];
        }
        return $router[$page]['loca'];
    }

    /**
     * Get the list of available game pages from router.json
     */
    public static function getAvailablePages(string $gameRoot = null): array
    {
        if ($gameRoot === null) {
            $gameRoot = dirname(__DIR__) . '/game/';
        }
        $routerFile = $gameRoot . 'router.json';
        if (!file_exists($routerFile)) {
            return [];
        }
        $router = json_decode(file_get_contents($routerFile), true);
        return array_keys($router);
    }
}
