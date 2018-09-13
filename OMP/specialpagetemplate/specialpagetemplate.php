<?php
/* 
This is a template script that was originally built for a special page querying data from, "A comprehensive, CRISPR-based Functional Analysis of Essential Genes in Bacteria," written by Peters et al.
Published June 2016.  

Authors: Becky Berg
Last revised 7/15/2016
*/

/* We are creating a new class called Myspecialpage. 
This new class is going to extend the properties and methods of a parent class called SpecialPage. */
class MySpecialpage extends SpecialPage {					
	function __construct() {
		parent::__construct( 'MySpecialpageName' );
	}

	#These are the main special page executions. 
	function execute( $par ) {
		
		# get values passed from form inputs
		$request = $this-> getRequest();  																					
		$formOpts = $request->getText('wpradioForm');  																		
		
		# get an Output object																	
		$out = $this->getOutput();  																						
		$out->setPageTitle( 'Special page title' );
		 
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
		$text .= "Special page user instructions";  	 
		$out->addHTML($text);
		$out->addWikiText(self::after());																								
	}		

	#Querying phpMyAdmin database based on the radio form selection type. 
	#Text field inputs from radio form selection type queries data from database. 
	#Each radio form selection type is a separate case. There are two html text field inputs per radio form selection type.     
	function doRequest($request, $formOpts){
		$conds = array();
		switch($formOpts){
 			#Each case specifies where to query what data
 			default:
 			case '0':				
				$tables = 'database.table';
				$requesttext1 = $request->getText('wptextfield1');  
				if ($requesttext1 != ''){	
					#Database ID's in column1a can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 1. 
					$conds[] = "column1a IN ('".implode("','", self::getdatabaseIDs($requesttext1))."')";
					
				}		
				$requesttext2 = $request->getText('wptextfield2');
				if ($requesttext2 != ''){	
					#Database ID's in column2a can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 2.
					$conds[] = "column2a IN ('".implode("','", self::getdatabaseIDs($requesttext2))."')";			
				}		
				break;
			case '1':
					#same text format as case 0 but different database/table and conds. 
					#Database ID's in column1b can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 2.
				$tables = 'databaseB.tableB';	
					$conds[] = "column1b IN ('".implode("','", self::getdatabaseIDs($requesttext1))."')";
					#text field 2 input queries data from column2b, databaseB/tableB.  
					$conds[] = "column2b LIKE '%$requesttext2%'";
				break;
			case '2':	
					#same text format as case 0 but different database/table and conds. 
				$tables = 'databaseC.tableC':		 
					#text field 1 input queries data from column1c, databaseC/tableC.  
					$conds[] = "column1c LIKE '%$requesttext1%'";	
					#text field 2 input queries data from column2c, databaseC/tableC.  
					$conds[] = "column2c LIKE '%$requesttext2%'";			
		}
		
		#Introduction to database query. 
		$dbr = wfGetDB( DB_SLAVE );
		$results = $dbr->select(
						$tables,	#specified in each case
						'*',
						$conds,		#specified in each case	
						__METHOD__	
					);
		#echo $dbr->lastQuery();				
		return $results;
	}
	
	#column1c, column2c, or column3c inputs produce column3c outputs.  
	static function getdatabaseIDs($text){
		$dbID = array();
		$dbr = wfGetDB( DB_SLAVE ); #doing a database query inputing column1c or column2c to get column3c output. 
		$results = $dbr->select(
						'databaseC.tableC',
						'*',
						"column1c LIKE '%$text%' OR column2c LIKE '%$text%' OR column3c LIKE '%$text%'",
						__METHOD__
					);	
	#	echo $dbr->lastQuery();
		foreach($results as $x){
			$dbID[] = $x->column3c;
		}
		return $dbID;			
	}
	
	#Creates three button radio form and text fields on special page. 
	function makeForm($request, $formOpts){
		#print_r ($request);
		$formDescriptor = array(
			'radioForm' => array(
                				'type' => 'radio',
                				'label' => 'Select a search type:',
                				'options' => array( # The options available within the checkboxes (displayed => value)
												'Radio button label 0' => 0,
												'Radio button label 1' => 1,
												'Radio button label 2' => 2,
											),
                				'default' => 0 # The option selected by default (identified by value)
                			),
            'textfield1' => array(
								'label' => 'textfield 1 label', # What's the label of the field
								'class' => 'HTMLTextField' # What's the input type
								),
			'textfield2' => array(
					'label' => 'textfield 2 label', # What's the label of the field
					'class' => 'HTMLTextField' # What's the input type
					), 				
 		);
		
		switch($formOpts){
			case '1':
				$formDescriptor['textfield1']['label'] = 'textfield 1 label'; # What's the label of the field
				$formDescriptor['textfield2']['label'] = 'textfield 2 label'; # What's the label of the field	
				break;
			case '2':
				#same as case 1
			default:
		}

		#Submit button structure and page callback. 
        $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'myform'); # We build the HTMLForm object, calling the form 'myform'
        $htmlForm->setSubmitText( 'Submit button text' ); # What text does the submit button display
		
		/* We set a callback function */ 
		#This code has no function to the special page. It used to produce red wiki text callback such as "Try Again" commented-out below under processInput function.  
		$htmlForm->setSubmitCallback( array( 'specialpagetemplate', 'processInput' ) ); # Call processInput() in specialpagetemplate on submit
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
        $html .= 'Brief table explanation for users.'; #html text displayed with the table
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
				$headings = array('column 1 heading','column 2 heading','column 3 heading');
				$html .= self::make_table_header($headings); 
	 
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($results as $x){
					$html .= "<tr><td>$x->database_query_column_1</td><td>$x->database_query_column_2</td><td>$x->database_query_column_3</td></tr>";
				}	
				break;	
        	case '1':
				#same as case 0
				break;	
			case '2':
				#same as case 0
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
		This databrowser queries data from this paper. <ref name='PMID:########'/>
		
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