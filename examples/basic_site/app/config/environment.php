<?
/* When running sites on the PHP-WAX framework you have have a separate version of the framework 
 * inside each site folder. If you've downloaded a packaged version with a 'wax' or 'phpwax' folder 
 * in your site root, then you don't need to worry about any of these options.
 */

  define('WAX_ROOT', dirname(dirname(dirname(__FILE__)))."/" );

/*
 * uncomment this line if you want an absolute path and replace the constant with the path to your
 * you wax folder - TRAILING SLASH IS REQUIRED!!!!
 */ 
  
  //define('WAX_ROOT', "/path/to/wax/folder/" );

/**
 * to change the relative path of wax folder then set this constant to the location you want
 * remember this has to be relative to the wax_root set above.. NO TRAILING SLASH
 */ 

  define('WAX_DIR', 'wax');
  if(is_dir(WAX_ROOT.WAX_DIR)) define('FRAMEWORK_DIR', WAX_ROOT.WAX_DIR);
  elseif(is_dir(WAX_ROOT."phpwax")) define('FRAMEWORK_DIR', WAX_ROOT."phpwax");
  else throw new Exception('PHP WAX folder does not exist!');
  ini_set('include_path', ini_get("include_path").":".WAX_ROOT);

  //load the framework

  require_once(FRAMEWORK_DIR."/AutoLoader.php");