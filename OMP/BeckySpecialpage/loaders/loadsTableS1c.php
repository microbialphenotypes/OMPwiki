<?php

/** 

Edit this file to create a new maintenance task
Execute with

php <filename> -w <path to wiki> <more flags and params>


	Modified by JH on 20120714 to reflect post 1.18 structure.
	For basic idea, see: docs/maintenance
	For getting params see:...

 * $Id$
 * @lastmodified $LastChangedDate$
 * @filesource $URL$
 */

/*
Sample code from docs/maintenance assumes that we will run scripts from inside the maintenance directory of the wiki
Modified to allow running from other working directory paths.
*/
$params = getopt( "w:" );
set_IP($params['w']);
/*
Class definition for the desired maintenance object.  
This must be defined BEFORE the execution lines at the end of the file:

*/

$maintClass = "TableS1c";

class TableS1c extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->parse_parameters();
	}

	/*
	The guts of the object... do the maintenance task;
	Replace the content of this function with your code
	*/
	public function execute() {
		# example of using a command line argument
		$filename = $this->getOption('file');
		echo "opening $filename\n";
		$fh = fopen($filename, 'r');
		
		# example of doing a database query. Here we get the first 10 pages in the db
		$dbw = wfGetDB( DB_MASTER );
		
		
		while (!feof($fh)){
			$line = fgets($fh);
			if ($line{0} == "#" ||trim($line) == ""){
				continue;
			}
			
			list ($strain, $sgRNAseq, $sgRNAname, $targetBSU, $targetName, $targetStrand, $targetOther, $targetProd)= explode("\t",trim($line));
			$strainKey = $targetBSU.'-'.(strtoupper($sgRNAname));
			echo "$strainKey\t$targetOther\t$targetProd\n";
		
			$result = $dbw->insert(
				'peters2016.strain_info',
				array(
					'straininfo_id' => null,
					'strain_key' => $strainKey,
					'other_names' => $targetOther,
					'products' => $targetProd
				)
			); 
		} #close loop 
	}
  
	/*
	
	to get parameters from the shell add a block of lines of the form
	$this->addOption( $name, $description, $required = false, $withArg = false, $shortName = false ) 
	
	This allows us to use the getter method $this->getOption($name);, and also automatically adds to the help text
	See maintenance/Maintenance.php for what the arguments mean.
	
	*/
	private function parse_parameters(){
		$this->addOption( "file", "this is the file we are opening", $required = false, $withArg = true, $shortName = 'f' );	
	}
}

require_once( RUN_MAINTENANCE_IF_MAIN );

/*
Function to set the global variable $IP and include the abstract
class Maintenance
*/
function set_IP($path){
	global $IP;
	if ( isset($path) && is_file("$path/maintenance/Maintenance.php") ){
		$IP = $path;
		require_once( $IP . "/maintenance/Maintenance.php" );
		return $path; 
	} else {
		die ("need -w <path-to-wiki-directory>");
	}
	
}
