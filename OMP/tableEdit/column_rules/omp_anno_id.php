<?php
/*
Column rule to generate annotation ids for annotations.

Jim Hu 4/8/2015 - started. 
	Assign a temp id before save. We could just assign an id immediately, which is what we should do
	for page creation, but here, we have to deal with abortive editing of tables.
 
	$wgHooks['TableEditBeforeDbSaveRow'][] = 'OMPaccessions::BeforeRowSave';
	$wgHooks['TableEditAfterDbSaveRow'][] = 'OMPaccessions::AfterRowSave';
hooks

*/

# the class name must be ecTableEdit_ followed by the name of the column rule
class ecTableEdit_omp_anno_id extends TableEdit_Column_rule{
	const OMP_TMP_ANNO_ID = "Pending";

	function  preprocess(){
		if($this->col_data != ""){
			return $this->col_data;
		}
		$this->col_data = self::OMP_TMP_ANNO_ID;
	}
	
	function make_form_row(){
		$form = TableEditView::form_field($this->col_index, $this->col_data, 40,'hidden').$this->col_data;
		return $form;
	
	}
	
	# overload to make the data a list
	function show_data(){
		return $this->col_data;
	}
	
}