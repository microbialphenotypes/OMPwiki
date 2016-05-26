<?php
class OMPmodel{
	public function __construct($id = null){
	
	}

	public static function getRowDataHash($row){
		$box = wikiBox::newFromBoxId($row->box_id);
		$row_hash = array();
		$data = explode('||', $row->row_data);
		foreach ($box->column_names as $i => $name){
			$value = "";
			if(isset($data[$i])) $value = $data[$i];
			$row_hash[$box->column_names[$i]] = $value;
		}
		return $row_hash;
	}
		
	public function time( $unix_timestamp = null ) {
        wfProfileIn( __METHOD__ );
		if ( is_null($unix_timestamp) ) {
			$unix_timestamp = time();
		}
        wfProfileOut( __METHOD__ );
		return date( 'YmdHis', $unix_timestamp );
	}
}