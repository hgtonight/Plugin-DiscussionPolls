<?php if (!defined('APPLICATION')) exit();
/*	Copyright 2013 Zachary Doll All rights reserved. Do not distribute. */
class DiscussionPollsModule extends Gdn_Module {
	// TODO: Document
	public function __construct($Sender = '') {
		parent::__construct($Sender);
	}

	// TODO: Document
	public function AssetTarget() {
		return 'Panel';
	}

	public function ToString() {
		// TODO: Insert proper conditional
		if ($this->_HasData == TRUE) {
			return parent::ToString();
		}
		return '';
	}
}
