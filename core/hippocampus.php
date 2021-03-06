<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/db.php');
require_once(__DIR__ . '/user.php');
require_once(__DIR__ . '/userManager.php');
require_once(__DIR__ . '/themeManager.php');
require_once(__DIR__ . '/moduleManager.php');
require_once(__DIR__ . '/theme.php');
require_once(__DIR__ . '/utils.php');

class Hippocampus
{
    private $db;
    private $themeManager;
    private $userManager;
    private $moduleManager;

    public function __construct()
    {
        global $_CONFIG;
        session_start();
        $this->db = new Database($this, $_CONFIG['db']['database'], $_CONFIG['db']['username'], $_CONFIG['db']['password'], $_CONFIG['db']['host']);
        $this->themeManager   = new ThemeManager($this);
        $this->userManager    = new UserManager($this);
        $this->moduleManager  = new ModuleManager($this);
    }

    public function run()
    {
        global $hc;
        $this->themeManager->loadAllThemes();

        $page = '';
        if (!empty($_GET['p'])) {
          $page = $_GET['p'];
        }
        if (empty($page) || strlen($page) === 0) $page = '/';
        if ($page[0] !== '/') $page = '/'.$page;

        $aliases = [
          '/' => '/home',
          '/index.php' => '/home'
        ];
        if (!empty($aliases[$page])) $page = $aliases[$page];


        $u = $this->userManager->getLoggedInUser();

        if ($this->getDB()->getConfigValue("site.maintenance")) {
          header("HTTP/1.1 503 Service Temporarily Unavailable");
          header("Status: 503 Service Temporarily Unavailable");
          header("Retry-After: 3600");
          echo '
          <html>
          <head>
            <title>Site upgrade in progress</title>
          </head>
          <body>
            <h1>Maintenance Mode</h1>
            <p>We are currently undergoing scheduled maintenance.<br />
            Please try back <strong>in 60 minutes</strong>.</p>
            <p>Sorry for the inconvenience.</p>
          </body>
          </html>';
          exit();
        }

        switch($page) {
          case '/register':
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('register'));
            break;
          case '/who':
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('who'));
            break;
          case '/doc':
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('doc'));
            break;
          case '/admin':
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('admin'));
            break;
          case '/style.css':
            header("Content-type: text/css");
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('style'));
            break;
          case '/scripts.js':
            header('Content-Type: application/javascript');
            require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('javascript'));
            break;
          case '/home':
            if ($u) {
                require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('userview'));
            } else {
                require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('index'));
            }
            break;
          case '/logout':
            $this->userManager->logOutUser();
            header('Location: home');
            echo 'Logged out! <a href="home">Home</a>';
            break;
          case '/window':
            if ($u) {
                require(__DIR__ . '/../themes/'.$this->themeManager->getFeaturePath('window'));
            } else {
                echo '404! Window.';
            }
            break;
          default:
            header("HTTP/1.0 404 Not Found");
            echo "404 Not Found\n";
            print_r($_GET);
            break;
        }
    }

    public function getMetacode() {
      $metacodeArr = $this->themeManager->getMetacode();
      $this->moduleManager->onCreatingMetacode($metacodeArr);
      return implode("\n  ", $metacodeArr);
    }

    public function getDB()
    {
        return $this->db;
    }

    public function getUserManager()
    {
        return $this->userManager;
    }

    public function getModuleManager()
    {
        return $this->moduleManager;
    }

    public function getNotifications() {
        $notifications = [];
        $this->getModuleManager()->onCreatingNotifications($notifications);
        return $notifications;
    }

    public function getSidebarTabs() {
      $sidebarTabs = [];
      /*
      $sidebarTabs = [
        3 => [
          'icon' => 'gmail',
          'text' => 'Gmail',
          'id' => 'gmail',
        ],
        4 => [
          'icon' => 'drive',
          'text' => 'Drive',
          'id' => 'drive',
        ],
        5 => [
          'icon' => 'calendar',
          'text' => 'Calendar',
          'id' => 'calendar',
        ],
        6 => [
          'icon' => 'classroom',
          'text' => 'Classroom',
          'id' => 'classroom',
        ],
        9 => [
          'icon' => 'facebook',
          'text' => 'Facebook',
          'id' => 'facebook',
        ],
        11 => [
          'icon' => 'chat',
          'text' => 'Mensajes',
          'id' => 'chat',
        ],
        100 => [
          'icon' => 'about',
          'text' => 'Ayuda',
          'id' => 'about',
        ],
      ];
      */
      $this->moduleManager->onCreatingSidebar($sidebarTabs);
      //ksort($sidebarTabs, SORT_NUMERIC);
      return $sidebarTabs;
    }
}
