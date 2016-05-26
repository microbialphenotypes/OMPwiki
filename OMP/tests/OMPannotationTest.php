<?php
/*
Test for model/OMPannotation
*/

class OMPannotationTest extends PHPUnit_Framework_Testcase{

	public function setup(){
		# look for a box that has at least one row
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			array('ext_TableEdit_box','ext_TableEdit_row'),
			'ext_TableEdit_box.*',
			array('ext_TableEdit_box.box_id = ext_TableEdit_row.box_id', "template = 'OMP_annotation_table'"),
			__METHOD__,
			array('LIMIT' => '1')
		);
		foreach($result as $x){
			#print_r($x);
			$this->box = new wikiBox($x->box_uid);
			$this->box->setId($x->box_id);
			$this->box->load();
		}
		return true;
	}

	public function testDesignedToPass(){
		print_r($this->box->rows);
		$this->assertTrue(false);
	
	}


}