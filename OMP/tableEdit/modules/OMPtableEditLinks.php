<?php
class TableEditOMPLinker extends TableEditLinker{
	
	function generic_links($table){
		$table = self::pmid_links($table);
		$table = self::gonuts_links($table);
		$table = self::obo_links($table);
		$table = self::go_ref_links($table);
		$table = self::dbxref_links($table);
		return $table;
	}
	
	
	function table_specific_links($table, $template = ''){
		# rows in this context are not wikiBoxRow objects; they're just wikitext inside the table delimited by "\n|-"
		$newrows = array();
		$oldrows = explode("\n|-",$table);

		// ==== table-specific regexes ============================	
		switch ($template){
			case "Strain_info_table":
				foreach($oldrows as $i => $row){
					if(strpos($row, 'Genotype') > 0){
						$row = str_replace("\n*",' ',trim($row,"*"));
					}
					if(strpos($row, 'Ancestry') > 0){
						# strip existing wikitext links
						$row = str_replace(array('[[',']]'),'' ,$row);
						# merge type:strain into one field
						$row = str_replace(":\n",':' ,$row);
						list($label, $straininfo) = explode("||", "$row||");
						$strains = explode("\n", $straininfo); 
						foreach($strains as $strain){
							if($strain == '') continue;
							if(strpos($strain, ':') > 0){
								list($type, $strainName) = explode(':', "$strain", 2); 
							}else{
								# this should not happen (all entries should be prefixed) but just in case
								$strainName = $strain;
							}
							$t = Title::newFromText($strainName);
							if(is_a($t, 'Title') && $t->isKnown()){
								$row = str_replace(":$strainName", ":[[$strainName]]", $row);
							}	
						}
					}
					
					
					$newrows[] = $row;
				}
				break;
			case 'OMP_annotation_table':
				$dbr = wfGetDB( DB_SLAVE );
				foreach($oldrows as $i => $row){
					#echo "<pre>$row</pre>";
					preg_match('/Relative to:\s*(OMP_AN:\d+\b)/U', $row, $annotationIDs);
					if(isset($annotationIDs[1])){
						$omp_an =  $annotationIDs[1];
						$result = $dbr->select(
							array('page as p', 'ext_TableEdit_box as b', 'ext_TableEdit_row as r'),
							'*',
							array(
								"b.box_id = r.box_id",
								"r.row_data LIKE '$omp_an||%'",
								"p.page_id = b.page_uid",
								"page_namespace IN (0,14)"
								),
							__METHOD__
						);
						foreach ($result as $x){
							#echo "$omp_an $x->page_title $x->page_namespace";
							$page_name = $x->page_title;
							if($x->page_namespace == 14){
								$page_name = ":Category:$page_name";
							}
							$row = str_replace("$omp_an", "[[$page_name|$omp_an]]", $row); #echo $row;
						}
					}
					$newrows[] = $row;
				}	
				
				break;	
/*			case "Annotation_Headings":
				// IEA rows
				$rows = array();
				$tmp = explode("\n|-",$table);
				foreach ($tmp as $row){
					if (strpos($row,"\nIEA:") > 0) $row = "style='background:#ddffdd;' ".$row;
					$rows[] = $row;
				}
				$table = implode("\n|-",$rows);					
				break;
*/				
			default:
				$newrows = $oldrows;
		} # end switch	
		$table = implode("\n|-",$newrows);					
		return $table;
	}

	public static function obo_links($table){
		# Do links to OMP
		$table = self::do_obo_replacements( "/OMP:\d+/", $table);
		$table = self::do_obo_replacements( "/ECO:\d+/", $table);
		return $table;
	}
	
	static function do_obo_replacements($pattern, $table){
		$stripped_table = preg_replace('/\[.*\]/','', $table);
		preg_match_all($pattern, $stripped_table, $matches);
		foreach (array_unique($matches[0]) as $match){
			$page = str_replace(" ","_", self::get_obo_term($match));	
			# only link if term is a page. 
			$t = Title::newFromText($page, NS_CATEGORY);
			if(is_a($t,'Title') && $t->isKnown()){
				$replacement = "[[Category:$page]][[:Category:$page|$match]]";
				$table = preg_replace("/([\|\s])$match/", "$1$replacement", $table);
			}
		}	
		return $table;
	}
	
	static function get_obo_term($match){
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			'page',
			'*',
			array(
				"page_namespace = '14'",
				"page_title LIKE '$match%'",
				"page_is_redirect = '0'"
				),
			__METHOD__
		);
		foreach ($result as $x){
			return $x->page_title; 
		}
	}
# end class
}