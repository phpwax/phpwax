<?php
/**
 * packager.php -- Example script using
 * PEAR_PackageFileManager to prepare a 
 * package.xml file for a PEAR distribution 
 * of phpMyAdmin.
 *
 * @author  Clay Loveless <clay@killersoft.com>
 */

// include package.xml 2.0-aware PackageFileManager
ini_set('include_path', '/usr/local/php5.1/lib/php');
require_once 'PEAR/PackageFileManager2.php';

// Die a horrible death if we make any mistakes
PEAR::setErrorHandling(PEAR_ERROR_DIE);

// Instantiate PackageFileManager object
$pkg = new PEAR_PackageFileManager2;

// Set options
$options = array();

// We'll install in a 'pma' directory the base of our 
// web root directory.
$options['baseinstalldir'] = '/wxframework';

// Where are we reading the files for our package from?
$options['packagedirectory'] = 
    '/usr/local/php5.1/lib/php/wxframework/';

// We want to read from the package directory just like a
// regular directory.
// If you're reading from a Subversion or CVS working copy,
// you can use filelistgenerator types of 'CVS' or 'SVN'.
$options['filelistgenerator'] = 'file';

// PEAR-compatible installations generally install
// documentation files in the doc_dir defined by PEAR's 
// configuration. Since the phpMyAdmin interface links
// directly to the bundled documentation.html file, we'll
// leave that in place in the main package install dir.
// But, let's make sure other doc-like files are put in 
// the proper place.
$options['exceptions'] = array(
            'Documentation.txt'     => 'doc',
            'ChangeLog'             => 'doc',
            'CREDITS'               => 'doc',
            'INSTALL'               => 'doc',
            'LICENSE'               => 'doc',
            'README'                => 'doc',
            'RELEASE-DATE-2.6.3-pl1'=> 'data',
            'TODO'                  => 'doc'
            );

// That's it for options -- just set them.
$pkg->setOptions($options);

// Set the name of our package
$pkg->setPackage('wxframework');

// Set the overall version numbers
// Note that the PEAR installer does not consider 
// dashes in the version number to be valid, so 
// those have been removed here.
// 2.6.3-pl1 becomes 2.6.3pl1
$pkg->setReleaseVersion('0.5.1');
$pkg->setAPIVersion('0.5.1');

// Stability settings - dev, alpha, beta or stable
$pkg->setReleaseStability('beta');
$pkg->setAPIStability('beta');

// Set the channel - you'd use your own channel server
// information here.
$pkg->setChannel('pear.webxpress.com');

// A 'php' Package type is a PEAR-style package
$pkg->setPackageType('php');

// Summary of what this package is
$pkg->setSummary('Webxpress php framework');

// Full description of this package
$pkg->setDescription('These are the core library files required to run the framework');

// Link in the license information
$pkg->setLicense('BSD',
    'http://www.opensource.org/licenses/bsd-license.php');

// Add the package maintainer, who may not necessarily
// be a package developer. I'm the lead package maintainer
// in this case.
$pkg->addMaintainer(
    'lead','ross','Ross Riley','ross@webxpress.com');
    
// Require at least PHP 5.1.0, as required by the framework
$pkg->setPhpDep('5.1.0');

// Since this is a PEAR 1.4 package, require a minimum
// installer version.
$pkg->setPearinstallerDep('1.4.0a12');

// NOTE: If we wanted to add a dependency on a PEAR
// package, such as PEAR::DB, for example, we'd use the 
// following:
// $pkg->addPackageDepWithChannel(
//     'PHPTAL',               // package name
//     'pear.php.net',     // package channel
//     '1.6.0',            // minimum version
//     false,              // no maximum version
//     '1.7.6'             // recommended version
//     );


// Add a release, and notes about this release.
$pkg->addRelease();
$pkg->setNotes('Initial PEAR-packaged release.');

// Generate the file list
$pkg->generateContents();


// Debug the package file by default, and 
// create the actual package.xml file if this script
// is called with a 'make' argument.
if (isset($argv[1]) && $argv[1] == 'make') {
	$pkg->writePackageFile();
	
	// What follows is a workaround to 
	// compensate for open issues in the alpha version
	// of PackageFileManager at the time of this writing.
	
	// Add the 'usesrole' section for our custom file role.
	// PFM1.6.0a1 does not have a method for automating this.
	$pkgxml = file_get_contents(
	           $options['packagedirectory'].'package.xml');
	$usesrole = " <usesrole>\n";
	$usesrole .= "  <role>php</role>\n";
	$usesrole .= "  <package>Role_php</package>\n";
	$usesrole .= "  <channel>pear.webxpress.com</channel>\n";
	$usesrole .= " </usesrole>";
	$pkgxml = str_replace("</dependencies>", 
	                      "</dependencies>\n$usesrole",
	                      $pkgxml);
	$fp = fopen($options['packagedirectory'].'package.xml',
	               'w');
	fwrite($fp, $pkgxml);
	fclose($fp);
	
} else {
	$pkg->debugPackageFile();
}

?>