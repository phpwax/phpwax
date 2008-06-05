<?php

/**
 * uses PackageFileManager
 */
require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/PackageFileManager/Svn.php';

/**
 * Base version
 */
$baseVersion = '0.8';

/**
 * current version
 */
$version	= $baseVersion;
$dir		= dirname( __FILE__ );

/**
 * Current API version
 */
$apiVersion = '0.8';

/**
 * current state
 */
$state = 'alpha';

/**
 * current API stability
 */
$apiStability = 'alpha';

/**
 * release notes
 */
$notes = "See http://dev.php-wax.com for details"

/**
 * package description
 */
$description = "PEAR install of PHP-WAX framework"

$package = new PEAR_PackageFileManager2();

$result = $package->setOptions(array(
    'license'           => 'MIT',
    'filelistgenerator' => 'file',
    'ignore'            => array( 'package.php', 'package.xml', '.svn', '.git' ),
    'simpleoutput'      => true,
    'baseinstalldir'    => 'phpwax',
    'packagedirectory'  => './',
    'dir_roles'         => array('wax' => 'php', 'skel'=>'php'),
    'addhiddenfiles'    => true
    
    ));
if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->setPackage('phpwax');
$package->setSummary('Full Stack PHP Framework');
$package->setDescription($description);

$package->setChannel('pear.php-wax.com');
$package->setAPIVersion($apiVersion);
$package->setReleaseVersion($version);
$package->setReleaseStability($state);
$package->setAPIStability($apiStability);
$package->setNotes($notes);
$package->setPackageType('php');
$package->setLicense('MIT', 'http://www.opensource.org/licenses/mit-license.php');

$package->addMaintainer('lead', 'phpwax', 'PHP-WAX', 'riley.ross@gmail.com', 'yes');
$package->addRelease();
$package->addInstallAs('system/phpwax', 'phpwax');
$package->setPhpDep('5.1.0');
$package->setPearinstallerDep('1.4.0');
$package->generateContents();

$result = $package->writePackageFile();

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
?>
