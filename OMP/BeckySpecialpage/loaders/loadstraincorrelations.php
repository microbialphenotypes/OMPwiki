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

$maintClass = "strain_correlations";

class strain_correlations extends Maintenance {

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
		
		# example of doing a database query. Here we get the first 10 pages in the db
		$dbw = wfGetDB( DB_MASTER );
		
		echo "opening $filename\n";
		$fh = fopen($filename, 'r');
		if(!$fh) die;
		# get the first line, which contains strain2 names
		$line = fgets($fh);
		$strain2names = explode("\t", trim($line));

		# loop through the rest of the file
		while (!feof($fh)) {
			#get a line for strain 1
			$line = fgets($fh);			
			$values = explode("\t", trim($line));
			foreach($values as $i => $val){
				if($i == 0){
					$strain1 = $val;
				}else{
					$strain2 = $strain2names[$i];
					echo "$strain1\t$strain2\t$val\n";
					$result = $dbw->insert(
						'peters2016.strain_cc',
						array(
							'strain_cc_id' => null,
							'strain' => $strain1,
							'strain2' => $strain2,
							'correlation_coefficient' => $val
						)
					)	;
				}
			}		
		
		
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