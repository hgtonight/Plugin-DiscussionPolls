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
	// create a fake controller for poll
	public function DiscussionController_Poll_Create($Sender) {
		$this->Dispatch($Sender, $Sender->RequestArgs);
	}
	
	// TODO: Document
	// Will be used for the settings page if there is one
	public function Controller_Index($Sender) {
		$DPModel = new DiscussionPollsModel();
		
		$Poll = $DPModel->Get(1);
		var_dump($Poll);
		//echo 'I did something on the fake controller!';
		$Sender->Render();
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
			$this->_RenderPollSubmissionForm();
	}
	
	// TODO: Document
	// Render poll in first post of discussion in 2.1b1 
	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
		// Insert Poll
		$this->_RenderPollSubmissionForm();
	}
	

	// TODO: Document
	// Render the poll form, inserting existing content if it exists
	// Render form to create poll on new discussion page in 2.x
	public function PostController_DiscussionFormOptions_Handler($Sender) {
		//echo '<pre>'; var_dump($Sender); echo '</pre>';
		// Make sure we can add polls
		$Sender->Permission('Plugins.DiscussionPolls.Add','',FALSE);
		// render check box
		$Sender->EventArguments['Options'] .= '<li>'.$Sender->Form->CheckBox('AttachDiscussionPoll', T('Attach Poll'), array('value' => '1', 'checked' => TRUE)).'</li>';
		
		// Load up existing poll data
		$DPModel = new DiscussionPollsModel();
		$DiscussionPoll = $DPModel->Get($Sender->Discussion->DiscussionID);
		
		// Determine if the poll should be closed automatically
		$Closed = $DPModel->HasResponses($Sender->Discussion->DiscussionID);
		$Disabled = array();
		if($Closed == TRUE) {
			/*if(Gdn::Session()->CheckPermission('Plugins.DiscussionPolls.Manage')) {
				// Managers can edit polls after responses have happened
				echo Wrap(T('Plugins.DiscussionPolls.ManagePrivilegeNotice', 'You can edit the poll below even though responses are already recorded. Please take care so as to not alienate members of your community!'), 'div', array('class' => 'DismissMessage AlertMessage'));
				$Closed = FALSE;
			}
			else {*/
				echo Wrap(T('Plugins.DiscussionPolls.PollClosedNotice', 'You cannot edit a poll when responses are already recorded. You <em>may</em> delete this poll by unchecking the Attach Poll checkbox.'), 'div', array('class' => 'Messages Warning'));
				$Disabled = array('disabled' => 'true');
			//}
		}
		// The opening of the form
		$Sender->Form->SetValue('DiscussionPollTitle', $DiscussionPoll->Title);
		echo '<div class="P" id="DiscussionPollsForm">';
			//$Sender->Form->InputPrefix = 'Discussion';
			echo $Sender->Form->Label('Discussion Poll Title', 'DiscussionPollTitle');
			echo Wrap($Sender->Form->TextBox('DiscussionPollTitle', array_merge($Disabled, array('maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));
			
			echo Anchor(' ', '/plugin/discussionpolls/', array('id' => 'DPPreviousQuestion'));
			
			$QuestionCount = 0;
			// set and the form data for existing questions and render a form
			foreach($DiscussionPoll->Questions as $Question) {
				echo '<fieldset id="DPQuestion'.$QuestionCount.'" class="DiscussionPollsQuestion">';
				
				// TODO: Figure out how to get SetValue to work with arrays
				//$Sender->Form->SetValue('DiscussionPollsQuestions['.$QuestionCount.']', $Question->Title);
				echo $Sender->Form->Label('Question #'.($QuestionCount + 1), 'DiscussionPollsQuestions');
				echo Wrap($Sender->Form->TextBox('DiscussionPollsQuestions[]', array_merge($Disabled, array('value' => $Question->Title, 'id' => 'DiscussionPollsQuestions'.$QuestionCount, 'maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));
				$j = 0;
				foreach($Question->Options as $Option) {
					//$Sender->Form->SetValue('DiscussionPollsOptions'.$QuestionCount.'['.$j.']', $Option->Title);
					echo $Sender->Form->Label('Option #'.($j + 1), 'DiscussionPollsOptions'.$QuestionCount.'.'.$i);
					echo Wrap($Sender->Form->TextBox('DiscussionPollsOptions'.$QuestionCount.'[]', array_merge($Disabled, array('value' => $Option->Title, 'id' => 'DiscussionPollsOptions'.$QuestionCount.'.'.$i, 'maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));
					$j++;
				}
				$QuestionCount++;
				echo '</fieldset>';
			}
			
			// If there is no data, render a single question form with 2 options to get started
			if(!$QuestionCount) {
				echo '<fieldset id="DPQuestion0" class="DiscussionPollsQuestion">';
				echo $Sender->Form->Label('Question #1', 'DiscussionPollsQuestions');
				echo Wrap($Sender->Form->TextBox('DiscussionPollsQuestions[]', array('id' => 'DiscussionPollsQuestions0', 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));

				for($i = 0; $i < 2; $i++) {
					echo $Sender->Form->Label('Option #'.($i + 1), 'DiscussionPollsOptions0.'.$i);
					echo Wrap($Sender->Form->TextBox('DiscussionPollsOptions0[]', array('id' => 'DiscussionPollsOptions0.'.$i, 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
				}
				echo '</fieldset>';
			}
			
			// the end of the form
			if(!$Closed) {
				echo Anchor('Add a Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DPNextQuestion'));
				echo Anchor('Add an option', '/plugin/discussionpolls/addoption', array('id' => 'DPAddOption'));
			}
			else {
				echo Anchor('Next Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DPNextQuestion'));
			}
		echo '</div>';
	}

	// TODO: Document
	// Save poll when saving a discussion.
	public function DiscussionModel_AfterSaveDiscussion_Handler($Sender) {
		// Needed no matter what
		$DPModel = new DiscussionPollsModel();
		$DiscussionID = GetValue('DiscussionID', $Sender->EventArguments, 0);
		$FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
		
		echo '<pre>'; var_dump($FormPostValues); echo '</pre>';
		
		// Unchecking the poll option will remove the poll if it exists
		if(!GetValue('AttachDiscussionPoll', $FormPostValues)) {
			// Check for existing poll
			if($DPModel->Exists($DiscussionID)) {
				// Delete existing poll
				$DPModel->Delete($DiscussionID);
			}
			// Don't continue either way
			return;
		}
		die();
		
		// Check to see if there are already poll responses; exit
		if($DPModel->HasResponses($DiscussionID)) {
			// TODO: Show a message saying it can't be edited
			return;
		}
		// Make sure we can add polls
		$Sender->Permission('Plugins.DiscussionPolls.Add','',FALSE);

		// save poll form fields
		$DPModel->Save($FormPostValues);
	}
   
	// TODO: Document
	// Remove attach poll when discussion is deleted
	public function DiscussionModel_DeleteDiscussion_Handler($Sender) {
		// Get discussionID that is being deleted
		$DiscussionID = $Sender->EventArguments['DiscussionID'];

		// Delete via model
		$DPModel = new DiscussionPollsModel();
		$DPModel->Delete($DiscussionID);
	}
   
	// TODO: Document
	protected function _RenderPollSubmissionForm($Sender) {
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

		$Construct->Table('DiscussionPolls');
		$Construct
		   ->PrimaryKey('PollID')
		   ->Column('DiscussionID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Open', 'tinyint(1)', '1')
		   ->Set();
		   
		$Construct->Table('DiscussionPollQuestions');
		$Construct
		   ->PrimaryKey('QuestionID')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Count', 'int', '0')
		   ->Set();
		   
		$Construct->Table('DiscussionPollQuestionOptions');
		$Construct
		   ->PrimaryKey('OptionID')
		   ->Column('QuestionID', 'int', TRUE, 'key')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('Score', 'int', '0')
		   ->Set();
		   
		$Construct->Table('DiscussionPollAnswers');
		$Construct
		   ->PrimaryKey('AnswerID')
		   ->Column('PollID', 'int', TRUE, 'key')
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
