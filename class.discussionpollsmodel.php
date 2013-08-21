<?php if (!defined('APPLICATION')) exit();
/*	Copyright 2013 Zachary Doll All rights reserved. Do not distribute. */
class DiscussionPollsModel extends Gdn_Model {
	/**
    * Class constructor. Defines the related database table name.
    */
	public function  __construct($Name = '') {
		parent::__construct('DiscussionPolls');
	}
	
	/**
	* Determines if a poll associated with the discussion exists
	*/
	public function Exists($DiscussionID) {
		$this->SQL
			->Select('PollID')
			->From('DiscussionPolls')
			->Where('DiscussionID', $DiscussionID);
		
		$Data = $this->SQL->Get()->Result();
		// echo '<pre>'; var_dump($Data); echo '</pre>';
		return !empty($Data);
	}
	
	/**
	* Determines if a poll associated with the discussion has been answered at all
	*/
	public function HasResponses($DiscussionID) {
		$this->SQL
			->Select('p.PollID')
			->From('DiscussionPolls p')
			->Join('DiscussionPollAnswers a', 'p.PollID = a.PollID')
			->Where('p.DiscussionID', $DiscussionID);
		
		$Data = $this->SQL->Get()->Result();
		// echo '<pre>'; var_dump($Data); echo '</pre>';
		return !empty($Data);
	}
	
	/**
	* Gets a poll object associated with a poll ID. Does not include individual user choices
	*/
	public function Get($PollID) {
		$this->SQL
			->Select('p.*')
			->Select('q.Text', '', 'Question')
			->Select('q.QuestionID')
			->Select('q.CountResponses')
			->Select('o.Text', '', 'Option')
			->Select('o.CountVotes', '', 'CountVotes')
			->Select('o.OptionID')
			->From('DiscussionPolls p')
			->Join('DiscussionPollQuestions q', 'p.PollID = q.PollID')
			->Join('DiscussionPollQuestionOptions o', 'q.QuestionID = o.QuestionID')
			->Where('p.PollID', $PollID);
		
		$DBResult = $this->SQL->Get()->Result();
		
		if(!empty($DBResult)) {
			$Data = array(
				'PollID' => $DBResult[0]->PollID,
				'DiscussionID' => $DBResult[0]->DiscussionID,
				'Title' => $DBResult[0]->Text,
				'IsOpen' => $DBResult[0]->Open,
				'Questions' => array()
			);
		}
		else {
			// Pass an empty array back
			$Data = array(
				'PollID' => '',
				'DiscussionID' => '',
				'Title' => '',
				'IsOpen' => '',
				'Questions' => array()
			);
		}
		// Loop through the result and assemble an associative array
		foreach($DBResult as $Row) {
			if(array_key_exists($Row->QuestionID, $Data['Questions'])) {
				// Just add the option
				$Data['Questions'][$Row->QuestionID]['Options'][] = array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'CountVotes' => $Row->CountVotes);
			}
			else {
				// First time seeing this question
				// Add it and the first option
				$Data['Questions'][$Row->QuestionID] = array(
					'QuestionID' => $Row->QuestionID,
					'Title' => $Row->Question,
					'Options' => array(array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'CountVotes' => $Row->CountVotes)),
					'CountResponses' => $Row->CountResponses
				);
			}
		}
		
		// convert array to object
		$DObject = json_decode(json_encode($Data));
		return $DObject;
	}
	
	/**
	* Convenience method to get a poll object associated with a discussion ID. Does not include individual user choices
	*/
	public function GetByDiscussionID($DiscussionID) {
		$this->SQL
			->Select('p.PollID')
			->From('DiscussionPolls p')
			->Where('p.DiscussionID', $DiscussionID);
		
		$PollID = $this->SQL->Get()->FirstRow()->PollID;
		return $this->Get($PollID);
	}
	
	/**
	* Saves the poll
	*/
	public function Save($FormPostValues) {
		// Insert the poll
		$this->SQL->Insert('DiscussionPolls', array(
			'DiscussionID' => $FormPostValues['DiscussionID'],
			'Text' => $FormPostValues['DP_Title']));
			
		// Select the poll ID
		$this->SQL
			->Select('p.PollID')
			->From('DiscussionPolls p')
			->Where('p.DiscussionID', $FormPostValues['DiscussionID']);
			
		$PollID = $this->SQL->Get()->FirstRow()->PollID;
		
		// Insert the questions
		foreach($FormPostValues['DP_Questions'] as $Index => $Question) {
			$this->SQL
				->Insert('DiscussionPollQuestions', array(
					'PollID' => $PollID,
					'Text' => $Question)
				);
		}
		
		// Select the question IDs
		$this->SQL
			->Select('q.QuestionID')
			->From('DiscussionPollQuestions q')
			->Where('q.PollID', $PollID);
		$QuestionIDs = $this->SQL->Get()->Result();
		
		// Insert the Options
		foreach($QuestionIDs as $Index => $QuestionID) {
			$QuestionOptions = ArrayValue('DP_Options'.$Index, $FormPostValues);
			//echo '<pre>'; var_dump($QuestionOptions); echo '</pre>';
			foreach($QuestionOptions as $Option) {
				$this->SQL
					->Insert('DiscussionPollQuestionOptions', array(
						'QuestionID' => $QuestionID->QuestionID,
						'PollID' => $PollID,
						'Text' => $Option)
					);
			}
		}
	}
	
	/**
	* Returns whether or not a user has answered a poll.
	*/
	public function HasAnswered($PollID, $UserID) {
		$this->SQL
			->Select('q.PollID, a.UserID')
			->From('DiscussionPollQuestions q')
			->Join('DiscussionPollAnswers a', 'q.QuestionID = a.QuestionID')
			->Where('q.PollID', $PollID)
			->Where('a.UserID', $UserID);
			
		$Result = $this->SQL->Get()->Result();
		
		return !empty($Result);
	}
	
	/**
	* Saves a poll answer for a specific user
	*/
	public function SaveAnswer($FormPostValues, $UserID) {
		if($this->HasAnswered($FormPostValues['PollID'], $UserID)) {
			return FALSE;
		}
		else {
			foreach($FormPostValues['DP_AnswerQuestions'] as $Index => $QuestionID) {
				$MemberKey = 'DP_Answer'.$Index;
				$this->SQL
					->Insert('DiscussionPollAnswers', array(
						'PollID' => $FormPostValues['PollID'],
						'QuestionID' => $QuestionID,
						'UserID' => $UserID,
						'OptionID' => $FormPostValues[$MemberKey])
					);
				
				$this->SQL
					->Update('DiscussionPollQuestions')
					->Set('CountResponses', 'CountResponses + 1', FALSE)
					->Where('QuestionID', $QuestionID)
					->Put();
				
				$this->SQL
					->Update('DiscussionPollQuestionOptions')
					->Set('CountVotes', 'CountVotes + 1', FALSE)
					->Where('OptionID', $FormPostValues[$MemberKey])
					->Put();
				//echo '<pre>'; var_dump($this->SQL); echo '</pre>';
			}
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	* Removes all data associated with the poll id
	*/
	public function Delete($PollID) {
		// TODO: Optimize
		$this->SQL->Delete('DiscussionPolls', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollQuestions', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollQuestionOptions', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollAnswers', array('PollID' => $PollID));
	}
	
	/**
	* Closes poll
	*/
	public function Close($DiscussionID) {
		$this->SQL
			->Update('DiscussionPolls p')
			->Set('Open', 0)
			->Where('p.DiscussionID', $DiscussionID)
			->Put();
	}
	
	/**
	* Returns if the poll is closed or open.
	* If the poll doesn't exist, it will return true.
	*/
	public function IsClosed($DiscussionID) {
		$this->SQL
			->Select('p.Open')
			->From('DiscussionPolls p')
			->Where('p.DiscussionID', $DiscussionID);		
		$IsOpen = $this->SQL->Get()->FirstRow()->Open;
		
		return !$IsOpen;
	}
}