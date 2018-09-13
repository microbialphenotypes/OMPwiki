<?php
/* 
Authors: Sandra LaBonte
Last revised 8/18/2017
*/

/* We are creating a new class called Myspecialpage. 
This new class is going to extend the properties and methods of a parent class called SpecialPage. */
class ECGAspecialpage extends SpecialPage {					
	function __construct() {
		parent::__construct( 'ECGA' );
	}

	#These are the main special page executions. 
	function execute( $par ) {
		$embargo = true;
		# get values passed from form inputs
		$request = $this-> getRequest();  																					
		$formOpts = $request->getText('wpradioForm');  																		
		
		# get an Output object and set page title 																	
		$out = $this->getOutput();  																						
		$out->setPageTitle( 'E. coli-to-Cancer Gene-function Atlas (ECGA)' );
		
		#verify the login before they can see the page
		$user = $this->getUser();
		if ($embargo && !$user->isLoggedIn()){
			throw new ErrorPageError( 'ecga','ecga-embargo' );

			return false;
		}		 
		#The page changes html textfield form after each radio button request
		$text = '<script>$(\'input[type=radio]\').on(\'change\', function() {$(this).closest(\'form\').submit();});</script>';		
		
		# do the query and make a table
		$results = $this->doRequest($request, $formOpts);  
		$text .= $this->renderTable($results, $request);  																	

		# add jQuery dataTables javascript and css stolen from TableEdit (requires TableEdit also be installed)
		$out->addModules('ext.TableEdit'); 
		
		$out->addWikiText(self::before());
		# build the page content
		# make the form
		$this->makeForm($request, $formOpts);  																								                   
		#$text .= "Please select a search type, enter your query in one or both text fields and click the 'Submit' button.";  	 
		$out->addHTML($text);
		$out->addWikiText(self::after());																								
	}		

	/* Querying phpMyAdmin database based on the radioform selection type. 
	
		This is an initial query to get the gene ids
	
	*/
	    
	function doRequest($request, $formOpts){
		$tables = array('ECGA.gene','ECGA.gene_data', 'ECGA.taxa');
		$conds = array("ECGA.gene.gene_id = ECGA.gene_data.gene", "ECGA.gene.taxon_id = ECGA.taxa.taxon_id"); 
		
		#echo "<pre>";print_r ($formOpts); echo "</pre>";
		
		switch($formOpts){
 			#Each case specifies where to query what data
 			default:
 			case '0':				
				$requesttext1 = $request->getText('wptextfield1'); 
				if ($requesttext1 != ''){	
					#Database ID's in column1a can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 1. 
					$conds[] = "gene_name IN ('".implode("','", self::getdatabaseIDs($requesttext1))."')";
				}		
				break;
			case '1':
				$requesttext2 = $request->getText('wpselect');  
				if ($requesttext2 != ''){	
					#Database ID's in column1a can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 1. 
					$conds[] = "data LIKE '%".$requesttext2."%'";
				}	
				break;		
			case '2':				
				$requesttext2 = $request->getText('wptextfield1');  
				if ($requesttext2 != ''){	
					#Database ID's in column1a can be recognized by column1c, column2c, or column3c inputs. Input is from html textfield 1. 
					$conds[] = "data LIKE '%".$requesttext2."%'";
				}
				break; 	
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
	 
	static function getdatabaseIDs($text){
		$dbID = array();
		$dbr = wfGetDB( DB_SLAVE ); #doing a database query inputing column1c or column2c to get column3c output. 
		$results = $dbr->select(
						'ECGA.gene',
						'*',
						"gene_name LIKE '%$text%'",
						__METHOD__
					);	
	#	echo $dbr->lastQuery();
		foreach($results as $x){
			$dbID[] = $x->gene_name;
		}
		return $dbID;			
	}
	/*
	Creates checkboxes, three button radio form and text fields on special page. 
	
	When the second radio button is selected a "select field" will show up with phenotype options to choose 
	*/
	function makeForm($request, $formOpts){
		
		
		$formDescriptor = array(  
					      
			'information'	=> array(
							'type' => 'info',
							'label' => 'Choose which species you would like to search by:',
							),										
			'Ecoli'    => array(
            				'type' => 'check',
                			'label' => 'E. coli',
                			'default' => true,
            				),
            'Human'    => array(
            				'type' => 'check',
                			'label' => 'Human',
                			),
            'information2'	=> array(
							'type' => 'info',
							'label' => 'Choose what you would like to see in the table:',  
							), 				
            'Product'    => array(
            				'type' => 'check',
                			'label' => 'Gene Product:',	
                			'default' => true,
            				),
            'DDP_Phenotypes'    => array(
            				'type' => 'check',
                			'label' => 'DNA Damage Protein Phenotypes:',
                			'default' => true,
                			),	
            'Function'    => array(
            				'type' => 'check',
                			'label' => 'Gene Function:',
                			'default' => true,	
            				),
    		'Gene_Homologs'    => array(
            				'type' => 'check',
                			'label' => 'Gene Homologs:',
                			'default' => true,	
            				),		
            'Cluster'    => array(
            				'type' => 'check',
                			'label' => 'Gene Cluster:',	
                			'default' => true,
            				), 				
            'Mutation'    => array(
            				'type' => 'check',
                			'label' => 'Normalized mutation rate:',	
                			'default' => true,
            				),
             'Localization'    => array(
            				'type' => 'check',
                			'label' => 'Subcellular localization of N-terminal GFP fusion:',	
            				),
             'Copy_Number'    => array(
            				'type' => 'check',
                			'label' => 'Copy-number increase in cancers?',	
            				),								
            			 												
			'radioForm'	=> array(
								'type'=> 'radio',
								'label' => 'Select what you like to search by:',
								'options' => array(
											'Gene Name' => 0,
											'Phenotype' => 1, 
											'Functional Class' => 2,
											),	
								'default' => 0				
								),	        																		
 		);
 		
 		switch($formOpts){
 			case '0':
 				$formDescriptor['textfield1']['label'] = 'Gene Name'; # What's the label of the field
 				$formDescriptor['textfield1']['class'] = 'HTMLTextField'; # What's the label of the field
 				break;
			case '1':
				$formDescriptor['select']['label'] = 'Phenotype'; # What's the label of the field
				$formDescriptor['select']['class'] = 'HTMLSelectField';
				$formDescriptor['select']['options'] = array(
														'Phleo' => Phleo,
														'MMC' => MMC,
														'H2O2' => H2O2,
														'ROS' => ROS,
														'AC' => AC,
														'RF' => RF,
														'DSB' => DSB,
														);
				$formDescriptor['select']['default'] = 'Phleo';	
				$formDescriptor['info'] = array(
                		'type' => 'info',
                		'label' => 'Phenotypes* of DDP overproducing strains:',
               			'default' => '		

			<br /><b>DSB</b>: increased frequency of DNA double-strand breaks(OMP:0007614) in single cells measured as GamGFP foci (<a href= https://elifesciences.org/articles/01222>PMID:24171103</a>) <br /> 

			<b>RF</b>: increased frequency of Holliday-junction structures in rec-A-deletion cells(OMP:0007621). These structures can result from increased stalled, reversed DNA replication forks, and are measured as foci of RuvCDefGFP(RDG) in recA-deletion cells (<a href=http://advances.sciencemag.org/content/2/11/e1601605>PMID:28090586</a>)<br />

			<b>AC</b>: increased frequency of cells that have lost DNA(DNA loss)(OMP:0005061) measured as the % anucleate cells in cultures by flow cytometry. <br /> 

			<b>ROS</b>: increased levels of reactive oxygen-species in single cells measured with dihydrorhodamine dye and flow cytometry (OMP:0007661)<br /> 

			<b>H2O2</b>: increased sensitivity to hydrogen peroxide (OMP:0007249 + CHEBI:16240), which implies reduction in the ability to perform DNA repair by base-excision repair<br />

			<b>MMC</b>: increased sensitivity to the inter-strand DNA cross-linking agent mitomycin C (OMP:0007654 + CHEBI:27504), which implies reduction in the ability to perform DNA repair by nucleotide-excision repair and/or homology-directed repair<br />

			<b>Phleo</b>: increased sensitivity to Phleomycin (OMP:0007173 + CHEBI:75044), which implies reduction in the ability to perform DNA repair by homology-directed repair<br />

			*Phenotypes shown represent a >2-fold difference (p < 0.05, q <0.10) from the vector only control strain. (unpaired two-tailed t-test with FDR adjustment) 
			for the above assays, clones were considered significantly different from vector only controls with p < 0.05, q <0.10 (unpaired two-tailed t-test with FDR adjustment).
',  # String in field
                		'raw' => true # if true, the above string won't be html-escaped.
            );						
				break;
			case '2':
				$formDescriptor['textfield1']['label'] = 'Function'; # What's the label of the field
				$formDescriptor['textfield1']['class'] = 'HTMLTextField';
				break;
			default:
		}
		
		#Submit button structure and page callback. 
        $htmlForm = new HTMLForm( $formDescriptor, $this->getContext(), 'myform'); # We build the HTMLForm object, calling the form 'myform'
        $htmlForm->setSubmitText( 'Submit' ); # What text does the submit button display
		
		/* We set a callback function */ 
		#This code has no function to the special page. It used to produce red wiki text callback such as "Try Again" commented-out below under processInput function.  
		$htmlForm->setSubmitCallback( array( 'ecgaspecialpage', 'processInput' ) ); # Call processInput() in specialpagetemplate on submit
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
		
	#this renders the table 
	protected function renderTable($results, $request) {    
		$parser = new Parser;   
		$title = $this->getPageTitle(); 
		$parserOptions = new ParserOptions($this->getUser());
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
        	echo "<pre>";print_r ($formOpts); echo "</pre>";
        	
        	#first we need to get the headers for the table based on which check boxes are selected
        	$all_headings = array(
        		'wpProduct' => 'Product', 
        		'wpDDP_Phenotypes'=>'DDP Phenotypes', 
        		'wpFunction' =>'Function',
        		'wpGene_Homologs'=>'Gene Homologs',
        		'wpCluster'=>'Cluster',
        		'wpMutation' => 'Normalized mutation rate',
        		'wpLocalization' => 'Subcellular localization of N-terminal GFP fusion',
        		'wpCopy_Number' => 'Copy-number increase in cancers?'
        		);
        		
				$headings = array('Gene');
					
					#array_push is used to add a column to the end of the table, for default, all checkboxes are displayed(second request-> gettext)	
					foreach($all_headings as $k => $h){
						if($request->getText($k) || $request->getText('wpradioForm') == '' ){
							array_push($headings, $h);
						}
					}
					#echo "<pre>";print_r ($headings); echo "</pre>";
	
				$html .= self::make_table_header($headings); 
				//get the array of genes to display
				$genes = array();
				foreach($results as $x){
					$genes[] = $x->gene;
				}
				/* Query to set up the taxa, will be more useful when other taxa are added. 

				*/
				$taxa = array(
					'wpEcoli' => 562,
					'wpHuman' => 9606,
				);
				#set up conditions to find all genes 
				$conds = array(
					'gene_id = gene',
					"gene IN ('".implode("','", array_unique($genes))."')",
                );
                #just want genes of specified taxa 
				foreach($taxa as $t => $e){
						if($request->getText($t)){
							#echo "taxon: $t \n";
								$taxonconds[] = "taxon_id = '".$taxa[$t]."'";
						}
					}
				#if multiple taxa are selected, makes it an OR instead of AND statement	
				if (!empty($taxonconds)){
					$conds[] = implode(' OR ', $taxonconds);
				}else{
					#set default taxa to e. coli
					$conds[] ="taxon_id = '".$taxa['wpEcoli']."'";
				}
				#echo "<pre>";print_r ($taxonconds); echo "</pre>";
				
				$dbr = wfGetDB( DB_SLAVE );
				$dataresults = $dbr->select(
					array('ECGA.gene','ECGA.gene_data'),
					'*',
					$conds,
					__METHOD__
				);	
				#echo $dbr->lastQuery();
				
				//sort the triples into fields
				$gene_data = array();
				foreach($dataresults as $g){
					$gene_data[$g->gene_name][$g->data_type][] = $g->data; 
				}
	 				
				// make the body
				$html .= Xml::openElement( 'tbody' );
				foreach($gene_data as $gene => $data){
					$html .= "<tr><td>$gene</td>";
					foreach($headings as $key){
						switch ($key){
							case "DDP Phenotypes":
								$html .="<td>".implode(', ', $data['phenotype_qual'])."</td>";
								break;
							case "Function":
								$html .="<td>".implode(', ', $data['function'])."</td>";
								break;
							case "Product":
								$html .="<td>".implode(', ', $data['gene_product'])."</td>";
								break;	
							case "Cluster":
								$html .="<td>".implode(', ', $data['cluster'])."</td>";
								break;	
							case "Gene Homologs":
								$html .="<td>".implode(', ', $data['homologs'])."</td>";
								break;	
							case "Normalized mutation rate":
								$html .="<td>".implode(', ', $data['mutation_rate'])."</td>";
								break;	
							case "Subcellular localization of N-terminal GFP fusion":
								$html .="<td>".implode(', ', $data['localization'])."</td>";
								break;
							case "Copy-number increase in cancers?":
								$html .="<td>".implode(', ', $data['copynumber'])."</td>";
								break;			
						}
						
					}
					$html .= "</tr>";
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
		This data browser queries data from\n
Jun Xia*, Li-Ya Chiu*, Ralf B. Nehring, María Angélica Bravo Núñez, Qian Mei, Mercedes
Perez, Yin Zhai, Devon M. Fitzgerald, John P. Pribis, Yumeng Wang, Chenyue W. Hu, Reid T.
Powell, Sandra A. LaBonte, Ali Jalali, Meztli L. Matadamas Guzmán, Alfred M. Lentzsch,
Adam T. Szafran, Mohan C. Joshi, Megan Richters, Janet L. Gibson, Ryan L. Frisch, P.J.
Hastings, David Bates, Christine Queitsch, Susan G. Hilsenbeck, Cristian Coarfa, James C. Hu,
Deborah A. Siegele, Kenneth L. Scott, Han Liang, Michael A. Mancini, Christophe Herman§,
Kyle M. Miller§ &amp; Susan M. Rosenberg§，Bacteria-to- human protein networks reveal origins of
endogenous DNA damage. submitted manuscript

		Shee et al. 2013 eLIFE <ref name='PMID:24171103'/>
		Xia et al. 2016 Science Advances <ref name='PMID:28090586'/>
		<references/>
			
		\n== Search ==
		Select which fields you would like to display:
		'''Note:'''
		 Only the Ecoli genes are clustered and have mutation rates.
		 Only the Human genes have Subcellular localization of N-terminal GFP fusion and Copy-number increase in cancers
		
		"
		#<ref name='PMID:########'/>
		;
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
			if ($formData['textfield1'] == '') {
				#return "Try again"; 
			}
	}		
} # close Class 