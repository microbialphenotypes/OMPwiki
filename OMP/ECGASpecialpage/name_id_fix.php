<?php
/*
This code will be used to fix the strain names that have Strain: in them. 
Author: Sandra LaBonte
Date: June 20, 2016
*/
$params = getopt( "w:" );
set_IP($params['w']);
$maintClass = "DemoMaint";
class DemoMaint extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->parse_parameters();
	}
#this searches the database based on the strain info table	
	public function execute() {
		#find pages based on strain info table and the title  
		$dbr = wfGetDB( DB_SLAVE );
		/*
		#use this to search for all genes with a certain data type or in the case when the id needs to be changed 
		$result = $dbr->select(
			array('ECGA_2.gene_data as d', 'ECGA_2.gene as g'),
			'*',
			array("d.gene = g.gene_name","d.data_type = 'cluster'" ),
			__METHOD__,
			array()
		);
		*/
		#use this to search for a specific gene and you can change the data type to whatever is needed 
		$result = $dbr->select(
			array('ECGA.gene_data'),
			'*',
			array("gene = '63'", "data = 'RF'"),
			__METHOD__,
			array()
		);
		
		#print_r($result);
		#echo($dbr->lastQuery());
		
		foreach($result as $x){
		#$id = $x->gene_id;
		$data = "ROS";
		echo "$x->gene $x->gene_name $x->gene_id\n";
		/*
		#use this to enter a new line in to the database, no select method is needed 
		$result = $dbr->insert(
					'ECGA_2.gene_data',
					array(
						'gene' => "63",
						'data' => "H2O2",
						'data_type' => "phenotype_qual"
						)
				);
		
		#use this to delete a complete data type from the database 
		$dbr->delete ('ECGA_2.gene_data', 
					array('data'=> $data),
					array("data = '$x->data'"),
					__METHOD__
					);
		/*		
		#use this to change the gene name to an id in the gene field 
		$dbr->update ('ECGA_2.gene_data', 
					array('gene'=> $id),
					array("gene = '$x->gene'"),
					__METHOD__
					);
		#
		*/
		$dbr->update ('ECGA.gene_data', 
					array('data'=> "ROS"),
					array("data = '$x->data'"),
					__METHOD__
					);	
					
					}			
		
	}	
	private function parse_parameters(){
		$this->addOption( "people", "say hello to this", $required = false, $withArg = true, $shortName = 'l' );	
	}
}
require_once( RUN_MAINTENANCE_IF_MAIN );
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