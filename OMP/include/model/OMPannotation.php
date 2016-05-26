<?php
/*
OMP annotation model and interface to OMP_master database

*/
class OMPannotation extends OMPmodel{

	public $id;
	public $strain = '';
	public $accession = '';
	public $omp_id = '';
	public $not = false;
	public $reference = '';
	public $eco_id = ''; # evidence
	public $env_condition = ''; # environment
	public $date = '';
	public $submitted_by = '';
	public $notes = '';
	public $relations = array();
	public $xrefs = array();
	private $fields = array('strain','accession','omp_id','reference','eco_id','env_condition','submitted_by','notes');
	
	public function __construct( $annotation_id = null ) {
		parent::__construct();
		if ( !is_null($annotation_id) ) {
			$this->id = $annotation_id;
		}
		#MWDebug::log( __METHOD__ . ' called for annotation_id: ' . $this->id );
	}	

	public static function newFromID($id){
		$this->id = $id;
		$annotation = $this->load();		
	}
	
	public static function newFromTableEditRow($row){
		$annotation = new self;
		$rowHash = self::getRowDataHash($row); #print_r($rowHash); die;
		$annotation->id = str_replace('OMP_AN:','', $rowHash['annotation_id']);
		$annotation->load();
		# overwrite from table data
		$annotation->accession = $rowHash['annotation_id'];
		$annotation->ompId = $rowHash['omp_id'];
		$annotation->not = $rowHash['not'];
		$annotation->reference = $rowHash['reference'];
		$annotation->eco_id = $rowHash['eco_id'];
		$annotation->env_condition = $rowHash['condition'];
		$annotation->notes = $rowHash['notes'];
		return $annotation;			
	}
	
	public function load(){
		if($this->id == ""){
				$annotation = new self;
				return $annotation;			
		}
	    $dbr =& wfGetDB( DB_SLAVE );
		$conds = array("id = '$this->id'");
		$result = $dbr->select(
			'omp_master.annotation',
			'*',
			 $conds	
		);
		switch ( $dbr->numRows($result) ){
			case 0:
				$annotation = new self;
				return $annotation;
			case 1:
				$resultRow = $dbr->fetchObject( $result );
				$annotation = new self( $resultRow->id );
				foreach ($this->fields as $field){
					$annotation->$field = $resultRow->$field;
				}
				$annotation->not = $resultRow->notVal;
				return $annotation;
			default:
				trigger_error("more than one annotation found for id =".$rowHash['annotation_id']);	
				return false;
		}	
	}
	
	public function save(){
	    $dbr =& wfGetDB( DB_SLAVE );
		$conds = array("id = '$this->id'");
		$result = $dbr->select(
			'omp_master.annotation',
			'*',
			 $conds	
		);
		switch ( $dbr->numRows($result) ){
			case 0:
				$this->insert();
				break;
			case 1:
				$this->update();
				break;
			default:
				trigger_error("more than one annotation found for id =".$rowHash['annotation_id']);	
				return false;
		}	
	}
	function update(){
	    $dbw =& wfGetDB( DB_SLAVE );
		$conds = array('id = "$this->id"');
		foreach ($this->fields as $field){
			$values[$field] = $this->$field;
		}
		$values['accession'] = "OMP_AN:".$this->id;
		$values['dateVal'] = self::time();
		$updateStatus = $dbw->update(
			'omp_master.annotation',
			$values,
			$conds,
			__METHOD__
		);
	}

	function insert(){
		global $wgUser;
	    $dbw =& wfGetDB( DB_MASTER );
		$insertStatus = $dbw->insert(
                'omp_master.annotation',
                array(
                  #  'id'			=> null,
                    'accession'		=> $this->accession,
                    'strain'		=> $this->strain,
                    'omp_id' 		=> $this->omp_id,
                    'notVal' 		=> $this->not,
                    'reference' 	=> $this->reference,
                    'eco_id' 		=> $this->eco_id,
                    'env_condition'		=> $this->env_condition,
                    'dateVal'      	=> self::time(),
                    'submitted_by' 	=> $wgUser->getName(),
                    'notes' 		=> $this->notes
                ),
                __METHOD__
            );
	    $this->id = $dbw->insertId();# echo "id: $this->id"; die;
	    $this->accession = "OMP_AN:".$this->id;
	    $this->update();
	}
}