<?php
/*
Class to manage obtaining accessions from external omp database.
*/
class OMPaccessions{

	public static function BeforeRowSave($row){
		$template = self::determineTemplate($row);
		$data = explode('||', $row->row_data);
		switch($template){
			case 'OMP_annotation_table':
				$dbTable = 'annotation';
				break;
			default:
				return true;	
		}
		$data[0] = self::getNewAccession($dbTable, $row); #echo "Here:".print_r($data, true);
		$row->row_data = implode('||', $data);
		return true;
	}



	public static function AfterRowSave($row){
		return true;
	}
	
	/*
	Save a new annotation and return the accession
	*/
	static function getNewAccession($dbTable, $row){
		
		switch ($dbTable){
			case 'annotation':
				$a = OMPannotation::newFromTableEditRow($row);
				$a->save();
				return $a->accession;
				break;
			
		}
		

	}
	
	static function determineTemplate($row){
		$box = wikiBox::newFromBoxId($row->box_id);
		return $box->template;
	}
	


}