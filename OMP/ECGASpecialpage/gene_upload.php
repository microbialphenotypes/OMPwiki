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

$maintClass = "gene_upload";

class gene_upload extends Maintenance {

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

		
		for ($i=0; $i<40 ;$i++){
			$line = fgets($fh);
			#echo "$i $line";
			
			if ($line{0} == "\r\n" ||trim($line) == ""){
				continue;
			}
			
			list ($gene, $mutation)= explode(",",trim($line));
			echo "$gene $mutation\n";
			/*
			if ($gene != ''){
				$dbw = wfGetDB( DB_MASTER );
				$result = $dbw->insert(
					'ECGA_2.gene_data',
					array(
						'gene' =>$gene,
						'data' => $mutation,
						'data_type' => "mutation_rate"
					)
				);
				}
			/*	
			if ($egene2 != ''){
				$dbw = wfGetDB( DB_MASTER );
				$result = $dbw->insert(
					'ECGA.gene_data',
					array(
						'gene' =>$egene2,
						'data' => $human_gene,
						'data_type' => "homologs"
						)
				);	
				}
			if ($egene3 != ''){
				$dbw = wfGetDB( DB_MASTER );
				$result = $dbw->insert(
					'ECGA.gene_data',
					array(
						'gene' =>$egene3,
						'data' => $human_gene,
						'data_type' => "homologs"
						)
				);	
				}	
			*/	
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
