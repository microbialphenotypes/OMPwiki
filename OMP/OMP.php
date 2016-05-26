<?php
/*
Suite of software for OMP annotation system
*/
define( 'OMP_VERSION', '0.2' );
$authors = array(
	'[mailto:bluecurio@gmail.com Daniel Renfro]',
	'Jim Hu'
);

$wgExtensionCredits['specialpage'][] = array(
	'name' 			=> 'Main OMP page.',
	'description' 	=> 'Handles the OMP pangenomics and annotations.',
	'author' 		=> $authors,
	'version' 		=> OMP_VERSION
);

// ============ check if we're inside MediaWiki =======================
if ( !defined('MEDIAWIKI') ) {
    echo <<<EOT
To install this extension suite, you'll need to edit your LocalSettings.php with
the appropriate configuration directives. Please see the README that comes
with the OMP software. 
EOT;
    exit( 1 );
}

# CODE_LIBRARY defined in code/Setup.php
if ( !defined('CODE_LIBRARY') ) {
    echo <<<EOT
The Hu Laboratory code-library has not been loaded or can't be found. Please
install the library by requiring the Setup.php file in the appropriate directory.
(Probably something like /home/shared/code/trunk/library/Setup.php.)
EOT;
    exit( 1 );
}

# register new column rules that are OMP specific
$te_column_rules['omp_anno'] = dirname(__FILE__).'/tableEdit/column_rules/omp_anno.php';
$te_column_rules['omp_anno_id'] = dirname(__FILE__).'/tableEdit/column_rules/omp_anno_id.php';
$te_column_rules['omp_genotype'] = dirname(__FILE__).'/tableEdit/column_rules/omp_genotype.php';
# for TableEdit2
$tableEditColumnRules['omp_anno'] = dirname(__FILE__).'/tableEdit/column_rules/omp_anno.php';

# TableEditLinks table remodeler for OMP.
# register class autoloader
$wgAutoloadClasses['TableEditOMPLinker'] =  dirname(__FILE__) .'/tableEdit/modules/OMPtableEditLinks.php';
# register hook
$wgHooks['ParserAfterStrip'][] = 'efOMPTableEditLinks';
function efOMPTableEditLinks( &$parser, &$text, &$strip_state ){
	$l = new TableEditOMPLinker($text);
	$text = $l->execute();
	return true;
}

# tabledit category tagging
$wgAutoloadClasses['OMPTableEditCategoryTags'] =  dirname(__FILE__) .'/tableEdit/modules/OMPtableEditCategoryTags.php';
$wgHooks['TableEditBeforeSave'][] = 'OMPTableEditCategoryTags::add_tags';


# Hooks for saving a row
# needed for autoincrement
$wgAutoloadClasses['OMPaccessions'] =  dirname(__FILE__) .'/include/OMPaccessions.php';
$wgHooks['TableEditBeforeDbSaveRow'][] = 'OMPaccessions::BeforeRowSave';
$wgHooks['TableEditAfterDbSaveRow'][] = 'OMPaccessions::AfterRowSave';
$wgAutoloadClasses['OMPtableEditCategoryTags'] =  dirname(__FILE__) .'/tableEdit/modules/OMPtableEditCategoryTags.php';
$wgHooks['TableEditBeforeSave'][] = 'OMPtableEditCategoryTags::add_tags';


# models
$wgAutoloadClasses['OMPmodel'] =  dirname(__FILE__) .'/include/model/OMPmodel.php';
$wgAutoloadClasses['OMPannotation'] =  dirname(__FILE__) .'/include/model/OMPannotation.php';

# special pages
require_once( dirname(__FILE__) .'/specialpages/StrainSpecialPage/StrainNewPage.php');

# unit tests
# PHPUnit testing
include_once('tests/OMPtestSuite.php');
