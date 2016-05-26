<?php
/*
Special page component of the OMP extension for forms-based creation of new strain pages in the OMP wiki

Authors: Sandy LaBonte and Jim Hu
version 1
Aug 7, 2015



*/
class SpecialNewPage extends SpecialPage {
	 
	function __construct() {
		parent::__construct( 'StrainNewPage' );
	}
	
	public function execute($par) {
         $this->setHeaders();
		 
		$formDescriptor = array(
			'strain_name_field' => array(
				'class' => 'HTMLTextField',
				'label' => 'Name',
				'validation-callback' => array('SpecialNewPage', 'validateStrainName'),
				required => true,
				),	
			'strain_name_help' => array(
				'type' => 'info',
				'default' => wfMessage("strainnewpage-help-name")->text(),
				'raw' => true
				),	
			'strain_parent_field' => array(
				'class' => 'HTMLTextField',
				'label' => 'Parent',
				'validation-callback' => array('SpecialNewPage', 'validateParentStrainName'),
				),	
			'strain_parent_help' => array(
				'type' => 'info',
				'default' => wfMessage("strainnewpage-help-parent")->text(),
				'raw' => true
				),	
			'strain_synonym_field' => array(
				'class' => 'HTMLTextField',
				'label' => 'Synonyms',
				),	
			'strain_reference_field' => array(
				'class' => 'HTMLTextField',
				'label' => 'Reference',
			#	'help' => 'The format should be similar to: PMID:456687630', 
				),		
			'strain_reference_help' => array(
				'type' => 'info',
				'default' => wfMessage("strainnewpage-help-reference")->text(),
				'raw' => true
				),	
			'strain_availability_field' => array(
				'class' => 'HTMLTextField',
				'label' => 'Availability',
				#'help' => 'The format should be similar to: Coli Genetics Stock Center [[link]]', 
				),		
			'strain_availability_help' => array(
				'type' => 'info',
				'default' => wfMessage("strainnewpage-help-availability")->text(),
				'raw' => true
				),	
		);
			
		$htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'myform' ); # We build the HTMLForm object, calling the form "myform"
		$htmlForm->setSubmitText( 'Submit' ); # What text does the submit button display
		$htmlForm->setSubmitCallback( array( 'SpecialNewPage', 'processInput' ) );  
        $htmlForm->show(); # Displaying the form
	}
	
	#validation logic to test whether or not the strain name is known so it won't create a new page if it is known
	static function validateStrainName($strainName, $allData) {
        if ($strainName == '') {
        	return wfMsgExt('htmlform-required','parseinline');
        }else{ 
        	#if there is a strain name like the input, get the url to the page(s)
        	$foundStrains = self::findStrains($strainName);
        	if(!empty($foundStrains)){
           		return "Strain name already exists.<ul><li>".
           			implode("</li><li>", $foundStrains).
           			"</li></ul>If this is not what you are looking for, please rename your strain.";
           	}		
        }	 
		return true;
	}
    
	static function validateParentStrainName($parentName, $allData) {
		if ( $parentName!= '' ) {
			$pt = Title::newFromText($parentName);
			if ($pt->isKnown()) {
				return true;
			}else{	
				$foundParents = self::findStrains($parentName);
				return "Parent Unknown. Did you mean:<ul><li>".implode("</li><li>", $foundParents)."</li></ul>";	
			}
		}
		return true;
	}	
	
	/*
	static method to query the wiki database for pages that could match a strain name input
	used to find "did you mean" candidates for both a new strain and the parent strain.
	
	@param $checkName: string	
	@return $found: array of links to pages
	*/
	static function findStrains($checkName){
		#search database for strain name like input and gets url
		$checkName = str_replace(' ','_', $checkName);
		$dbr = wfGetDB( DB_SLAVE );
		$results = $dbr->select(
			'page',
			'*',
			array("page_title LIKE '%$checkName%'", "page_namespace = '0'" ),
			__METHOD__,
			array()
		);	
		$found = array();
		if ($results->numrows() > 0) {	
			$linker = new Linker;
			foreach($results as $y){
				$y = Title::newFromText ($y->page_title);
				$link = $linker->link($y);
				$found[] = $link;
			}	
		}
		return $found;				
	}

	#callback function that wil create a new page from a template based on the data from the HTML form
	static function processInput( $formData, $form ) {
        if ( $formData['strain_name_field'] != '' ) {
				
			#creates a new id number for each strain
			$dbw = wfGetDB( DB_SLAVE );
			$dbw->insert( 'omp_master.strain', array( 'id' => null, 'name' => $formData['strain_name_field'] ) );
			$id = $dbw->insertId();
	
			#creates a new page based on a template and names the page and adds the category box
			$new_page_template = "Template:StrainPage";
			$new_page_pageName = "OMP_ST:"."$id"."_!_".$formData['strain_name_field'];
			$newpagetemplateTitle = Title::newFromText($new_page_template);
			$templatePage = new WikiPageTE($newpagetemplateTitle);
			$text1 = $templatePage->getContent();
			$reason = 'Page creation by '.__CLASS__;
			$t = Title::newFromText($new_page_pageName); 
			$t->getFullURL();
			
			#creates the new page and saves it
			$wikiPage = new WikiPageTE($t);
			$wikiPage->save($text1, $reason);
			$wikiPage->touch();
			
			$parentInfo = "";
			if ($formData['strain_parent_field'] != ''){
				$parentInfo = "parent:".$formData['strain_parent_field'];
				$p = Title::newFromText($formData['strain_parent_field']);
				#gets strain info table from parent page
				$temp = "Strain_info_table";
				$parent_page = new WikiPageTE($p);
				$parenttable = $parent_page->getTable($temp);
				$box2 = $parenttable[0];
				$parentgenotyperow = $box2->get_row_hash(0);
				$wikiPage->touch();
			}
						
			#new row on strain info table and inserts strain name, parent genotype, and parent name
			$strain_table_template = "Strain_info_table";
			$newtable = $wikiPage->getTable($strain_table_template);
			$box = $newtable[0];
			$newrow = $box->insert_row(
					$formData['strain_name_field']."||".
					$formData['strain_synonym_field']."||".
					$parentgenotyperow['taxon_information']."||".
					$parentgenotyperow['genotype']."||".
					$formData['strain_reference_field']."||".
					$formData['strain_availability_field']."||".
					$parentInfo
					);
			$wikiPage->touch();
									
			#redirects page
			$out = $form->getOutput();
			$out -> redirect($t);	
			return true; 
        } elseif ($formData['strain_name_field'] == 'again') {
                return false; #if returned false, the form will be redisplayed. 
    	}		
        return 'Try Again'; 
	}	
}	