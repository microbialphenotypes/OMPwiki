<?php
/*
Column rule to calculate genotype and phenotype differences from conditions.

Jim Hu 6/10/2014 - started. .

*/

# the class name must be ecTableEdit_ followed by the name of the column rule
class ecTableEdit_omp_anno extends TableEdit_Column_rule{

	function make_form_row(){
		$this->col_data = str_replace(array("Relative to:", "<br />", "<hr>"), '', $this->col_data);
		list($rel_anno_id) = explode("\n", $this->col_data);
		$rel_anno_id = trim($rel_anno_id);		
		$form = TableEditView::form_field($this->col_index, $rel_anno_id, 40,'text');
		$form .= $this->comparisons($rel_anno_id);
		return $form;
	
	}
	
	function comparisons($rel_anno_id){
		$str = '';
		# genotype
		$myGenotype = $this->getGenotype('self');
		$relGenotype = $this->getGenotype($rel_anno_id);
		$str .= $this->compareProperties('Genotype', $myGenotype, $relGenotype, $rel_anno_id);

		# conditions
		$myConditions = $this->getConditions('self');
		$relConditions = $this->getConditions($rel_anno_id);
		$str .= $this->compareProperties('Condition', $myConditions, $relConditions, $rel_anno_id);	
		return $str;
	}

	# overload to make the data a list
	function show_data(){
		$this->col_data = str_replace("Relative to:", '', $this->col_data);
		list($rel_anno_id) = explode("\n", $this->col_data);
		$rel_anno_id = trim($rel_anno_id);
		# get condition differences
		$str = "Relative to: $rel_anno_id\n";
		$str .= $this->comparisons($rel_anno_id);
		return $str;
	}
	
	function getRelativeAnnotation($annotation_id){
		$annotation_id = trim($annotation_id);
		$dbr =& wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			array('ext_TableEdit_box', 'ext_TableEdit_row'),
			array('*'),
			array( 
				'ext_TableEdit_box.box_id = ext_TableEdit_row.box_id',
				"template ='".$this->box->template."'",
				"row_data LIKE '$annotation_id||%'"			
			)
		);
		#echo "<p>".$dbr->lastQuery()."</p>";
		return $result;	
	}

	function getRelativeStrain($page_uid = ''){
		if ($page_uid == ''){
			return array();
		}
		$dbr =& wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			array('ext_TableEdit_box', 'ext_TableEdit_row'),
			array('*'),
			array( 
				'ext_TableEdit_box.box_id = ext_TableEdit_row.box_id',
				"template ='Strain_info_table'",
				"page_uid = '$page_uid'"			
			)
		);
		return $result;	
	}
	
	
	
	function getGenotype($annotation_id){
		# get a page_uid for where the annotation lives
		# initialize page_uid
		$page_uid = '';
		switch($annotation_id){
			case 'self':
				$page_uid = $this->box->page_uid;
				break;
			default:
				# look it up in the database
				$result = $this->getRelativeAnnotation($annotation_id);
				# should execute only once, but loop it anyway.
				foreach ($result as $i => $x){
					$page_uid = $x->page_uid;
				}
		}
		# get the genotype from the strain info table
		# initialize an empty array
		$genotype = array();
		$result = $this->getRelativeStrain($page_uid);
		# should execute only once, but loop it anyway.
		foreach ($result as $x){
			#echo $x->row_data;
			$box = new wikiBox($x->box_uid);
			$box->set_from_DB();
			$row_data = explode('||', $x->row_data);
			foreach ($box->column_names as $i => $name){
				$value = "";
				if(isset($row_data[$i])){
					$value = $row_data[$i];
				}	
				$row_hash[$box->column_names[$i]] = $value;
			}
		#	echo "<pre>row_hash\n";print_r($row_hash);echo "</pre>\n";

			$c = trim(str_replace(":\n",":", $row_hash['genotype']),"\n*");
			$c = str_replace("\n*", "\n", $c);
			$genotype = preg_split("/[\s,]+/", $c);
		}
		# convert to hash
		$hash = array();
		foreach ($genotype as $allele){
			$allele = trim($allele);
			list($key, $val) = explode(':', "$allele:");
			if($val != ''){ 
				$hash[$key][] = $allele;
			}else{	
				# for unprefixed allele names before we assign accessions
				$hash[$key][] = $key;
			}	
		}
		return $hash;
	}
	
	
	function getConditions($annotation_id){
		$conditions = array();
		switch($annotation_id){
			case 'self':
				# the conditions in the same row.
				$c = trim(str_replace(":\n",":", $this->row_hash['condition']), "\n*");
				$c = str_replace("\n*", "\n", $c);
				$conditions = explode("\n", $c);
				break;
			default:
				# look it up in the database
				$result = $this->getRelativeAnnotation($annotation_id);
				# should execute only once, but loop it anyway.
				foreach ($result as $x){
					#echo $x->row_data;
					$row_data = explode('||', $x->row_data);
					foreach ($this->box->column_names as $i => $name){
						$value = "";
						if(isset($row_data[$i])){
							$value = $row_data[$i];
						}	
						$row_hash[$this->box->column_names[$i]] = $value;
					}
					$c = trim(str_replace(":\n",":", $row_hash['condition']),"\n*");
					$c = str_replace("\n*", "\n", $c);
					$conditions = explode("\n", $c);
				}
		}
		# convert to hash
		$hash = array();
		foreach ($conditions as $condition){
			list($key, $val) = explode(':', $condition.":");
			$hash[$key][] = $val;
		}
		return $hash;
	}
	
	function compareProperties($type, $myProps, $relProps, $rel_anno_id){
		if($rel_anno_id != ''){
			#echo "$type<pre>self\n";print_r($myProps);echo "\n";
			#echo "foreign";print_r($relProps);echo "</pre>";
			$diff = array();
			# find where mine are different from rel
			# if same, unset.
			foreach ($myProps as $key => $arr){
				foreach ($arr as $i => $val){
					if(isset($relProps[$key]) && is_array($relProps[$key]) && in_array($val, $relProps[$key])){
						unset($myProps[$key][$i]);
						$relKeys = array_keys($relProps[$key], $val); 
						foreach($relKeys as $relKey){
							unset($relProps[$key][$relKey]);
						}
					}else{
						$diff[$key]['mine'][] = "$val";
					}
				}
			}
			# find anything left in rel
			foreach ($relProps as $key => $arr){
				foreach ($arr as $i => $val){
					$diff[$key]['rel'][] = $val;
				}
			}
			#echo "<pre>diff";print_r($diff);echo "</pre>";
			$str = '';
			if(empty($diff)){
				$str = 'no differences';
			}else{
				switch ($type){
					case 'Genotype':
						$str .= $this->genotypeDiffs($diff);
						break;
					case 'Condition':
						$str .= $this->conditionDiffs($diff);
						break;
				}
			}
		}else{
			$str = "Nothing to compare<br/>";
		}	
		return "\n<hr>$type differences:<br>\n".$str;
	}
	
	function conditionDiffs($diff){
		$str = '';
		foreach ($diff as $key => $d){
			$str .= "$key:";
			if(empty($d['mine'])){
				$str .= '?';
			}else{
				$str .= implode(';',$d['mine']);
			}
		#	$str .= " vs $rel_anno_id ";
			if(empty($d['rel'])){
				$str .= '?';
			}else{
				$str .= implode(';',$d['rel']);
			}
			$str .= "\n";
		}
		return trim($str);
	}
	
	function genotypeDiffs($diff){
		$str = '';
		foreach ($diff as $key => $d){
			if(empty($d['mine'])){
				$str .= '';
			}else{
				$str .= "+".implode(' +',$d['mine']);
			}
			$str .= " ";
			if(empty($d['rel'])){
				$str .= '';
			}else{
				$str .= '-'.implode('-',$d['rel']);
			}
		}
		$str .= "";
		return trim($str);
	}
}