<?php
/*
Column rule for genotypes.

Jim Hu 7/22/2015 - started. .

*/

# the class name must be ecTableEdit_ followed by the name of the column rule
class ecTableEdit_omp_genotype extends TableEdit_Column_rule{

	function preprocess(){
		# this preprocessing line is to deal with residue from previous entry as dbxrefs
		# where most alleles got prefixed by "other:" and alleles were an unordered list in wiki markup
		$this->col_data = str_replace(array("\n*", 'other:', '  '), " ", ltrim($this->col_data, '*'));
		$this->col_data = trim($this->col_data);
	}

	function make_form_row(){
		return TableEditView::form_field($this->col_index, $this->col_data);
	}	
	

	# overload to make the data a list
	function show_data(){
		return $this->col_data;
	}
	
}