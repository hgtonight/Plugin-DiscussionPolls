<?php if (!defined('APPLICATION')) exit();
/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
$PluginInfo['DiscussionPolls'] = array(
	'Name' => 'Discussion Polls',
	'Description' => 'A plugin that allows creating polls that attach to a discussion. Respects permissions.',
	'Version' => '0.1',
	'Author' => 'Zachary Doll',
	'AuthorEmail' => 'hgtonight@daklutz.com ',
	'AuthorUrl' => 'http://www.daklutz.com',
	'License' => 'All rights reserved. Do not distribute.'
);

class DiscussionPolls extends Gdn_Plugin 
{
	// TODO: Document
	public function __construct() {
		parent::__construct();
	}
	
	// TODO: Document
	// Add css and js to the discussion controller 
	public function DiscussionController_Render_Before($Sender) {
		// Add resources
	}
	
	// TODO: Document
	// Add css and js to the discussion controller 
	public function PostController_Render_Before($Sender) {
		// Add resources
	}
	
	// TODO: Document
	// Render poll in first post of discussion in 2.0.x 
	public function DiscussionController_AfterCommentBody_Handler($Sender) {
		// Make sure event argument type is Discussion
			
			// Insert Poll
			
	}
	
	// TODO: Document
	// Render poll in first post of discussion in 2.1b1 
	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
		// Insert Poll
	}
	

	// TODO: Document
	// Render form to create poll on new discussion page in 2.x
	public function PostController_DiscussionFormOptions_Handler($Sender) {
		// render check box
		
		// render poll creation form
			
			// initially hidden
	}
	
	// TODO: Document
	protected function _RenderDiscussionPoll($Sender) {
		// Render the poll if it exists
		
			// Has the user voted?
				
				// Render results
				
				// Render poll questions
			
		
		// Render poll controls if the user owns this discussion or they have the DiscussionPolls.Manage permission
			
			// Attach if poll doesn't exist
				
			// Remove if poll exists
	}
	
	protected function _AddResources($Sender) {
		$Sender->AddJsFile($this->GetResource('js/discussionpolls.js', FALSE, FALSE));
		$Sender->AddCSSFile($this->GetResource('design/discussionpolls.css', FALSE, FALSE));
	}
	
	// TODO: Document
	public function Setup() {
	
	}
	
	// TODO: Document
	protected function Structure() {
		$Database = Gdn::Database();
		$SQL = $Database->SQL();
		$Construct = $Database->Structure();

		$Construct->Table('DP_Polls');
		$Construct
		   ->PrimaryKey('PollID')
		   ->Column('DiscussionID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Open', 'tinyint(1)', '1');
		   
		$Construct->Table('DP_Questions');
		$Construct
		   ->PrimaryKey('QuestionID')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Count', 'int', '0');
		   
		$Construct->Table('DP_Answers');
		$Construct
		   ->PrimaryKey('AnswerID')
		   ->Column('QuestionID', 'int', TRUE, 'key')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Score', 'int', '0');
	}
	
	->Column('Name', 'varchar(50)', FALSE, 'key')
   ->Column('Password', 'varbinary(100)') // keep this longer because of some imports.
	->Column('HashMethod', 'varchar(10)', TRUE)
   ->Column('Photo', 'varchar(255)', NULL)
   ->Column('About', 'text', TRUE)
   ->Column('Email', 'varchar(200)', FALSE, 'index')
   ->Column('ShowEmail', 'tinyint(1)', '0')
   ->Column('Gender', array('m', 'f'), 'm')
   ->Column('CountVisits', 'int', '0')
   ->Column('CountInvitations', 'int', '0')
   ->Column('CountNotifications', 'int', NULL)
   ->Column('InviteUserID', 'int', TRUE)
   ->Column('DiscoveryText', 'text', TRUE)
   ->Column('Preferences', 'text', TRUE)
   ->Column('Permissions', 'text', TRUE)
   ->Column('Attributes', 'text', TRUE)
   ->Column('DateSetInvitations', 'datetime', TRUE)
   ->Column('DateOfBirth', 'datetime', TRUE)
   ->Column('DateFirstVisit', 'datetime', TRUE)
   ->Column('DateLastActive', 'datetime', TRUE)
   ->Column('LastIPAddress', 'varchar(15)', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('InsertIPAddress', 'varchar(15)', TRUE)
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateIPAddress', 'varchar(15)', TRUE)
   ->Column('HourOffset', 'int', '0')
	->Column('Score', 'float', NULL)
   ->Column('Admin', 'tinyint(1)', '0')
   ->Column('Banned', 'tinyint(1)', '0') // 1 means banned, otherwise not banned
   ->Column('Deleted', 'tinyint(1)', '0')
}
