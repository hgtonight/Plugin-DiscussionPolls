<?php if (!defined('APPLICATION')) exit();
/*	Copyright 2013 Zachary Doll All rights reserved. Do not distribute. */
class DiscussionPollsModel extends Gdn_Model {
	/**
    * Class constructor. Defines the related database table name.
    */
	public function  __construct($Name = '') {
		parent::__construct('DiscussionPolls');
	}
	
	public function Exists($ID) {
		$this->SQL
			->Select('p.PollID')
			->From('DiscussionPolls p')
			->Where('p.PollID', $ID);
		
		$Data = $this->SQL->Get();
		echo '<pre>'; var_dump($Data); echo '</pre>';
		return TRUE;
	}
	
	public function HasResponses($ID) {}
	
	public function Get($ID) {}
	
	public function Save($FormPostValues) {}
	
	public function UpdateResults($ID) {}
	
	public function Delete($ID) {}
}