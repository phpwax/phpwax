<?php
namespace Wax;
use Wax\Model\Model;
use Wax\Config\Config;

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE ^ E_DEPRECATED);
define("CLI_ENV", true);
define("ENV", "test");
define("FRAMEWORK_DIR", dirname(dirname(__FILE__)));
require_once(FRAMEWORK_DIR."/Core/Loader.php");
require_once(FRAMEWORK_DIR."/AutoLoader.php");

Config::load(FRAMEWORK_DIR."/tests/db.ini");
Model::$db_settings = Config::get("test/db");
