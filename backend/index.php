<?php  

// Define BASE path 
define('BASE_PATH', __DIR__);  

// Retrieve instance of ROOT path
$root = realpath("../").'app/'; 

// Define ROOT path
if($root && !is_dir($root)): $root = realpath(".").'/app/'; endif;
define('ROOT', $root);  

// Validate ROOT path
if(!ROOT): echo "Missing root path. Check your app and try again.";
exit; endif;

// Composer autoloader
$composer_dir = ROOT.'vendor/autoload.php';
if(file_exists($composer_dir)){  
    require_once($composer_dir);
} 

// App framework autoloader
require_once(ROOT.'autoload.php');