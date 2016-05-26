<?php
/*
 * OMPTableEditCategoryTags - An extension 'module' for the TableEdit extension.
 * @author Jim Hu (jimhu@tamu.edu)
 * @version 0.11
 * @copyright Copyright (C) 2013 Jim Hu
 * @license The MIT License - http://www.opensource.org/licenses/mit-license.php
 
 Started as a clone of EcoliWikiTableEditCategoryTags 
 
 */

if ( ! defined( 'MEDIAWIKI' ) ) die();

# Credits
$wgExtensionCredits['other'][] = array(
    'name'			=>'OmpWikiTableEditCategoryTags',
    'author'		=> array(
		'Jim Hu &lt;jimhu@tamu.edu&gt;',
	),
    'description'	=>'Add category tags for table entries for TableEdit in Omp.',
    'version'		=>'0.11'
);


class OMPtableEditCategoryTags extends TableEditCategoryTags{

	public static function add_tags(TableEdit $te, $table, wikiBox $box){		
		# make sure tags are only added once
		if(strpos($table, "<!-- OMP category tags -->") > 1){ 
			return true;
		}	
		$tagarr = self::do_taglist($table, $box);
		$table .= "<noinclude>\n<!-- OMP category tags -->".implode("\n",array_unique($tagarr))."\n</noinclude>\n";
		return true;
	}

	public static function do_taglist($table, $box){
		$tagarr = parent::do_taglist($table, $box); 
		$lc = LookupCollection::getInstance();
		switch($box->template){
			case 'Strain_info_table':
				$row_hash = $box->get_row_hash(0);
				$tagarr = self::tagParentStrains($tagarr, $row_hash);
				# check whether this strain page is accessioned
				if(strpos(" $box->page_name", "OMP_ST") != 1 ){
					$tagarr[] = "[[Category:Strains that need OMP_ST accessions]]";
				}
				break;		
		}
		return $tagarr;
	}	
	
	public static function tagParentStrains($tagarr, $row_hash){
		$ancestors = explode("\n", str_replace(":\n", ':', trim($row_hash['ancestry']))); #print_r($ancestors); die;
		$sortedGenotype = self::sortGenotype($row_hash['genotype']);
		foreach ($ancestors as $ancestor){
			list($type, $name) = explode(':', $ancestor, 2); 
			$name = trim($name, '[]');
			if($type == 'parent'){
				$t = Title::newFromText($name);
				if($t->isKnown()){
					$tagarr[] = "[[Category:$name derivatives]]";
					# check genotype of parent vs new strain
					$parentPage = new WikiPageTE($t);
					$parentInfo = $parentPage->getTable('Strain_info_table');
					$parentRowHash = $parentInfo[0]->get_row_hash(0); 
					$sortedParent = self::sortGenotype($parentRowHash['genotype']);
					if ($sortedParent == $sortedGenotype){
						$tagarr[] = "[[Category:Strains with genotype identical to parent]]";
					}
				}
			}
		}
		return $tagarr;
	}
	
	public static function sortGenotype($str){
		$c = trim(str_replace(":\n",":", $str),"\n*");
		$c = str_replace("\n*", "\n", $c);
		$array = preg_split("/[\s,]+/", $c);
		sort($array);
		return implode(' ', $array);
	}
	
}

