<?php
/**
 * package.xml generation file for patTemplate
 *
 * This file is executed by createSnaps.php to
 * automatically create a package that can be
 * installed via the PEAR installer.
 *
 * $Id$
 *
 * @author		Stephan Schmidt <schst@php-tools.net>
 */


/**
 * uses PackageFileManager
 */
require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/PackageFileManager/Svn.php';

/**
 * Base version
 */
$baseVersion = '0.7.1';

/**
 * current version
 */
$version	= $baseVersion;
$dir		= dirname( __FILE__ );

/**
 * Current API version
 */
$apiVersion = '0.7.1';

/**
 * current state
 */
$state = 'alpha';

/**
 * current API stability
 */
$apiStability = 'stable';

/**
 * release notes
 */
$notes = <<<EOT
See http://dev.php-wax.com for details
EOT;

/**
 * package description
 */
$description = <<<EOT
PEAR install of PHP-WAX framework
EOT;

$package = new PEAR_PackageFileManager2();

$result = $package->setOptions(array(
    'license'           => 'MIT',
    'filelistgenerator' => 'file',
    'ignore'            => array( 'package.php', 'autopackage2.php', 'package.xml', '.cvsignore', '.svn', 'examples/cache', 'rfcs' ),
    'simpleoutput'      => true,
    'baseinstalldir'    => 'phpwax',
    'packagedirectory'  => './',
    'dir_roles'         => array(
								 'system' => 'script'
                                 )
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

$package->setPhpDep('5.1.0');
$package->setPearinstallerDep('1.4.0');
$package->generateContents();

$result = $package->writePackageFile();

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
?>
