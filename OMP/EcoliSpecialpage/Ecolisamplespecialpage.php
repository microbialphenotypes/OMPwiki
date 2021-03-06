<?php

/*
Sample Special page code based on the examples at Mediawiki.org with some minor modifications

*/

# Alert the user that this is not a valid access point to MediaWiki if they try to access the special pages file directly.
# unlike the mediawiki example, we do NOT assume that the path will always be in the extensions directory
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "<path to extension>/SampleSpecialPage/SampleSpecialPage.php" );
EOT;
	exit( 1 );
}

# add to the Extension credits that are displayed at Special:Version 
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'E.coli Special Page',
	'author' => 'Becky Berg',
#	'url' => 'https://www.mediawiki.org/wiki/Extension:SampleSpecialPage',		
	'descriptionmsg' => 'Ecolisamplespecialpage-desc',
	'version' => '0.0.0',
);

# The rest of the file registers the locations of the other files 
$wgAutoloadClasses['Ecolispecialpage'] = __DIR__ . '/Ecolispecialpage.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgMessagesDirs['Ecolisamplespecialpage'] = __DIR__ . "/i18n"; # Location of localisation files (Tell MediaWiki to load them)
$wgExtensionMessagesFiles['Ecolisamplespecialpagealias'] = __DIR__ . '/Ecolisamplespecialpage.alias.php'; # Location of an aliases file (Tell MediaWiki to load it)
$wgSpecialPages['ecolispecialpage'] = 'Ecolispecialpage'; # Tell MediaWiki about the new special page and its class name