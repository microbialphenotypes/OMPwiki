<?php
class OMPtestSuite{

	public static function registerTests($files){
		$files = array_merge( $files, glob( __DIR__ . '/*Test.php' ) );
		return true;
	}
}

$wgHooks['UnitTestsList'][] = 'OMPTestSuite::registerTests';