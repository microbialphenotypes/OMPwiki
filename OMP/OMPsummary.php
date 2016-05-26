<?php

/*
OMPsummary

Collect info from phenotype table and redisplay

The number of rows is too large to display in dataTables, so we can do summary info.


*/

$wgExtensionFunctions[] = "efOMPsummaryExtension";
$wgHooks['ResourceLoaderRegisterModules'][] = 'OMPsummary::RegisterModules';
$wgHooks['BeforePageDisplay'][]  = 'OMPsummary::AddHeadThings';

function efOMPsummaryExtension() {
	global $wgParser;
	$wgParser->setHook( "ompsummary", "OMPsummary::renderOMPsummary" );
	return true;
}
class OMPsummary{
	public static function renderOMPsummary( $paramstring, $argv, $parser, $frame ){
		$params = array();
		$paramLines = explode("\n", $paramstring);
		foreach ($paramLines as $paramLine){
			list($key, $val) = explode("=", $paramLine);
			if(trim($key) != '') $params[$key] = $val;
		}
		$text = print_r($params, true);
		switch($params['template']){
			case 'PMID_Phenotype_table':
				$text = self::pmidPhenotypeTable($parser);
				break;
			default:
				$text = self::phenotypeTable2($parser, $frame);	
		}
		return $text;
	}
	
	static function phenotypeTable2($parser, $frame){
		global $wgUser;
		$dbr =& wfGetDB( DB_SLAVE );
		$where = array(
			"ext_TableEdit_box.template = 'Phenotype_Table_2'",
			"ext_TableEdit_box.box_id = ext_TableEdit_row.box_id",
			"page_name LIKE  '%PMID:%'",
			"row_data LIKE  '%||OMP:%'"
		);	
		$result = $dbr->select(
			array('ext_TableEdit_box','ext_TableEdit_row'),
			'*',
			$where,
			__METHOD__
		);
		$tableWikiText = "\n\n{|{{Prettytable}} class='dataTable OMP_summary'\n|-bgcolor='#CCCCFF'\n!Page!!";
		foreach ($result as $i => $x ){
			$box = wikiBox::newFromBoxId($x->box_id);
			if($i == 1){
				$box->set_headings_from_template();
				$headings = explode("\n", $box->headings);
				$tableWikiText .= implode("!!",$headings)."\n";
	
			}
			foreach($box->rows as $row){
				if($x->row_id == $row->row_id){
					$rdata = str_replace("||","\n|\n",$row->row_data);
					$tableWikiText .= "\n|-\n|[[$x->page_name]]||$rdata\n";
				}
			}
		
		}
		$tableWikiText .= "\n|}\n";
		$tableWikiText = TableEditOMPLinker::obo_links($tableWikiText);
		$output = $parser->recursiveTagParse($tableWikiText, $frame);
		$output = TableEditTableMarkerUpper::do_header($output);
		return $output;
	
	}
	
	static function pmidPhenotypeTable($parser){
		global $wgOut, $wgTitle, $wgScriptPath, $wgServer, $wgSitename;
		return "";
		/*
		This original table has problems with tables that have been deleted. So let's kill it for now.
		*/
		
		$text = "Hello world!";
		if ( !isSet($wgTitle) ) {
			return true;
		}
		# kill caches
		$parser->disableCache();
		$wgOut->setSquidMaxage( 30 ); 		# Cache for .5 minutes only
		$wgOut->enableClientCache(false);

		#gather data
		$dbr =& wfGetDB( DB_SLAVE );
		$where = array(
			"ext_TableEdit_box.template = 'PMID_Phenotype_table'",
			"ext_TableEdit_box.box_id = ext_TableEdit_row.box_id"
		);	
		$result = $dbr->select(
			array('ext_TableEdit_box','ext_TableEdit_row'),
			'*',
			$where,
			__METHOD__
		);
		$text = "<table border = 1 class = 'dataTable OMP_summary' width = '100%'>
		<thead>
		<th>Item</th>
		<th>Page</th>
		<th>Organism</th>
		<th>Taxid</th>
		<th>Strain</th>
		<th>Gene</th>
		<th>OMP</th>
		<th>Phenotype</th>
		<th>Details</th>
		<th>Evidence</th>
		<th>Notes</th>
		</thead>
		<tbody>";
		$i = 0;
		while( $x = $dbr->fetchObject ( $result ) ){
			$data = explode("||",$x->row_data);
			$i++;
			$text .= "<tr><td><a name='$i'>$i</td><td><a href='$wgServer$wgScriptPath/index.php/".$x->page_name."'>".$x->page_name."</a></td><td>".implode("</td><td>",$data)."</td></tr>";
		#	if ($i > 20) break;
		}
		$text .= "</tbody></table>";
		return $text;
	}
	
	public static function RegisterModules( $resourceLoader ) {
		global $wgResourceModules;
		$wgResourceModules['ext.OMPsummary'] = array(
		#	'scripts' => array('js/jquery.dataTables.js', 'js/init_datatables.js'),
		#	'styles' => array('css/main.css'),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'OMP'
		);
		return true;
	}


	public static function AddHeadThings( OutputPage &$out, Skin &$skin){
		$out->addModules( 'ext.OMPsummary' );
		return true;

	}
}