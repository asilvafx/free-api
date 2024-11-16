<?php

// Load core of the framework
$f3 = require_once(ROOT . 'lib/base.php');

// Load default server configuration
$f3->set('VERSION', '1.0'); // App Version
$f3->set('DEBUG', 1); // App Debug (0-Live / 1-Dev)
$f3->set('AUTOLOAD', "app/core/controllers/|app/core/models/|app/core/functions/"); //Where the framework autoloader will look for app files
$f3->set('BASE', ""); // Host base path (default: empty)
$f3->set('LOGS', "app/logs/"); // Where errors are logged
$f3->set('TEMP', "app/tmp/cache/"); // Where errors are logged
$f3->set('ONERROR', "Report->error"); // Our custom error handler, so we also get a pretty page for our users
$f3->set('UI', "ui/"); // Where the framework will look for templates and related HTML-support filess
$f3->set('UPLOADS', "public/uploads/"); // Where uploads will be saved
$f3->set('db', "sqlite"); // Database DSN (sqlite/mysql)
$f3->set('dbPath', "app/data/db/cms.db"); // Database Path
$f3->set('auto_logout', 14400); // Automatically logout after this many seconds of inactivity
$f3->set('TIMEOUT', 86400); // Define cookies timeout (in seconds, default: 24h > 86400)
$f3->set('time_format', "d M Y h:ia"  ); // How timestamps look like on the pages
$f3->set('eurocookie', FALSE); // Display eurocookie notices

// Server errors
$debug = 0;
if ($f3->get('DEBUG') > 0) {
    $debug = 1;
    error_reporting(E_ALL);
} else {
    error_reporting($debug);
}
ini_set('display_errors', $debug);
ini_set('display_startup_errors', $debug);

// Headers Config
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

ini_set('session.save_path', ROOT . 'tmp/session/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$public_path = $f3->get('SCHEME') . '://' . $f3->get('HOST') . $f3->get('BASE') . '/';

$f3->set('ROOT', ROOT);
$f3->set('PUBLIC', 'public');

// Load Site Db
$dbConn = $f3->get('db') . ':' . $f3->get('dbPath');
$db = new DB\SQL($dbConn);
if (file_exists(ROOT.'data/db/setup.sql')) {
	// Initialize database with default setup
	$db->exec(explode(';',$f3->read(ROOT.'data/db/setup.sql')));
	// Make default setup inaccessible
	rename(ROOT.'data/db/setup.sql',ROOT.'data/db/setup.$ql');
}

$siteDb = null;

$site = new DB\SQL\Mapper($db, 'site');
$site->load(array('id>? AND setup_wizzard>?', 0, 0));

if ($site->dry()) {
    $f3->set('SITE.wizzard', true);
    $f3->route('GET|POST /', 'Setup->wizzard');
    $f3->route('GET|POST /setup/@slug*', 'Setup->wizzard');
} else {
    $f3->set('SITE.wizzard', false);

    // Load site configuration
    $f3->set('SITE.name', $site->site_name); // Site Name
    $f3->set('SITE.title', $site->site_title); // Site Description
    $f3->set('SITE.description', $site->site_description); // Site Description
    $f3->set('SITE.keywords', $site->site_keywords); // Site Keywords
    $f3->set('SITE.logo', $site->site_logo); // Site Logo

    $f3->set('SITE.currencyCode', 'EUR'); // Site Currency code (ex: USD)
    $f3->set('SITE.currencySymbol', '€'); // Site Currency symbol (ex: $)

    $f3->set('SITE.smtp_host', $site->smtp_host);  // SMTP Host
    $f3->set('SITE.smtp_mail', $site->smtp_mail);  // SMTP Sender
    $f3->set('SITE.smtp_port', $site->smtp_port);  // SMTP Port (Default: 465)
    $f3->set('SITE.smtp_scheme', $site->smtp_scheme);  // SMTP Scheme: SSL/TSL
    $f3->set('SITE.smtp_user', $site->smtp_mail);  // SMTP Username
    $f3->set('SITE.smtp_pass', $site->smtp_pass);  // SMTP Password
    $f3->set('SITE.uri_backend', $site->uri_backend);  // Backend URI (path)
    $f3->set('SITE.uri_auth', $site->uri_auth);  // Backend URI (path)

    $f3->set('SITE.color_primary',  $site->color_primary); // Primary Color
    $f3->set('SITE.color_primary_text',  $site->color_primary_text); // Primary Text Color
    $f3->set('SITE.color_dark',  $site->color_dark); // Dark Theme Color
    $f3->set('SITE.color_light',  $site->color_light); // Light Theme Color
    $f3->set('SITE.color_dark_secondary',  $site->color_dark_secondary); // Dark Theme Accent Color
    $f3->set('SITE.color_light_secondary',  $site->color_light_secondary); // Light Theme Accent Color

    $baseUrl = $f3->get('SCHEME') . '://' . $f3->get('HOST');
    $f3->set('SITE.base_url', $baseUrl); // Site Base URL 
    $f3->set('API.base_url', $baseUrl . '/v1'); // Sever RestAPI Base URL 

    // Enable Frontend
    $enable_frontend = false;
    if ($site->enable_frontend > 0) {
        $enable_frontend = true;
    }
    $f3->set('SITE.enable_frontend', $enable_frontend);  // Enable Frontend (true/false) 

    // Enable API
    $enable_api = false;
    if ($site->enable_api > 0) {
        $enable_api = true;
    }
    $f3->set('SITE.enable_api', $enable_api);  // Enable `API` (true/false) 

    // Enable Register
    $enable_register = false;
    if ($site->enable_register > 0) {
        $enable_register = true;
    }
    $f3->set('SITE.enable_register', $enable_register);  // Enable `Register` (true/false) 

    // Custom server configuration
    $f3->set('VERSION', '1.0');
    $f3->set('DEBUG', 1);
    $f3->set('auto_logout', 14400);

    // Load frontend routes
    $f3->route('GET|POST|PUT /', 'Frontend->Base');
    $f3->route('GET|POST|PUT /@slug*', 'Frontend->Base');

    // Load auth routes
    $f3->route('GET /' . $f3->get('SITE.uri_auth'), 'Authenticate->Base');
    $f3->route('GET|POST /' . $f3->get('SITE.uri_auth') . '/@slug*', 'Authenticate->Base');
    $f3->route('GET /' . $f3->get('SITE.uri_auth') . '/logout', 'Authenticate->Logout');

    // Load backend routes
    $f3->route('GET /' . $f3->get('SITE.uri_backend'), 'Backend->Base');
    $f3->route('GET|POST /' . $f3->get('SITE.uri_backend') . '/@slug*', 'Backend->Base');

    // Load api routes
    $f3->route('GET|POST|DELETE /v1/@slug', 'Api->Base');
    $f3->route('GET|PUT /v1/@slug/@search/@value', 'Api->Base');

    // Load WebAuthn Routes
    $f3->route('GET|POST /web/authn/attestation/options', 'WebAuthn->Options');
    $f3->route('GET|POST /web/authn/attestation/result', 'WebAuthn->Options');
    $f3->route('GET|POST /web/authn/assertion/options', 'WebAuthn->Options');
    $f3->route('GET|POST /web/authn/assertion/result', 'WebAuthn->Options');

    $custom_db_path = ROOT . 'data/db/site.db';
    if (!file_exists($custom_db_path)) {
        new SQLite3($custom_db_path);
    }
    $dbConn = $f3->get('db') . ':' . $custom_db_path;
    $siteDb = new DB\SQL($dbConn);


// Load Integrations
  $integrationsPath = ROOT.'views/admin/integrations/';
  define('INTEGRATIONS', $integrationsPath);
  foreach(glob(INTEGRATIONS.'*', GLOB_ONLYDIR) as $folder){
    // Get the folder name by using basename()
    $folderName = basename($folder);

    // Build the full path to the file you're checking
    $loadFile = INTEGRATIONS.$folderName.'/index.php';

    if(file_exists($loadFile)){
      require_once($loadFile);
    }
  }
}
$f3->set('SITE.db', $siteDb);

$f3->route('GET /public/@slug*', 'Frontend->Public');
$f3->route('GET /assets/@slug*', 'Frontend->Assets');
$f3->route('GET /secure/dl/*', 'Frontend->SecureDl');

if (empty($f3->get('SESSION.loggedin'))) {
    $f3->set('SESSION.loggedin', false);
}

if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
    $client_ip = $_SERVER['HTTP_CLIENT_IP'];
} else {
    $client_ip = $_SERVER["REMOTE_ADDR"];
}
$f3->set('CLIENT.ip', $client_ip);

// Accepted languages
$languages = array(
    "en" => "en",
    "es" => "es",
    "fr" => "fr",
    "pt" => "pt",
    "de" => "de",
);

// Get 2 char lang code
$lang2 = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

// Set default language if a `$lang` version of site is not available
if (!in_array($lang2, array_keys($languages))) {
    $lang2 = $f3->get('SITE.lang');
}

if (is_null($f3->get('SESSION.locale')) || empty($f3->get('SESSION.locale'))) {
    // Auto site translation
    $f3->set('SESSION.locale', $languages[$lang2]);
}

$f3->set('YearNow', date("Y"));

// Load application
$f3->run();
