<?php
class PageMover {

	#this code will move the pages to a new OMP ID 
	public function pagemove () {
	
		$strain = "Template:StrainPage";
		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select(
			'page',
			'*',
			array("page_title LIKE '$strain'", "page_namespace = '0'" ),
			__METHOD__,
			array('LIMIT'=>1)
		);
	
		$oldtitle = Title::newFromText($result);
		$old_page = new WikiPageTE($oldtitle);
		
		$dbw = wfGetDB( DB_SLAVE );
		$dbw->insert( 'omp_master.strain', array( 'id' => null, 'name' => $oldtitle) );
		$id = $dbw->insertId();
	
		$newpagename = "OMP_ST:"."$id"."_!_".$old_page;
		$title = Title::newFromText($newpagename);
		$new_page = new WikiPageTE($title);
		$pagemover = $old_page->move($new_page);
		
		$wikiPage = new WikiPageTE($new_page);
		$wikiPage->save();
		$wikiPage->touch();
	}
}