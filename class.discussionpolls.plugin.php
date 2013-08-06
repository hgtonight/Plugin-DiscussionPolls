<?php if (!defined('APPLICATION')) exit();
/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
$PluginInfo['DiscussionPolls'] = array(
	'Name' => 'Discussion Polls',
	'Description' => 'A plugin that allows creating polls that attach to a discussion. Respects permissions.',
	'Version' => '0.1',
	'RegisterPermissions' => array('Plugins.DiscussionPolls.Add', 'Plugins.DiscussionPolls.View', 'Plugins.DiscussionPolls.Delete', 'Plugins.DiscussionPolls.Manage'),
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
		$this->_AddResources($Sender);
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
		//echo '<pre>'; var_dump($Sender); echo '</pre>';
		// Make sure we can add polls
		$Sender->Permission('Plugins.DiscussionPolls.Add','',FALSE);
		// render check box
		$Sender->EventArguments['Options'] .= '<li>'.$Sender->Form->CheckBox('DiscussionPoll', T('Attach Poll'), array('value' => '1', 'checked' => TRUE)).'</li>';
		
		// TODO: Put this stuff in a theme?
		// render poll creation form
		echo '<div class="P" id="DiscussionPollsForm">';
			echo $Sender->Form->Label('Discussion Poll Title', 'DiscussionPollTitle');
			echo Wrap($Sender->Form->TextBox('DiscussionPollTitle', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			
			echo Anchor(' ', '/plugin/discussionpolls/question/add', array('id' => 'DPPreviousQuestion'));
			// render the first poll question form
			echo '<fieldset id="DPQuestion0" class="DiscussionPollsQuestion">';
			echo $Sender->Form->Label('Question #1', 'DiscussionPollQuestion');
			echo Wrap($Sender->Form->TextBox('DiscussionPollQuestion[]', array('id' => 'DiscussionPollQuestion0', 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			
			// start with two options 
			for($i = 0; $i < 2; $i++) {
				echo $Sender->Form->Label('Option #'.($i + 1), 'DiscussionPollOption0.'.$i);
				echo Wrap($Sender->Form->TextBox('DiscussionPollOption0[]', array('id' => 'DiscussionPollOption0.'.$i, 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			}
			echo '</fieldset>';
			echo Anchor('Add a Question', '/plugin/discussionpolls/question/add', array('id' => 'DPNextQuestion'));
			echo Anchor('Add an option', '/plugin/discussionpolls/option/add', array('id' => 'DPAddOption'));
		echo '</div>';
	}

	// TODO: Document
	// Render form to create poll on new discussion page in 2.x
	public function PostController_AfterDiscussionSave_Handler($Sender) {
		echo '<pre>'; var_dump($Sender->Form->FormValues()); echo '</pre>';
		die();
		// Make sure we can add polls
		$Sender->Permission('Plugins.DiscussionPolls.Add','',FALSE);
		
		// parse form fields
		
		// save poll data
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
	
	// Setup database structure for model
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
		   ->Column('Open', 'tinyint(1)', '1')
		   ->Set();
		   
		$Construct->Table('DP_Questions');
		$Construct
		   ->PrimaryKey('QuestionID')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Count', 'int', '0')
		   ->Set();
		   
		$Construct->Table('DP_Options');
		$Construct
		   ->PrimaryKey('OptionID')
		   ->Column('QuestionID', 'int', TRUE, 'key')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Score', 'int', '0')
		   ->Set();
		   
		$Construct->Table('DP_Results');
		$Construct
		   ->PrimaryKey('PollID')
		   ->Column('QuestionID', 'int', TRUE, 'key')
		   ->Column('UserID', 'int', TRUE, 'key')
		   ->Column('OptionID', 'int', TRUE, 'key')
		   ->Set();
	}
	
	// TODO: Document
	public function Setup() {
		// Register permissions
		$PermissionModel = Gdn::PermissionModel();
		$PermissionModel->Define(
			array(
				'Plugins.DiscussionPolls.Add',
				'Plugins.DiscussionPolls.View' => 1,
				'Plugins.DiscussionPolls.Delete',
				'Plugins.DiscussionPolls.Manage'
			));
			
		// Set initial guest permissions.
		$PermissionModel->Save(array(
			'Role' => 'Guest',
			'Plugins.DiscussionPolls.View' => 1
		));

		// Set initial confirm email permissions.
		$PermissionModel->Save(array(
			'Role' => 'Confirm Email',
			'Plugins.DiscussionPolls.View' => 1
		));

		// Set initial applicant permissions.
		$PermissionModel->Save(array(
			'Role' => 'Applicant',
			'Plugins.DiscussionPolls.View' => 1
		));

		// Set initial member permissions.
		$PermissionModel->Save(array(
			'Role' => 'Member',
			'Plugins.DiscussionPolls.Add' => 1,
			'Plugins.DiscussionPolls.View' => 1
		));

		// Set initial moderator permissions.
		$PermissionModel->Save(array(
			'Role' => 'Moderator',
			'Plugins.DiscussionPolls.Add' => 1,
			'Plugins.DiscussionPolls.View' => 1,
			'Plugins.DiscussionPolls.Delete' => 1
		));

		// Set initial admininstrator permissions.
		$PermissionModel->Save(array(
			'Role' => 'Administrator',
			'Plugins.DiscussionPolls.Add' => 1,
			'Plugins.DiscussionPolls.View' => 1,
			'Plugins.DiscussionPolls.Delete' => 1,
			'Plugins.DiscussionPolls.Manage' => 1
		));

		// Set up the db structure
		$this->Structure();
	}
	
	// TODO: Document
	public function OnDisable() {
		// Deregister permissions (only in 2.1+)
		/*$PermissionModel = Gdn::PermissionModel();
		$PermissionModel->Undefine(
			array(
				'Plugins.DiscussionPolls.Add',
				'Plugins.DiscussionPolls.View',
				'Plugins.DiscussionPolls.Delete',
				'Plugins.DiscussionPolls.Manage'
			));*/
	}
}
