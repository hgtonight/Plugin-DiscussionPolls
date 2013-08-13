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
	* Determines if the poll exists
	*/
	public function Exists($ID) {
		$this->SQL
			->Select('PollID')
			->From('DiscussionPolls')
			->Where('PollID', $ID);
		
		$Data = $this->SQL->Get()->Result();
		// echo '<pre>'; var_dump($Data); echo '</pre>';
		return !empty($Data);
	}
	
	/**
	* Determines if the poll has been answered at all
	*/
	public function HasResponses($ID) {
		$this->SQL
			->Select('PollID')
			->From('DiscussionPollAnswers')
			->Where('PollID', $ID);
		
		$Data = $this->SQL->Get()->Result();
		// echo '<pre>'; var_dump($Data); echo '</pre>';
		return !empty($Data);
	}
	
	/**
	* Gets a poll object. Does not include individual user choices
	*/
	public function Get($ID) {
		$this->SQL
			->Select('p.*')
			->Select('q.Text', '', 'Question')
			->Select('q.QuestionID')
			->Select('o.Text', '', 'Option')
			->Select('o.Score', '', 'OptionScore')
			->Select('o.OptionID')
			->From('DiscussionPolls p')
			->Join('DiscussionPollQuestions q', 'p.PollID = q.PollID')
			->Join('DiscussionPollQuestionOptions o', 'q.QuestionID = o.QuestionID')
			->Where('p.PollID', $ID);
		
		$DBResult = $this->SQL->Get()->Result();
		
		//echo '<pre>'; var_dump($DBResult); echo '</pre>';
		
		$Data = array(
			'PollID' => $DBResult[0]->PollID,
			'DiscussionID' => $DBResult[0]->DiscussionID,
			'Title' => $DBResult[0]->Text,
			'IsOpen' => $DBResult[0]->Open,
			'Questions' => array()
		);
		// Loop through the result and assemble an associative array
		foreach($DBResult as $Row) {
			//echo '<pre>'; var_dump($Row); echo '</pre>';
			if(array_key_exists($Row->QuestionID, $Data['Questions'])) {
				// Just add the option
				$Data['Questions'][$Row->QuestionID]['Options'][] = array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'Count' => $Row->OptionScore);
			}
			else {
				// First time seeing this question
				// Add it and the first option
				$Data['Questions'][$Row->QuestionID] = array(
					'QuestionID' => $Row->QuestionID,
					'Title' => $Row->Question,
					'Options' => array(array('OptionID' => $Row->OptionID, 'Title' => $Row->Option, 'Count' => $Row->OptionScore))
				);
			}
		}
		
		// convert array to object
		$DObject = json_decode(json_encode($Data));
		return $DObject;
	}
	
	/**
	* Saves the poll
	*/
	public function Save($FormPostValues) {
	
	}
	
	/**
	* Gets an answer object. Does not include the poll object
	*/
	public function GetAnswer($ID, $UserID) {}
	
	/**
	* Saves the poll answer for a specific user
	*/
	public function SaveAnswer($FormPostValues) {}
	
	/**
	* Removes all data associated with the poll
	*/
	public function Delete($ID) {}
	
	/**
	* Closes poll
	* TODO: Future feature
	*/
	public function Close($ID) {}
}