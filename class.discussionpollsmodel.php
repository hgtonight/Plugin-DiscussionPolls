<?php if (!defined('APPLICATION')) exit();
/*	Copyright 2013 Zachary Doll All rights reserved. Do not distribute. */
class DiscussionPollsModel extends Gdn_Model {
	/**
    * Class constructor. Defines the related database table name.
    */
	public function  __construct($Name = '') {
		parent::__construct('DiscussionPolls');
	}
	
	public function Get($DiscussionID) {}
	
	public function Save($FormPostValues) {}
	
	public function UpdateResults($ReplyCommentID) {}
}