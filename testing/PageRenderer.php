<?php

/**
 * PageRenderer renders real game pages for Golden Pages snapshot testing.
 *
 * It boots the game the same way game/index.php does, but against the
 * in-memory SQLite engine (DB_CONNECTION=sqlite, DB_DATABASE=:memory:) with
 * the fixture universe created by FixtureBuilder. No mock DB functions are
 * used: pages run against the real database layer (game/core/db.php).
 */
class PageRenderer
{
    private $gameRoot;
    private $fixture;
    private $playerIndex;
    private $queryParams;
    private $renderedHtml;
    private $session;

    public function __construct(FixtureBuilder $fixture)
    {
        $this->gameRoot = dirname(__DIR__) . '/game/';
        $this->fixture = $fixture;
        $this->queryParams = [];
        $this->renderedHtml = '';
        $this->session = '';
    }

    /**
     * Set which player to render as (0-based index).
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
     * Set query parameters for the page (e.g., page=buildings&mode=Flotte).
     */
    public function withParams(array $params): self
    {
        $this->queryParams = $params;
        return $this;
    }

    /**
     * Render the page and return the HTML.
     */
    public function render(string $page): string
    {
        // Re-create the request context of index.php.
        $_GET = array_merge(['page' => $page, 'session' => $this->session], $this->queryParams);
        $_REQUEST = $_GET;
        $_POST = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'GoldenPagesTest';
        $_SERVER['REQUEST_URI'] = '/game/index.php?page=' . $page . '&session=' . $this->session;

        // DB connection globals (already connected by FixtureBuilder).
        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        $db_prefix = $this->fixture->getDbPrefix();
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';

        // The real DB layer must be loaded.
        if (!function_exists('dbquery')) {
            require_once $this->gameRoot . 'core/db.php';
        }

        // Change working directory to game root BEFORE loading the game core:
        // core.php and the pages resolve relative includes (db.php, loca files,
        // router.json, ...) against the current working directory.
        $originalDir = getcwd();
        chdir($this->gameRoot);

        // Game core modules (require_once: no-op for already loaded files).
        require_once $this->gameRoot . 'core/core.php';

        // Session/language globals.
        global $GlobalUser, $GlobalUni, $aktplanet, $session, $now, $pagetime;
        global $loca_lang, $Languages, $DefaultLanguage, $UserCache, $StartPage, $from_cron;
        global $PageMessage, $PageError;

        $UserCache = array ();
        $StartPage = 'index.php';
        $from_cron = false;
        $session = $this->session;
        $now = time();
        $pagetime = microtime(true);
        $PageMessage = "";
        $PageError = "";

        ob_start();

        try {
            // index.php flow.
            $GlobalUni = LoadUniverse();
            if (!$GlobalUni) {
                throw new RuntimeException("Universe not found in {$db_prefix}uni");
            }

            loca_add("debug", $GlobalUni['lang']);

            $result = CheckParams($_REQUEST);
            if (!$result['success']) {
                throw new RuntimeException("CheckParams failed: " . implode(", ", $result['errors']));
            }

            if (AuthUser($session) == false) {
                throw new RuntimeException("AuthUser failed for session '$session'");
            }

            if ($GlobalUni['freeze'] && $GlobalUser['admin'] == 0) {
                throw new RuntimeException("Universe is frozen (freeze = {$GlobalUni['freeze']})");
            }

            loca_add("common", $GlobalUser['lang']);
            loca_add("technames", $GlobalUser['lang']);

            ModsInit();

            // Router.
            $router = LoadJsonFirst("router.json");
            ModsExecRef('route', $router);

            $pk = false;
            if (key_exists('page', $_GET)) {
                if (key_exists($_GET['page'], $router)) {
                    $pk = $_GET['page'];
                }
            }
            if ($pk === false) {
                throw new RuntimeException("Page '$page' not found in router.json");
            }

            foreach ($router[$pk]['loca'] as $i => $loca) {
                loca_add($loca, $GlobalUser['lang']);
            }

            $now = time();

            $external = false;
            if (key_exists('external', $router[$pk]) && !key_exists('session', $_GET)) {
                $external = $router[$pk]['external'];
            }

            if (!$external && key_exists('session', $_GET)) {
                if (key_exists('cp', $_GET)) SelectPlanet($GlobalUser['player_id'], intval($_GET['cp']));
                $GlobalUser['aktplanet'] = GetSelectedPlanet($GlobalUser['player_id']);

                $update_queue = true;
                if ($GlobalUser['admin'] != 0 && key_exists('admin_update_queue', $router[$pk])) {
                    $update_queue = $router[$pk]['admin_update_queue'];
                }
                if ($update_queue) {
                    UpdateQueue($now);
                }
                $aktplanet = GetUpdatePlanet($GlobalUser['aktplanet'], $now);
                if ($aktplanet == null) {
                    throw new RuntimeException("Can't get aktplanet");
                }
                $update_activity = true;
                if (key_exists('update_activity', $router[$pk])) {
                    $update_activity = $router[$pk]['update_activity'];
                }
                if ($update_activity) {
                    UpdatePlanetActivity($aktplanet['planet_id']);
                }
                UpdateLastClick($GlobalUser['player_id']);

                $header = true;
                if (key_exists('header', $router[$pk])) {
                    $header = $router[$pk]['header'];
                }
                $menu = true;
                if (key_exists('menu', $router[$pk])) {
                    $menu = $router[$pk]['menu'];
                }
            } else {
                $header = $menu = false;
            }

            $redirect_page = "";
            if (key_exists('redirect_page', $router[$pk])) {
                $redirect_page = $router[$pk]['redirect_page'];
            }
            $redirect_sec = 0;
            if (key_exists('redirect_sec', $router[$pk])) {
                $redirect_sec = $router[$pk]['redirect_sec'];
            }
            $bare = false;
            if (key_exists('bare', $router[$pk])) {
                $bare = $router[$pk]['bare'];
            }
            $mvc = false;
            if (key_exists('mvc', $router[$pk])) {
                $mvc = $router[$pk]['mvc'];
            }

            // The pages are included below in this method's scope (like
            // index.php includes them at the top level). Their top-level code
            // reads game globals ($GlobalUser, $fleetmap, $UnitParam, ...)
            // and assigns page-level globals that helper functions later read
            // via `global` (e.g. $ally in allianzen.php). Import every global
            // BY REFERENCE, so both directions work: reads see the globals,
            // and top-level assignments write through to $GLOBALS.
            //
            // Some page-level globals do not exist yet (they are created by
            // the page's own top-level code), so they cannot be imported here.
            // Pre-declare them as references so that the page assignment still
            // writes through to $GLOBALS and helper functions see the value.
            foreach (array('ally', 'SearchResults', 'FleetError', 'FleetErrorText', 'not_enough') as $pageGlobal) {
                if (!array_key_exists($pageGlobal, $GLOBALS)) {
                    $GLOBALS[$pageGlobal] = null;
                }
            }
            foreach (array_keys($GLOBALS) as $gname) {
                if ($gname === 'GLOBALS') continue;
                ${$gname} = &$GLOBALS[$gname];
            }
            unset($gname, $pageGlobal);

            if ($mvc) {
                // New-style (MVC): pages/overview.php etc. define a Page subclass.
                $classFile = $router[$pk]['path'];
                if (file_exists($classFile)) {
                    require_once $classFile;
                    $className = ucfirst($pk);
                    $inst = new $className;
                    $show = $inst->controller();

                    if ($show) {
                        PageHeader($pk, !$header, $menu, $redirect_page, $redirect_sec);
                        BeginContent();
                        $inst->view();
                        EndContent();
                        PageFooter($PageMessage, $PageError, !$menu /*popup*/, $header ? 81 : 0, !$header);
                    }
                }
            } else {
                // Old-style pages.
                if (!$bare) {
                    PageHeader($pk, !$header, $menu, $redirect_page, $redirect_sec);
                    BeginContent();
                }

                include $router[$pk]['path'];

                if (!$bare) {
                    EndContent();
                    PageFooter($PageMessage, $PageError, !$menu /*popup*/, $header ? 81 : 0, !$header);
                }
            }

            $output = ob_get_clean();
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
     * Get the rendered HTML.
     */
    public function getHtml(): string
    {
        return $this->renderedHtml;
    }

    /**
     * Get the list of available game pages from router.json.
     */
    public static function getAvailablePages(?string $gameRoot = null): array
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
