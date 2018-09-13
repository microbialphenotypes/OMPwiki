<?php
/* 
This script was originally built for a special page querying data from, "A comprehensive, CRISPR-based Functional Analysis of Essential Genes in Bacteria," written by Peters et al.
This new special page will query E.coli data. 
Published June 2016. 
 

Authors: Becky Berg
Last revised 8/3/2016
*/

/* We are creating a new class called Myspecialpage. 
This new class is going to extend the properties and methods of a parent class called SpecialPage. */
class EcoliSpecialpage extends SpecialPage {					
	function __construct() {
		parent::__construct( 'ecolispecialpage' );
	}

	#These are the main special page executions. 
	function execute( $par ) {
		
		# get values passed from form inputs
		$request = $this-> getRequest();  																					
		$formOpts = $request->getText('wpradioForm');  																		
		
		# get an Output object																	
		$out = $this->getOutput();  																						
		$out->setPageTitle( 'Nichols 2011 Escherichia coli data query' );
		 
		#The page changes html textfield form after each radio button request
		$text = '<script>$(\'input[type=radio]\').on(\'change\', function() {$(this).closest(\'form\').submit();});</script>';		
	
		# do the query and make a table
		$results = $this->doRequest($request, $formOpts);  
		$text .= $this->renderTable($results, $formOpts);  																	

		# add jQuery dataTables javascript and css stolen from TableEdit (requires TableEdit also be installed)
		$out->addModules('ext.TableEdit'); 
		
		$out->addWikiText(self::before());
		# build the page content
		# make the form
		$this->makeForm($request, $formOpts);  																								                   
		$text .= "Please select a search type, enter your query in one or both text fields and click the 'Submit' button. Acceptable condition query's include: condition name and keywords.";  	 
		$out->addHTML($text);
		$out->addWikiText(self::after());																								
	}		

	#Querying phpMyAdmin database based on the radio form selection type. 
	#Text field inputs from radio form selection type queries data from database. 
	#Each radio form selection type is a separate case. There are two html text field inputs per radio form selection type.     
	function doRequest($request, $formOpts){
		$conds = array();
		$options = array();
		switch($formOpts){
 			#Each case specifies where to query what data
 			default:
 			case '0':				
				$tables = 'chemgen.straincond';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					$conds[] = "strain LIKE '%$requesttext1%'";
					$options['ORDER BY'] = 'score desc';
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					$conds[] = "cond IN ('".implode("','", self::getConds($requesttext2))."')";		
					$options['ORDER BY'] = 'score desc';
				}		
				if ($requesttext1 == '' && $requesttext2 == ''){
					$options['LIMIT'] = 100;
					$options['ORDER BY'] = 'score desc';
				}
				break;
			case '1':
				$tables = 'chemgen.condition_cc';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					$conds[] = "cond IN ('".implode("','", self::getConds($requesttext1))."')";
					$options['ORDER BY'] = 'correlation_coefficient desc';				
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					$conds[] = "cond2 IN ('".implode("','", self::getConds($requesttext2))."')";		
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}
				if ($requesttext1 == '' && $requesttext2 == ''){
					#$options['LIMIT'] = 100;
					$options['ORDER BY'] = 'correlation_coefficient desc';
					$conds[] = 'correlation_coefficient != 1';
				}		
				break;
			case '2':	
				$tables = 'chemgen.strain_cc';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					$conds[] = "strain IN ('".implode("','", self::getStrains($requesttext1))."')";
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					$conds[] = "strain2 IN ('".implode("','", self::getStrains($requesttext2))."')";		
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}
				if ($requesttext1 == '' && $requesttext2 == ''){
					$options['LIMIT'] = 100;
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}
				break;
			case '3':	
				$tables = 'chemgen.conditions';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					$conds[] = "name IN ('".implode("','", self::getConds($requesttext1))."')";
					
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					$conds[] = "keywords LIKE '%$requesttext2%'";			
				}							
		}
		
		#Introduction to database query. 
		$dbr = wfGetDB( DB_SLAVE );
		$results = $dbr->select(
						$tables,	#specified in each case
						'*',
						$conds,		#specified in each case
						__METHOD__, 
						$options	
					);
		#echo $dbr->lastQuery(); 	
		return $results;
	}
	
	static function getConds($text){
		$dbconds = array();
		$dbr = wfGetDB( DB_SLAVE ); #doing a database query inputing column1c or column2c to get column3c output. 
		$results = $dbr->select(
						'chemgen.conditions',
						'*',
						"name LIKE '%$text%' OR keywords LIKE '%$text%'",
						__METHOD__
					);	
	#	echo $dbr->lastQuery();
		foreach($results as $x){
			$dbconds[] = $x->name;
		}
		return $dbconds;			
	}
	
	static function getStrains($text){
		$dbstrain = array();
		$dbr = wfGetDB( DB_SLAVE ); #doing a database query inputing column1c or column2c to get column3c output. 
		$results = $dbr->select(
						'chemgen.straincond',
						'*',
						"strain LIKE '%$text%'",
						__METHOD__
					);	
	#	echo $dbr->lastQuery();
		foreach($results as $x){
			$dbstrain[] = str_replace("'","\'",$x->strain);
		}
		return $dbstrain;			
	}
	
	#Creates three button radio form and text fields on special page. 
	function makeForm($request, $formOpts){
		#print_r ($request);
		$formDescriptor = array(
			'radioForm' => array(
                				'type' => 'radio',
                				'label' => 'Select a search type:',
                				'options' => array( # The options available within the checkboxes (displayed => value)
												'Growth data (Strain/Condition)' => 0,
												'Correlations among conditions' => 1,
												'Correlations among strains' => 2,
												'Conditions' => 3,
											),
                				'default' => 0 # The option selected by default (identified by value)
                			),
            'textfield1' => array(
					'label' => 'Strain', # What's the label of the field
					'class' => 'HTMLTextField' # What's the input type
					),
			'textfield2' => array(
					'label' => 'Condition', # What's the label of the field
					'class' => 'HTMLTextField' # What's the input type
					), 				
 		);
		
		switch($formOpts){
			case '1':
				$formDescriptor['textfield1']['label'] = 'Condition 1'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'Condition 2'; # What's the label of the field	
				break;
			case '2':
				$formDescriptor['textfield1']['label'] = 'Strain 1'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'Strain 2'; # What's the label of the field
				break;
			case '3':
				$formDescriptor['textfield1']['label'] = 'Condition'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'Keywords'; # What's the label of the field		
			default:
		}

		#Submit button structure and page callback. 
        $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'myform'); # We build the HTMLForm object, calling the form 'myform'
        $htmlForm->setSubmitText( 'Submit' ); # What text does the submit button display
		
		/* We set a callback function */ 
		#This code has no function to the special page. It used to produce red wiki text callback such as "Try Again" commented-out below under processInput function.  
		$htmlForm->setSubmitCallback( array( 'EcoliSpecialpage', 'processInput' ) ); # Call processInput() in specialpagetemplate on submit
        $htmlForm->show(); # Displaying the form
	}

	/* We write a callback function */
	#Making a generic table header	
	public static function make_table_header(Array $headings){
		$html  = Xml::openElement( 'thead' );
        $html .= Xml::openElement( 'tr' );
        foreach ( $headings as $heading ) {
            $html .= Xml::element( 'th', array(), $heading );
		}
        $html .= Xml::closeElement( 'tr' );
        $html .= Xml::closeElement( 'thead' );
		return $html;
	}
	#Making specified table for each radio button case
	protected function renderTable($results, $formOpts) {        
        $html = "";  
        $html .= Xml::openElement( 'p' );  
        $html .= 'The following table displays your query and subsequent data based on the selected search type.'; #html text displayed with the table
        $html .= Xml::closeElement( 'p' );
		$html .= Xml::openElement( 
					'table',
					array( 
						'id' => 'data display',
						'class' => 'dataTable OMP_annotation_table tableEdit',
						'border' => 1
					)
				); 		   	
		
		switch($formOpts){
        	case '0':
				// make the headings
				$headings = array('Strain','Condition','Score');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->strain</td><td>$x->cond</td><td>$x->score</td></tr>";
				}	
				break;	
        	case '1':
				// make the headings
				$headings = array('Condition 1','Condition 2','Correlation Coefficient');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->cond</td><td>$x->cond2</td><td>$x->correlation_coefficient</td></tr>";
				}	
				break;	
			case '2':
				// make the headings
				$headings = array('Strain 1','Strain 2','Correlation Coefficient');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->strain</td><td>$x->strain2</td><td>$x->correlation_coefficient</td></tr>";
				}
				break;	
			case '3':
				// make the headings
				$headings = array('Condition','Keywords');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->name</td><td>$x->keywords</td></tr>";
				}	
			default:
		}
			#closing table body and table
			$html .= Xml::closeElement( 'tbody' );
			$html .= Xml::closeElement( 'table' ); 
			return $html; #display table
	}
	
	#adding Wiki elements to the page before html table.
	#wiki section title: \n== Title ==
	static function before(){
		$text = "
		\n== Background ==  
		This databrowser queries data from Nichols et al. 2011 Cell article. <ref name='PMID:21185072'/>
		
		\n== References ==
		<references/>
			
		\n== Search ==
		";
	return $text;
	}
	
	#adding Wiki elements to the page after html table. 
	static function after(){
		$text = "
		";
	return $text;
	} 
	
	#Page callback if both textfields are empty. 
	static function processInput($formData) {	    	
			if ($formData['textfield1'] == '' && $formData['textfield2'] == '' ) {
				#return "Try again"; 
			}
	}		
} # close Class 