<?php if (!defined('APPLICATION')) exit();
/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
$PluginInfo['DiscussionPolls'] = array(
	'Name' => 'Discussion Polls',
	'Description' => 'A plugin that allows creating polls that attach to a discussion. Respects permissions.',
	'Version' => '0.1',
	'RegisterPermissions' => array('Plugins.DiscussionPolls.Add', 'Plugins.DiscussionPolls.View', 'Plugins.DiscussionPolls.Delete', 'Plugins.DiscussionPolls.Manage'),
	'SettingsUrl' => '/settings/discussionpolls',
	'SettingsPermission' => 'Garden.Settings.Manage',
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
	// Settings page
	public function SettingsController_DiscussionPolls_Create($Sender) {
		// Makes it look like a dashboard page
		$Sender->AddSideMenu('settings/discussionpolls');
		
		$Sender->PluginDescription = 'A plugin that allows creating polls that attach to a discussion. Respects permissions.';
		
		$Sender->Title('Discussion Polls Settings');
		$Sender->Render($this->GetView("settings.php"));
	}
	
	// TODO: Document
	// create a fake controller for poll
	public function DiscussionController_Poll_Create($Sender) {
		//echo '<pre>'; var_dump($Args); echo '</pre>';
		$this->Dispatch($Sender, $Sender->RequestArgs);
	}
	
	// TODO: Document
	public function Controller_Index($Sender) {
		Redirect('discussions');
	}
	
	// TODO: Document
	// Submit a poll
	public function Controller_Submit($Sender) {
		//echo '<pre>'; var_dump($Sender->Form->FormValues()); echo '</pre>';
		//echo '<pre>'; var_dump($Sender->Request->GetRequestArguments('post')); echo '</pre>';
		$Session = Gdn::Session();
		$FormPostValues = $Sender->Form->FormValues();
		
		// You have to have voting privilege only
		if(!$Session->CheckPermission('Plugins.DiscussionPolls.Vote', FALSE)
			|| !$Session->UserID) {
			Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.UnableToSubmit', 'You do not have permission to submit a poll.'));
			Redirect('discussions/'.$FormPostValues->DiscussionID);
		}
		
		// If seeing the form for the first time...
		if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
			// redirect to the discussions view
			Redirect('discussions');
			//$Sender->Form->SetData($ConfigurationModel->Data);
		}
		else {
			$DPModel = new DiscussionPollsModel();
			
			$Saved = $DPModel->SaveAnswer($FormPostValues, $Session->UserID);
			if ($Saved) {
				Redirect('discussion/'.$FormPostValues['DiscussionID']);
			}
			else {
				Redirect('discussions');
			}
		}
		
		// Render the proper view
		//$Sender->Render($this->GetView('submitpoll.php'));
	}
	
	// Renders the results of a poll
	// Will render a full page view
	// This is also used on the frontend with JS
	public function Controller_Results($Sender) {
		//echo '<pre>'; var_dump($Sender->RequestArgs); echo '</pre>';
		
		$DPModel = new DiscussionPollsModel();
		$Poll = $DPModel->Get($Sender->RequestArgs[1]);
		
		$PollResults = $this->_RenderResults($Poll, FALSE);
		if($Sender->DeliveryType == DELIVERY_TYPE_VIEW) {
			//$Data = array('html' => $PollResults);
			echo json_encode($Data);
		}
		else {
			$Sender->SetData('PollString', $PollResults);
			$Sender->Render($this->GetView('poll.php'));
		}
	}
	
	// TODO: Use this somewhere
	public function Controller_RemoveVote($Sender) {
		//echo '<pre>'; var_dump($Sender->RequestArgs); echo '</pre>';
		
		$DPModel = new DiscussionPollsModel();
		$Poll = $DPModel->RemoveAnswer($Sender->RequestArgs[1], $Sender->RequestArgs[3]);
		
		$this->_RenderResults($Poll);
	}
	
	// Renders the results of deleting a poll
	// This will only be seen on legacy systems without JS
	public function Controller_Delete($Sender) {
		//echo '<pre>'; var_dump($Sender->RequestArgs); echo '</pre>';
		$DPModel = new DiscussionPollsModel();
		$DPModel->Delete($Sender->RequestArgs[1]);
				
		if($Sender->DeliveryType == DELIVERY_TYPE_VIEW) {
			echo TRUE;
		}
		else {
			$Sender->SetData('PollString', 'Removed poll with id '.$Sender->RequestArgs[1]);
			$Sender->Render($this->GetView('poll.php'));
		}
	}
	
	// TODO: Document
	// Add css and js to the discussion controller 
	public function DiscussionController_Render_Before($Sender) {
		// Add poll response resources
		$Sender->AddJsFile($this->GetResource('js/discussionpolls.js', FALSE, FALSE));
		$Sender->AddCSSFile($this->GetResource('design/discussionpolls.css', FALSE, FALSE));
	}
	
	// TODO: Document
	// Add css and js to the discussion controller 
	public function PostController_Render_Before($Sender) {
		// Add poll creation resources
		$Sender->AddJsFile($this->GetResource('js/admin.discussionpolls.js', FALSE, FALSE));
		$Sender->AddCSSFile($this->GetResource('design/admin.discussionpolls.css', FALSE, FALSE));
	}
	
	// TODO: Document
	// Render poll in first post of discussion in 2.0.x 
	public function DiscussionController_AfterCommentBody_Handler($Sender) {
		// echo '<pre>'; var_dump($Sender->EventArguments['Type']); echo '</pre>';
			
		// Make sure event argument type is Discussion
		if($Sender->EventArguments['Type'] == 'Discussion') {
			// Insert Poll
			$this->_PollInsertion($Sender);
		}
	}
	
	// TODO: Document
	// Render poll in first post of discussion in 2.1b1 
	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
		// Insert Poll
		$this->_PollInsertion($Sender);
	}
	

	// TODO: Document
	// Render the poll form, inserting existing content if it exists
	// Render form to create poll on new discussion page in 2.x
	public function PostController_DiscussionFormOptions_Handler($Sender) {
		//echo '<pre>'; var_dump($Sender); echo '</pre>';
		// Make sure we can add polls
		$Sender->Permission('Plugins.DiscussionPolls.Add','',FALSE);
		
		// render check box
		$Sender->EventArguments['Options'] .= '<li>'.$Sender->Form->CheckBox('DP_Attach', T('Attach Poll'), array('value' => '1', 'checked' => TRUE)).'</li>';
		
		// Load up existing poll data
		$DPModel = new DiscussionPollsModel();
		$DiscussionPoll = $DPModel->GetByDiscussionID($Sender->Discussion->DiscussionID);
		
		//echo '<pre>'; var_dump($DiscussionPoll); echo '</pre>';
		
		// If there is existing poll data, disable editing
		// Editing will be in a future release
		if(!empty($DiscussionPoll->PollID)) {
			$Closed = TRUE;
			$Disabled = array('disabled' => 'true');
			echo Wrap(T('Plugins.DiscussionPolls.PollClosedToEdits', 'You cannot edit a poll. You <em>may</em> delete this poll by unchecking the Attach Poll checkbox.'), 'div', array('class' => 'Messages Warning'));
		}
		else {
			$Disabled = array();
			$Closed = FALSE;
		}
		
		$Sender->AddDefinition('DiscussionPollClosed', $Closed);
		
		// Future release
		// Determine if the poll should be closed automatically
		/*$Closed = $DPModel->HasResponses($Sender->Discussion->DiscussionID);
		$Disabled = array();
		if($Closed == TRUE) {
			if(Gdn::Session()->CheckPermission('Plugins.DiscussionPolls.Manage')) {
				// Managers can edit polls after responses have happened
				echo Wrap(T('Plugins.DiscussionPolls.ManagePrivilegeNotice', 'You can edit the poll below even though responses are already recorded. Please take care so as to not alienate members of your community!'), 'div', array('class' => 'DismissMessage AlertMessage'));
				$Closed = FALSE;
			}
			else {
				echo Wrap(T('Plugins.DiscussionPolls.PollClosedNotice', 'You cannot edit a poll when responses are already recorded. You <em>may</em> delete this poll by unchecking the Attach Poll checkbox.'), 'div', array('class' => 'Messages Warning'));
				$Disabled = array('disabled' => 'true');
			}
		}*/
		
		// The opening of the form
		// This doesn't work on 2.0.18.8 --v
		//$Sender->Form->InputPrefix = 'Discussion';
		$Sender->Form->SetValue('DiscussionPollTitle', $DiscussionPoll->Title);
		
		//echo $Sender->Form->Hidden('PollID');
		//$Sender->Form->SetValue('DiscussionPollID', $DiscussionPoll->PollID);
		echo '<div class="P" id="DP_Form">';
			echo $Sender->Form->Label('Discussion Poll Title', 'DP_Title');
			echo Wrap($Sender->Form->TextBox('DP_Title', array_merge($Disabled, array('maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));
			
			echo Anchor(' ', '/plugin/discussionpolls/', array('id' => 'DP_PreviousQuestion'));
			
			$QuestionCount = 0;
			// set and the form data for existing questions and render a form
			foreach($DiscussionPoll->Questions as $Question) {
				echo '<fieldset id="DP_Question'.$QuestionCount.'" class="DP_Question">';
				
				// TODO: Figure out how to get SetValue to work with arrays
				//$Sender->Form->SetValue('DiscussionPollsQuestions['.$QuestionCount.']', $Question->Title);
				echo $Sender->Form->Label(
					'Question #'.($QuestionCount + 1),
					'DP_Questions'.$QuestionCount
				);
				echo Wrap(
					$Sender->Form->TextBox(
						'DP_Questions[]',
						array_merge($Disabled, array(
							'value' => $Question->Title,
							'id' => 'DP_Questions'.$QuestionCount,
							'maxlength' => 100,
							'class' => 'InputBox BigInput'
						))),
					'div',
					array('class' => 'TextBoxWrapper')
				);
				
				$j = 0;
				foreach($Question->Options as $Option) {
					//$Sender->Form->SetValue('DiscussionPollsOptions'.$QuestionCount.'['.$j.']', $Option->Title);
					echo $Sender->Form->Label(
						'Option #'.($j + 1),
						'DP_Options'.$QuestionCount.'.'.$i
					);
					
					echo Wrap(
						$Sender->Form->TextBox(
							'DP_Options'.$QuestionCount.'[]',
							array_merge($Disabled, array(
								'value' => $Option->Title,
								'id' => 'DP_Options'.$QuestionCount.'.'.$i,
								'maxlength' => 100,
								'class' => 'InputBox BigInput'
							))),
						'div',
						array('class' => 'TextBoxWrapper')
					);
					$j++;
				}
				
				$QuestionCount++;
				echo '</fieldset>';
			}
			
			// If there is no data, render a single question form with 2 options to get started
			if(!$QuestionCount) {
				$DefaultQuestionString = '<fieldset id="DP_Question0" class="DP_Question">';
				$DefaultQuestionString .= $Sender->Form->Label('Question #1', 'DP_Questions0');
				$DefaultQuestionString .=  Wrap(
					$Sender->Form->TextBox(
						'DP_Questions[]',
						array(
							'id' => 'DP_Questions0',
							'maxlength' => 100,
							'class' => 'InputBox BigInput'
						)
					),
					'div',
					array('class' => 'TextBoxWrapper')
				);

				for($i = 0; $i < 2; $i++) {
					$DefaultQuestionString .= $Sender->Form->Label(
						'Option #'.($i + 1),
						'DP_Options0.'.$i
					);
					$DefaultQuestionString .= Wrap(
						$Sender->Form->TextBox(
							'DP_Options0[]',
							array(
								'id' => 'DP_Options0.'.$i,
								'maxlength' => 100,
								'class' => 'InputBox BigInput'
							)
						),
						'div',
						array('class' => 'TextBoxWrapper')
					);
				}
				$DefaultQuestionString .= '</fieldset>';
				$Sender->AddDefinition('DP_EmptyQuestion', $DefaultQuestionString);
				echo $DefaultQuestionString;
			}
			
			// the end of the form
			if(!$Closed) {
				echo Anchor('Add a Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DP_NextQuestion'));
				echo Anchor('Add an option', '/plugin/discussionpolls/addoption', array('id' => 'DP_AddOption'));
			}
			else if($QuestionCount > 1){
				echo Anchor('Next Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DP_NextQuestion'));
			}
		echo '</div>';
	}

	// TODO: Document
	// Save poll when saving a discussion.
	public function DiscussionModel_AfterSaveDiscussion_Handler($Sender) {
		//echo '<pre>'; var_dump($Sender); echo '</pre>';
		// Needed no matter what
		$DPModel = new DiscussionPollsModel();
		$Session = Gdn::Session();
		
		// Make sure we can add/manage polls
		if(!$Session->CheckPermission(array('Plugins.DiscussionPolls.Add', 'Plugins.DiscussionPolls.Manage'), FALSE)) {
			Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.UnableToEdit', 'You do not have permission to edit a poll.'));
			return;
		}

		$DiscussionID = GetValue('DiscussionID', $Sender->EventArguments, 0);
		$FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
		
		// Unchecking the poll option will remove the poll
		if(!GetValue('DP_Attach', $FormPostValues)) {
			// Delete existing poll
			Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.PollRemoved', 'The attached poll has been removed'));
			$DPModel->Delete($DiscussionID);
			return;
		}
		
		if($DPModel->Exists($DiscussionID)) {
			// Skip saving if a poll exists
			Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.AlreadyExists', 'This poll already exists, poll was not updated'));
			return;
		}
		
		// Check to see if there are already poll responses; exit
		if($DPModel->HasResponses($DiscussionID) &&
			!$Session->CheckPermission('Plugins.DiscussionPolls.Manage')) {
			
			Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.UnableToEditAfterResponses', 'You do not have permission to edit a poll with responses.'));
			return;
		}

		// Validate that all required fields are filled out
		// TODO: Figure out a good way to validate the poll fields
		
		// save poll form fields
		$DPModel->Save($FormPostValues);
	}
   
	// TODO: Document
	// Remove attached poll when discussion is deleted
	public function DiscussionModel_DeleteDiscussion_Handler($Sender) {
		// Get discussionID that is being deleted
		$DiscussionID = $Sender->EventArguments['DiscussionID'];

		// Delete via model
		$DPModel = new DiscussionPollsModel();
		$DPModel->Delete($DiscussionID);
	}
   
	// TODO: Document
	// Determines what part of the poll (if any) needs to be rendered
	// Checks permissions and displays any tools available to user
	protected function _PollInsertion($Sender) {
		//echo '<pre>'; var_dump($Sender->Discussion); echo '</pre>';
		$Discussion = $Sender->Discussion;
		$Session = Gdn::Session();
		$DPModel = new DiscussionPollsModel();
		
		// Does an attached poll exist?
		if($DPModel->Exists($Discussion->DiscussionID)) {
			$Results = FALSE;
			$Poll = $DPModel->GetByDiscussionID($Discussion->DiscussionID);
			// Can the current user view polls?
			if(!$Session->CheckPermission('Plugins.DiscussionPolls.View')) {
				// make this configurable?
				echo Wrap(T('Plugins.DiscussionPolls.NoView', 'You do not have permission to view polls.'), 'div', array('class' => 'DP_AnswerForm'));
				return;
			}
			// Check to see if the discussion is closed
			if($Discussion->Closed) {
				// Close the Poll if the discussion is closed (workaround)
				$DPModel->Close($Discussion->DiscussionID);
				// TODO: Get rid of workaround by finding _some way_ to hook into the discussion model
				// and close/open the poll **only** when the attached discussion is [un]closed.
				$Closed = TRUE;
			}
			
			// Has the user voted?
			if($DPModel->HasAnswered($Poll->PollID, $Session->UserID) || !$Session->IsValid()) {
				$Results = TRUE;
				
				// Render results
				$this->_RenderResults($Poll);
			}
			else {
				// Render the submission form
				$this->_RenderVotingForm($Sender, $Poll, $Session->UserID);
			}
			
			// Render poll controls
			// Owner and Plugins.DiscussionPolls.Manage gets delete if exists and attach if it doesn't
			// Plugins.DiscussionPolls.View gets show results if the results aren't shown
			$Tools = '';
			if($Discussion->InsertUserID == $Session->UserID
				|| $Session->CheckPermission('Plugins.DiscussionPolls.Manage') ) {
				$Tools .= Wrap(
					Anchor(T('Delete Poll'), '/discussion/poll/delete/'.$Poll->PollID),
					'li',
					array('id' => 'DP_Remove')
				);
			}
			
			if(!$Results) {
				$Tools .= Wrap(
					Anchor(T('Show Results'), '/discussion/poll/results/'.$Poll->PollID),
					'li',
					array('id' => 'DP_Results')
				);
			}
			
			if($Tools != '') {
				echo Wrap($Tools, 'ul', array('id' => 'DP_Tools'));
			}
		}
		else {
			// Poll does not exist
			if($Discussion->InsertUserID == $Session->UserID
				|| $Session->CheckPermission('Plugins.DiscussionPolls.Manage') ) {
				echo Wrap(
					Wrap(
						Anchor('Attach Poll', '/vanilla/post/editdiscussion/'.$Discussion->DiscussionID),
						'li'),
					'ul',
					array('id' => 'DP_Tools')
				);
			}
		}
	}
	
	// TODO: Inspect partial view rendering
	// Renders a poll object as results
	protected function _RenderResults($Poll, $Echo = TRUE) {
		//echo '<pre>'; var_dump($Poll); echo '</pre>';
		$Result = '<div class="DP_ResultsForm">';
		$Result .= $Poll->Title;
		
		$Result .= '<ol class="DP_ResultQuestions">';
		foreach($Poll->Questions as $Question) {
			$Result .= '<li class="DP_ResultQuestion">';
			$Result .= Wrap($Question->Title, 'span');
			$Result .= Wrap(sprintf(Plural($Question->CountResponses, '%s vote', '%s votes'), $Question->CountResponses), 'span', array('class' => 'Number DP_VoteCount'));
			
			// k is used to have different option bar colors
			$k = $Question->QuestionID % 10;//rand(0, 9);
			$Result .= '<ol class="DP_ResultOptions">';
			foreach($Question->Options as $Option) {
				$string = Wrap($Option->Title, 'div');
				$Percentage = number_format(($Option->CountVotes / $Question->CountResponses * 100), 2);
				if($Percentage < 10) {
					$Percentage = $Percentage.'%';
					// put the text on the outside
					$string .= '<span class="DP_Bar DP_Bar-'.$k.'" style="width: '.$Percentage.';">&nbsp</span>'.$Percentage;
				}
				else {
					$Percentage = $Percentage.'%';
					// put the text on the inside
					$string .= '<span class="DP_Bar DP_Bar-'.$k.'" style="width: '.$Percentage.';">'.$Percentage.'</span>';
				}
				
				$Result .= Wrap($string, 'li', 'DP_ResultOption');
				
				$k++; $k = $k % 10;
			}
			$Result .= '</ol>';
			$Result .= '</li>';
		}
		$Result .= '</ol>';
		$Result .= '</div>';
		
		if($Echo) {
			echo $Result;
		}
		else {
			return $Result;
		}
	}
	
	// Renders a poll object as a voting form 
	protected function _RenderVotingForm($Sender, $Poll) {
		// Render the submission form
		echo '<div class="DP_AnswerForm">';
		echo $Poll->Title;
		$Sender->PollForm = new Gdn_Form();
		$Sender->PollForm->AddHidden('DiscussionID', $Poll->DiscussionID);
		$Sender->PollForm->AddHidden('PollID', $Poll->PollID);
		
		// TODO: Look into AJAX form submission 'ajax' => TRUE
		echo $Sender->PollForm->Open(array('action' => Url('/discussion/poll/submit/'), 'method' => 'post'));
		echo $Sender->PollForm->Errors();
		
		$m = 0;
		// Render poll questions
		echo '<ol class="DP_AnswerQuestions">';
		foreach($Poll->Questions as $Question) {
			echo '<li class="DP_AnswerQuestion">';
			echo $Sender->PollForm->Hidden('DP_AnswerQuestions[]', array('value' => $Question->QuestionID));
			echo Wrap($Question->Title, 'span');
			echo '<ol class="DP_AnswerOptions">';
			foreach($Question->Options as $Option) {
				echo Wrap($Sender->PollForm->Radio('DP_Answer'.$m, $Option->Title, array('Value' => $Option->OptionID)), 'li');
			}
			echo '</ol>';
			echo '</li>';
			$m++;
		}
		echo '</ol>';
		
		echo $Sender->PollForm->Close('Submit');
		echo '</div>';
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
		   ->Column('CountResponses', 'int', '0')
		   ->Set();
		   
		$Construct->Table('DiscussionPollQuestionOptions');
		$Construct
		   ->PrimaryKey('OptionID')
		   ->Column('QuestionID', 'int', TRUE, 'key')
		   ->Column('PollID', 'int', TRUE, 'key')
		   ->Column('Text', 'varchar(140)')
		   ->Column('CountVotes', 'int', '0')
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
