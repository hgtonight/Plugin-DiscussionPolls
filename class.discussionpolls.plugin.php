<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2013 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['DiscussionPolls'] = array(
    'Name' => 'Discussion Polls',
    'Description' => 'A plugin that allows creating polls that attach to a discussion. Respects permissions.',
    'Version' => '1.0.1',
    'RegisterPermissions' => array('Plugins.DiscussionPolls.Add', 'Plugins.DiscussionPolls.View', 'Plugins.DiscussionPolls.Vote', 'Plugins.DiscussionPolls.Manage'),
    'SettingsUrl' => '/dashboard/settings/discussionpolls',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Zachary Doll',
    'AuthorEmail' => 'hgtonight@daklutz.com ',
    'AuthorUrl' => 'http://www.daklutz.com',
    'License' => 'All rights reserved. Do not distribute.'
);

class DiscussionPolls extends Gdn_Plugin {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Creates a settings page at /dashboard/settins/discussionpolls
   * @param VanillaController $Sender SettingsController
   */
  public function SettingsController_DiscussionPolls_Create($Sender) {
    $Sender->Permission('Garden.Settings.Manage');

    $Validation = new Gdn_Validation();
    $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
    $ConfigurationModel->SetField(array('Plugins.DiscussionPolls.EnableShowResults'));

    $Sender->Form->SetModel($ConfigurationModel);

    if($Sender->Form->AuthenticatedPostBack() === FALSE) {
      $Sender->Form->SetData($ConfigurationModel->Data);
    }
    else {
      if($Sender->Form->Save() !== FALSE) {
        $Sender->InformMessage('<span class="InformSprite Sliders"></span>' . T("Your changes have been saved."), 'HasSprite');
      }
    }

    // Makes it look like a dashboard page
    $Sender->AddSideMenu('/dashboard/settings/discussionpolls');
    $Sender->Title('Discussion Polls Settings');
    $Sender->Render($this->GetView("settings.php"));
  }

  /**
   * Creates a Poll method on the discussion controller
   * I use it as a mini-controller with it's own methods
   * @param VanillaController $Sender DiscussionController
   */
  public function DiscussionController_Poll_Create($Sender) {
    $this->Dispatch($Sender, $Sender->RequestArgs);
  }

  /**
   * Default action on /discussion/poll is to redirest to
   * /discussions
   * @param VanillaController $Sender DiscussionController
   */
  public function Controller_Index($Sender) {
    Redirect('discussions');
  }

  /**
   * Used to submit a poll vote via form
   * @param VanillaController $Sender DiscussionController
   */
  public function Controller_Submit($Sender) {
    $Session = Gdn::Session();
    $FormPostValues = $Sender->Form->FormValues();

    // You have to have voting privilege only
    if(!$Session->CheckPermission('Plugins.DiscussionPolls.Vote', FALSE) || !$Session->UserID) {
      Gdn::Session()->Stash('DiscussionPollsMessage', T('Plugins.DiscussionPolls.UnableToSubmit', 'You do not have permission to submit a poll.'));
      Redirect('discussions/' . $FormPostValues->DiscussionID);
    }

    // If seeing the form for the first time...
    if($Sender->Form->AuthenticatedPostBack() === FALSE) {
      // redirect to the discussions view
      Redirect('discussions');
    }
    else {
      $DPModel = new DiscussionPollsModel();
      
      //check all question are answered if not don't save. 
      if(!$DPModel->CheckFullyAnswered($FormPostValues)){
        Gdn::Session()->Stash('DiscussionPollsMessage', T('Plugins.DiscussionPolls.UnsweredAllQuestions', 'You have not answered all questions!'));
        Redirect('discussion/' . $FormPostValues['DiscussionID']);
      }
      $Saved = $DPModel->SaveAnswer($FormPostValues, $Session->UserID);
      if($Saved) {
        Redirect('discussion/' . $FormPostValues['DiscussionID']);
      }
      else {
        Gdn::Session()->Stash('DiscussionPollsMessage', T('Plugins.DiscussionPolls.UnsweredUnable', 'Unable to save!'));
        Redirect('discussions');
      }
    }
  }

  /**
   * Renders the results of a poll either full page for legacy users
   * or as a partial for frontend ajax
   * @param VanillaController $Sender DiscussionController
   */
  public function Controller_Results($Sender) {
    $DPModel = new DiscussionPollsModel();
    $Poll = $DPModel->Get($Sender->RequestArgs[1]);

    $PollResults = $this->_RenderResults($Poll, FALSE);
    if($Sender->DeliveryType() == DELIVERY_TYPE_VIEW) {
      $Data = array('html' => $PollResults);
      echo json_encode($Data);
    }
    else {
      $Sender->SetData('PollString', $PollResults);
      $Sender->Render($this->GetView('poll.php'));
    }
  }

  /*
   * Renders the results of deleting a poll
   * This will only be seen on legacy systems without JS
   * @param VanillaController $Sender DiscussionController
   */

  public function Controller_Delete($Sender) {
    $Session = Gdn::Session();
    $DPModel = new DiscussionPollsModel();
    $DiscussionModel = new DiscussionModel();

    $Poll = $DPModel->Get($Sender->RequestArgs[1]);

    $Discussion = $DiscussionModel->GetID($Poll->DiscussionID);

    $PollOwnerID = $Discussion->InsertUserID;

    if($Session->CheckPermission('Plugins.DiscussionPolls.Manage') || $PollOwnerID == $Session->UserID) {
      $DPModel = new DiscussionPollsModel();
      $DPModel->Delete($Sender->RequestArgs[1]);

      $Result = 'Removed poll with id ' . $Sender->RequestArgs[1];
      if($Sender->DeliveryType() == DELIVERY_TYPE_VIEW) {
        $Data = array('html' => $Result);
        echo json_encode($Data);
      }
      else {
        $Sender->SetData('PollString', $Result);
        $Sender->Render($this->GetView('poll.php'));
      }
    }
  }

  /**
   * Add frontend css and js to the discussion controller
   * @param VanillaController $Sender DiscussionController
   */
  public function DiscussionController_Render_Before($Sender) {
    // Add poll voting resources
    $Sender->AddJsFile($this->GetResource('js/discussionpolls.js', FALSE, FALSE));
    $Sender->AddCSSFile($this->GetResource('design/discussionpolls.css', FALSE, FALSE));
    //check for any stashed messages from poll submit
    $Message = Gdn::Session()->Stash('DiscussionPollsMessage');
    if($Message){
      //inform
      Gdn::Controller()->InformMessage($Message);
      //pass to form error
      $Sender->SetData('DiscussionPollsMessage',$Message);
    }
  }

  /**
   * Add backend css and js to the discussion controller
   * @param VanillaController $Sender PostController
   */
  public function PostController_Render_Before($Sender) {
    // Add poll creation resources
    $Sender->AddJsFile($this->GetResource('js/admin.discussionpolls.js', FALSE, FALSE));
    $Sender->AddCSSFile($this->GetResource('design/admin.discussionpolls.css', FALSE, FALSE));
  }

  /**
   * Insert poll in first post of discussion in 2.0.x 
   * @param VanillaController $Sender DiscussionController
   */
  public function DiscussionController_AfterCommentBody_Handler($Sender) {
    // Make sure event argument type is Discussion
    if($Sender->EventArguments['Type'] == 'Discussion') {
      $this->_PollInsertion($Sender);
    }
  }

  /**
   * Insert poll in first post of discussion in 2.1b1 
   * @param VanillaController $Sender DiscussionController
   */
  public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
    $this->_PollInsertion($Sender);
  }

  /**
   * Render the poll admin form on the add/edit discussion page in 2.x
   * @param VanillaController $Sender PostController
   */
  public function PostController_DiscussionFormOptions_Handler($Sender) {
    // Make sure we can add polls
    $Sender->Permission('Plugins.DiscussionPolls.Add', '', FALSE);

    // render check box
    $Sender->EventArguments['Options'] .= '<li>' . $Sender->Form->CheckBox('DP_Attach', T('Attach Poll'), array('value' => '1', 'checked' => TRUE)) . '</li>';

    // Load up existing poll data
    if($Sender->Discussion->DiscussionID != NULL) {
      $DPModel = new DiscussionPollsModel();
      $DiscussionPoll = $DPModel->GetByDiscussionID($Sender->Discussion->DiscussionID);
    }

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

    $Sender->AddDefinition('DP_Closed', $Closed);

    // The opening of the form
    $Sender->Form->SetValue('DP_Title', $DiscussionPoll->Title);

    echo '<div class="P" id="DP_Form">';
    echo $Sender->Form->Label('Discussion Poll Title', 'DP_Title');
    echo Wrap($Sender->Form->TextBox('DP_Title', array_merge($Disabled, array('maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));

    echo Anchor(' ', '/plugin/discussionpolls/', array('id' => 'DP_PreviousQuestion'));

    $QuestionCount = 0;
    // set and the form data for existing questions and render a form
    foreach($DiscussionPoll->Questions as $Question) {
      echo '<fieldset id="DP_Question' . $QuestionCount . '" class="DP_Question">';

      // TODO: Figure out how to get SetValue to work with arrays
      //$Sender->Form->SetValue('DiscussionPollsQuestions['.$QuestionCount.']', $Question->Title);
      echo $Sender->Form->Label(
              'Question #' . ($QuestionCount + 1), 'DP_Questions' . $QuestionCount
      );
      echo Wrap(
              $Sender->Form->TextBox(
                      'DP_Questions[]', array_merge($Disabled, array(
                  'value' => $Question->Title,
                  'id' => 'DP_Questions' . $QuestionCount,
                  'maxlength' => 100,
                  'class' => 'InputBox BigInput'
              ))), 'div', array('class' => 'TextBoxWrapper')
      );

      $j = 0;
      foreach($Question->Options as $Option) {
        echo $Sender->Form->Label(
                'Option #' . ($j + 1), 'DP_Options' . $QuestionCount . '.' . $i
        );

        echo Wrap(
                $Sender->Form->TextBox(
                        'DP_Options' . $QuestionCount . '[]', array_merge($Disabled, array(
                    'value' => $Option->Title,
                    'id' => 'DP_Options' . $QuestionCount . '.' . $i,
                    'maxlength' => 100,
                    'class' => 'InputBox BigInput'
                ))), 'div', array('class' => 'TextBoxWrapper')
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
      $DefaultQuestionString .= Wrap(
              $Sender->Form->TextBox(
                      'DP_Questions[]', array(
                  'id' => 'DP_Questions0',
                  'maxlength' => 100,
                  'class' => 'InputBox BigInput'
                      )
              ), 'div', array('class' => 'TextBoxWrapper')
      );

      for($i = 0; $i < 2; $i++) {
        $DefaultQuestionString .= $Sender->Form->Label(
                'Option #' . ($i + 1), 'DP_Options0.' . $i
        );
        $DefaultQuestionString .= Wrap(
                $Sender->Form->TextBox(
                        'DP_Options0[]', array(
                    'id' => 'DP_Options0.' . $i,
                    'maxlength' => 100,
                    'class' => 'InputBox BigInput'
                        )
                ), 'div', array('class' => 'TextBoxWrapper')
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
    else if($QuestionCount > 1) {
      echo Anchor('Next Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DP_NextQuestion'));
    }
    echo '</div>';
  }

  /**
   * Validate the poll fields before we save so we can inform the user
   * without saving the discussion
   * @param VanillaModel $Sender DiscussionModel
   * @return boolean Whether or not validation was successful
   */
  public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {
    $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());
    if(!GetValue('DP_Attach', $FormPostValues)) {
      // No need to validate
      return FALSE;
    }

    // Validate that all poll fields are filled out
    $Invalid = FALSE;
    $Error = '';
    if(trim($FormPostValues['DP_Title']) == FALSE) {
      $Invalid = TRUE;
      $Error = 'You must enter a valid poll title!';
    }

    foreach($FormPostValues['DP_Questions'] as $Index => $Question) {
      if(trim($Question) == FALSE) {
        $Invalid = TRUE;
        $Error = 'You must enter valid question text!';
        break;
      }
      foreach($FormPostValues['DP_Options' . $Index] as $Option) {
        if(trim($Option) == FALSE) {
          $Invalid = TRUE;
          $Error = 'You must enter valid option text!';
          break;
        }
      }
    }

    if($Invalid) {
      $Error = Wrap('Error', 'h1') . Wrap($Error, 'p');
      // should prevent the discussion from being saved
      die($Error);
    }
    return TRUE;
  }

  /**
   * Save poll when saving a discussion
   * @param VanillaModel $Sender DiscussionModel
   * @return boolean if the poll was saved
   */
  public function DiscussionModel_AfterSaveDiscussion_Handler($Sender) {
    // Needed no matter what
    $DPModel = new DiscussionPollsModel();
    $Session = Gdn::Session();

    // Make sure we can add/manage polls
    if(!$Session->CheckPermission(array('Plugins.DiscussionPolls.Add', 'Plugins.DiscussionPolls.Manage'), FALSE)) {
      // this should only be shown to users that are mucking with the system
      Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.UnableToEdit', 'You do not have permission to edit a poll.'));
      return FALSE;
    }

    // Don't trust the discussion ID implicitly
    $DiscussionID = GetValue('DiscussionID', $Sender->EventArguments, 0);
    if($DiscussionID == 0) {
      $Error = Wrap('Error', 'h1') . Wrap('Invalid discussion id', 'p');
      return FALSE;
    }

    $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments, array());

    // Unchecking the poll option will remove the poll
    if(!GetValue('DP_Attach', $FormPostValues)) {
      // Delete existing poll
      if($DPModel->Exists($DiscussionID)) {
        Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.PollRemoved', 'The attached poll has been removed'));
        $DPModel->Delete($DiscussionID);
        return FALSE;
      }
    }

    if($DPModel->Exists($DiscussionID)) {
      // Skip saving if a poll exists
      Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.AlreadyExists', 'This poll already exists, poll was not updated'));
      return FALSE;
    }

    // Check to see if there are already poll responses; exit
    if($DPModel->HasResponses($DiscussionID) &&
            !$Session->CheckPermission('Plugins.DiscussionPolls.Manage')) {

      Gdn::Controller()->InformMessage(T('Plugins.DiscussionPolls.UnableToEditAfterResponses', 'You do not have permission to edit a poll with responses.'));
      return FALSE;
    }

    // Validate that all poll fields are filled out
    $Invalid = FALSE;
    $Error = '';
    if(trim($FormPostValues['DP_Title']) == FALSE) {
      $Invalid = TRUE;
      $Error = 'You must enter a valid poll title!';
    }

    foreach($FormPostValues['DP_Questions'] as $Index => $Question) {
      if(trim($Question) == FALSE) {
        $Invalid = TRUE;
        $Error = 'You must enter valid question text!';
        break;
      }
      foreach($FormPostValues['DP_Options' . $Index] as $Option) {
        if(trim($Option) == FALSE) {
          $Invalid = TRUE;
          $Error = 'You must enter valid option text!';
          break;
        }
      }
    }

    if($Invalid) {
      // fail silently since this shouldn't happen
      $Error = Wrap('Error', 'h1') . Wrap($Error, 'p');
      return FALSE;
    }
    else {
      // save poll form fields
      $DPModel->Save($FormPostValues);
      return TRUE;
    }
  }

  /**
   * Remove attached poll when discussion is deleted
   * @param VanillaModel $Sender DiscussionModel
   */
  public function DiscussionModel_DeleteDiscussion_Handler($Sender) {
    // Get discussionID that is being deleted
    $DiscussionID = $Sender->EventArguments['DiscussionID'];

    // Delete via model
    $DPModel = new DiscussionPollsModel();
    $DPModel->Delete($DiscussionID);
  }

  /**
   * Determines what part of the poll (if any) needs to be rendered
   * Checks permissions and displays any tools available to user
   * @param VanillaController $Sender
   * @return type
   */
  protected function _PollInsertion($Sender) {
    //echo '<pre>'; var_dump($Sender->Discussion); echo '</pre>';
    $Discussion = $Sender->Discussion;
    $Session = Gdn::Session();
    $DPModel = new DiscussionPollsModel();

    // Does an attached poll exist?
    if($DPModel->Exists($Discussion->DiscussionID)) {
      $Results = FALSE;
      $Closed = FALSE;
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
      if($DPModel->HasAnswered($Poll->PollID, $Session->UserID) || !$Session->IsValid() || $Closed) {
        $Results = TRUE;

        // Render results
        $this->_RenderResults($Poll);
      }
      else {
        // Render the submission form
        $this->_RenderVotingForm($Sender, $Poll, $Session->UserID);
      }

      // Render poll tools
      // Owner and Plugins.DiscussionPolls.Manage gets delete if exists and attach if it doesn't
      // Plugins.DiscussionPolls.View gets show results if the results aren't shown
      $Tools = '';
      if($Discussion->InsertUserID == $Session->UserID || $Session->CheckPermission('Plugins.DiscussionPolls.Manage')) {
        $Tools .= Wrap(
                Anchor(T('Delete Poll'), '/discussion/poll/delete/' . $Poll->PollID), 'li', array('id' => 'DP_Remove')
        );
      }

      if(!$Results && C('Plugins.DiscussionPolls.EnableShowResults', TRUE)) {
        $Tools .= Wrap(
                Anchor(T('Show Results'), '/discussion/poll/results/' . $Poll->PollID), 'li', array('id' => 'DP_Results')
        );
      }

      if($Tools != '') {
        echo Wrap($Tools, 'ul', array('id' => 'DP_Tools'));
      }
    }
    else {
      // Poll does not exist
      if($Discussion->InsertUserID == $Session->UserID || $Session->CheckPermission('Plugins.DiscussionPolls.Manage')) {
        echo Wrap(
                Wrap(
                        Anchor('Attach Poll', '/vanilla/post/editdiscussion/' . $Discussion->DiscussionID), 'li'), 'ul', array('id' => 'DP_Tools')
        );
      }
    }
  }

  /**
   * Renders a poll object as results
   * @param stdClass $Poll the poll object we are rendering
   * @param boolean $Echo echo or return result string
   * @return mixed Will return string if $Echo is false, will return true otherwise
   */
  protected function _RenderResults($Poll, $Echo = TRUE) {
    $Result = '<div class="DP_ResultsForm">';
    $Result .= $Poll->Title;

    $Result .= '<ol class="DP_ResultQuestions">';
    foreach($Poll->Questions as $Question) {
      $Result .= '<li class="DP_ResultQuestion">';
      $Result .= Wrap($Question->Title, 'span');
      $Result .= Wrap(sprintf(Plural($Question->CountResponses, '%s vote', '%s votes'), $Question->CountResponses), 'span', array('class' => 'Number DP_VoteCount'));

      // k is used to have different option bar colors
      $k = $Question->QuestionID % 10;
      $Result .= '<ol class="DP_ResultOptions">';
      foreach($Question->Options as $Option) {
        $string = Wrap($Option->Title, 'div');
        $Percentage = number_format(($Option->CountVotes / $Question->CountResponses * 100), 2);
        if($Percentage < 10) {
          $Percentage = $Percentage . '%';
          // put the text on the outside
          $string .= '<span class="DP_Bar DP_Bar-' . $k . '" style="width: ' . $Percentage . ';">&nbsp</span>' . $Percentage;
        }
        else {
          $Percentage = $Percentage . '%';
          // put the text on the inside
          $string .= '<span class="DP_Bar DP_Bar-' . $k . '" style="width: ' . $Percentage . ';">' . $Percentage . '</span>';
        }

        $Result .= Wrap($string, 'li', array('class' => 'DP_ResultOption'));

        $k++;
        $k = $k % 10;
      }
      $Result .= '</ol>';
      $Result .= '</li>';
    }
    $Result .= '</ol>';
    $Result .= '</div>';

    if($Echo) {
      echo $Result;
      return TRUE;
    }
    else {
      return $Result;
    }
  }

  /**
   * Renders a voting form for a poll object
   * @param VanillaController $Sender controller object
   * @param stdClass $Poll poll object
   * @param boolean $Echo echo or return result string
   * @return mixed Will return string if $Echo is false, will return true otherwise
   */
  protected function _RenderVotingForm($Sender, $Poll, $Echo = TRUE) {
    // Render the submission form
    $Result = '<div class="DP_AnswerForm">';
    $Result .= $Poll->Title;
    $Sender->PollForm = new Gdn_Form();
    $Sender->PollForm->AddHidden('DiscussionID', $Poll->DiscussionID);
    $Sender->PollForm->AddHidden('PollID', $Poll->PollID);
    
    //add error message passed through session stash
    if($Sender->Data('DiscussionPollsMessage'))
      $Sender->PollForm->AddError($Sender->Data('DiscussionPollsMessage'));

    $Result .= $Sender->PollForm->Open(array('action' => Url('/discussion/poll/submit/'), 'method' => 'post'));
    $Result .= $Sender->PollForm->Errors();

    $m = 0;
    // Render poll questions
    $Result .= '<ol class="DP_AnswerQuestions">';
    foreach($Poll->Questions as $Question) {
      $Result .= '<li class="DP_AnswerQuestion">';
      $Result .= $Sender->PollForm->Hidden('DP_AnswerQuestions[]', array('value' => $Question->QuestionID));
      $Result .= Wrap($Question->Title, 'span');
      $Result .= '<ol class="DP_AnswerOptions">';
      foreach($Question->Options as $Option) {
        $Result .= Wrap($Sender->PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID)), 'li');
      }
      $Result .= '</ol>';
      $Result .= '</li>';
      $m++;
    }
    $Result .= '</ol>';

    $Result .= $Sender->PollForm->Close('Submit');
    $Result .= '</div>';

    if($Echo) {
      echo $Result;
      return TRUE;
    }
    else {
      return $Result;
    }
  }

  /**
   * Setup database structure for model
   */
  protected function Structure() {
    $Database = Gdn::Database();
    $Construct = $Database->Structure();

    $Construct->Table('DiscussionPolls');
    $Construct
            ->PrimaryKey('PollID')
            ->Column('DiscussionID', 'int', FALSE, 'key')
            ->Column('Text', 'varchar(140)')
            ->Column('Open', 'tinyint(1)', '1')
            ->Set();

    $Construct->Table('DiscussionPollQuestions');
    $Construct
            ->PrimaryKey('QuestionID')
            ->Column('PollID', 'int', FALSE, 'key')
            ->Column('Text', 'varchar(140)')
            ->Column('CountResponses', 'int', '0')
            ->Set();

    $Construct->Table('DiscussionPollQuestionOptions');
    $Construct
            ->PrimaryKey('OptionID')
            ->Column('QuestionID', 'int', FALSE, 'key')
            ->Column('PollID', 'int', FALSE, 'key')
            ->Column('Text', 'varchar(140)')
            ->Column('CountVotes', 'int', '0')
            ->Set();

    $Construct->Table('DiscussionPollAnswers');
    $Construct
            ->PrimaryKey('AnswerID')
            ->Column('PollID', 'int', FALSE, 'key')
            ->Column('QuestionID', 'int', FALSE, 'key')
            ->Column('UserID', 'int', FALSE, 'key')
            ->Column('OptionID', 'int', TRUE, 'key')
            ->Set();
  }

  /**
   * This is executed when enabled via the dashboard
   * It sets up the database and permissions used
   */
  public function Setup() {
    // Register permissions
    $PermissionModel = Gdn::PermissionModel();
    $PermissionModel->Define(
            array(
                'Plugins.DiscussionPolls.Add',
                'Plugins.DiscussionPolls.View' => 1,
                'Plugins.DiscussionPolls.Vote',
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
        'Plugins.DiscussionPolls.View' => 1,
        'Plugins.DiscussionPolls.Vote' => 1
    ));

    // Set initial moderator permissions.
    $PermissionModel->Save(array(
        'Role' => 'Moderator',
        'Plugins.DiscussionPolls.Add' => 1,
        'Plugins.DiscussionPolls.View' => 1,
        'Plugins.DiscussionPolls.Vote' => 1,
        'Plugins.DiscussionPolls.Manage' => 1
    ));

    // Set initial admininstrator permissions.
    $PermissionModel->Save(array(
        'Role' => 'Administrator',
        'Plugins.DiscussionPolls.Add' => 1,
        'Plugins.DiscussionPolls.View' => 1,
        'Plugins.DiscussionPolls.Vote' => 1,
        'Plugins.DiscussionPolls.Manage' => 1
    ));

    // Set up the db structure
    $this->Structure();
  }

  /**
   * Run when a plugin is disabled via dashboard
   * Right now it only removes permissions on 2.1b1+
   */
  public function OnDisable() {
    // Deregister permissions (only in 2.1+)
    if(version_compare(APPLICATION_VERSION, '2.1b1', '>=')) {
      $PermissionModel = Gdn::PermissionModel();
      $PermissionModel->Undefine(
              array(
                  'Plugins.DiscussionPolls.Add',
                  'Plugins.DiscussionPolls.View',
                  'Plugins.DiscussionPolls.Vote',
                  'Plugins.DiscussionPolls.Manage'
      ));
    }
  }

}
