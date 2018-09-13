<?php
/* 
This script builds a special page that queries data from, "A comprehensive, CRISPR-based Functional Analysis of Essential Genes in Bacteria," written by Peters et al.
Published June 2016

Authors: Becky Berg
Last revised 7/13/2016
*/

/* We are creating a new class called Specialbeckypage. 
This new class is going to extend the properties and methods of a parent class called SpecialPage. */
class Specialbeckypage extends SpecialPage {					
	function __construct() {
		parent::__construct( 'peters2016' );
	}

	#These are the main special page executions. 
	function execute( $par ) {
		
		# get values passed from form inputs
		$request = $this-> getRequest();  																					
		$formOpts = $request->getText('wpradioForm');  																		
		
		# get an Output object																	
		$out = $this->getOutput();  																						
		$out->setPageTitle( 'Peters 2016 Bacillus subtilis data query' ); 
		
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
		$text .= "Please select a search type, enter your query in one or both text fields and click the 'Submit' button. Acceptable strain query's include: strain BSU#, alternative gene name, or strain target product.";  	 
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
				$tables = 'peters2016.strain_cc';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					#Strain BSU-# can be recognized by target product or other name inputs since target product or other name inputs produce a strain_key data output which is the same BSU-# as the strain BSU-#. Input from html textfield 1. 
					$conds[] = "strain IN ('".implode("','", self::getBSUs($requesttext1))."')";
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					#Strain BSU-# can be recognized by target product or other name inputs since target product or other name inputs produce a strain_key data output which is the same BSU-# as the strain BSU-#. Input from html textfield 2. 
					$conds[] = "strain2 IN ('".implode("','", self::getBSUs($requesttext2))."')";			
					$options['ORDER BY'] = 'correlation_coefficient desc';
				}
				if ($requesttext1 == '' && $requesttext2 == ''){
					$options['ORDER BY'] = 'correlation_coefficient desc';
					$conds[] = 'correlation_coefficient != 1';	
				}		
				break;
			case '1':
				$tables = 'peters2016.straincond';
				$requesttext1 = $request->getText('wptextfield1');
				if ($requesttext1 != ''){	
					#Strain BSU-# can be recognized by target product or other name inputs since target product or other name inputs produce a strain_key data output which is the same BSU-# as the strain BSU-#. Input from html textfield 1. 
					$conds[] = "strain IN ('".implode("','", self::getBSUs($requesttext1))."')";	
					$options['ORDER BY'] = 'score desc';
				}	
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					#Input from html textfield 2 will produce cond (gene conditions) output.
					$conds[] = "cond LIKE '%$requesttext2%'";		
					$options['ORDER BY'] = 'score desc';
				}	
				if ($requesttext1 == '' && $requesttext2 == ''){
					$options['ORDER BY'] = 'score desc';		
				}
				break;
			case '2':				
				$tables = 'peters2016.strain_info';
				$requesttext1 = $request->getText('wptextfield1');
				if ($requesttext1 != ''){	
					#Input from html textfield 1 will produce other_name (alternative gene name) data output. 
					$conds[] = "other_names LIKE '%$requesttext1%'";	
				}	
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					#Input from html textfield 2 will produce products (target products) data output.
					$conds[] = "products LIKE '%$requesttext2%'";		
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
	
	#Target products or alternative gene name or strain_key inputs produce strain_key (BSU-#) outputs.  
	static function getBSUs($text){
		$bsus = array();
		$dbr = wfGetDB( DB_SLAVE ); #doing a database query inputing products or other_names to get the strain_key (USB-#)
		$results = $dbr->select(
						'peters2016.strain_info',
						'*',
						"products LIKE '%$text%' OR other_names LIKE '%$text%' OR strain_key LIKE '%$text%'",
						__METHOD__
					);	
	#	echo $dbr->lastQuery();
		foreach($results as $x){
			$bsus[] = $x->strain_key;
		}
		return $bsus;			
	}
	
	#Creates three button radio form and text fields on special page. 
	function makeForm($request, $formOpts){
		#print_r ($request);
		$formDescriptor = array(
			'radioForm' => array(
                				'type' => 'radio',
                				'label' => 'Select a search type:',
                				'options' => array( # The options available within the checkboxes (displayed => value)
												'Correlations among strains' => 0,
												'Growth data (Strain/Condition)' => 1,
												'Strain Information' => 2,
											),
                				'default' => 0 # The option selected by default (identified by value)
                			),
            'textfield1' => array(
								'label' => 'Strain 1', # What's the label of the field
								'class' => 'HTMLTextField' # What's the input type
								),
			'textfield2' => array(
					'label' => 'Strain 2', # What's the label of the field
					'class' => 'HTMLTextField' # What's the input type
					), 				
 		);
		#echo "<br>formOpts:$formOpts<br>";
		switch($formOpts){
			case '1':
				$formDescriptor['textfield1']['label'] = 'Strain'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'Condition'; # What's the label of the field	
				break;
			case '2':
				$formDescriptor['textfield1']['label'] = 'Alternative gene name'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'Target product'; # What's the label of the field	
				break;	
			default:
		}

		#Submit button structure and page callback. 
        $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'myform'); # We build the HTMLForm object, calling the form 'myform'
        $htmlForm->setSubmitText( 'Submit' ); # What text does the submit button display
		
		/* We set a callback function */
		# This code no longer does anything on the special page.  This gave us the red callback text on the page.  
		$htmlForm->setSubmitCallback( array( 'specialbeckypage', 'processInput' ) ); # Call processInput() in specialbeckypage on submit
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
				$headings = array('Strain 1','Strain 2','Correlation Coefficient');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->strain</td><td>$x->strain2</td><td>$x->correlation_coefficient</td></tr>";
				}	
				break;	
        	case '1':
				// make the headings
				$headings = array('Strain','Condition','Score');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->strain</td><td>$x->cond</td><td>$x->score</td></tr>";
				}	
				break;	
			case '2':
				// make the headings
				$headings = array('Target BSU-sgRNA Name', 'Target Product(s)','Alternative Gene Name(s)');
				$html .= self::make_table_header($headings); 
				
				// make the body
				foreach($results as $x){
					$html .= "<tr><td>$x->strain_key</td><td>$x->products</td><td>$x->other_names</td></tr>";
				}
			default:
		}
			#closing table body and table
			$html .= Xml::closeElement( 'tbody' );
			$html .= Xml::closeElement( 'table' ); 
			return $html; #display table
	}
	
	
	#adding Wiki elements to the page before html table
	#wiki section title: \n== Title ==
	static function before(){
		$text = "
		\n== Background ==
		This databrowser queries data from Peters et al. 2016 Cell article. <ref name='PMID:27238023'/>
		
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