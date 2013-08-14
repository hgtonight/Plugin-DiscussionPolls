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
	* Gets a poll object associated with a discussion ID. Does not include individual user choices
	*/
	public function Get($DiscussionID) {
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
			->Where('p.DiscussionID', $DiscussionID);
		
		$DBResult = $this->SQL->Get()->Result();
		
		//echo '<pre>'; var_dump($DBResult); echo '</pre>';
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
		$PollID = ArrayValue('Discussion/PollID', $FormPostValues, '');
		echo '<pre>'; var_dump($FormPostValues); echo '</pre>';
		
		// Determine if we are updating or inserting
		$Insert = $PollID == '' ? TRUE : FALSE;
		
		/*if($Insert) {
			echo 'Inserted!';
			// Insert the poll
			$this->SQL->Insert('DiscussionPolls', array(
				'DiscussionID' => $FormPostValues['DiscussionID'],
				'Text' => $FormPostValues['Discussion/DiscussionPollTitle']));
				
			// Select the poll ID
			$this->SQL
				->Select('p.PollID')
				->From('DiscussionPolls p')
				->Where('p.DiscussionID', $FormPostValues['DiscussionID']);
				
			$PollID = $this->SQL->Get()->FirstRow()->PollID;
			//echo '<pre>'; var_dump($PollID); echo '</pre>';
			
			// Insert the questions
			foreach($FormPostValues['Discussion/DiscussionPollsQuestions'] as $Index => $Question) {
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
			
			//echo '<pre>'; var_dump($QuestionIDs); echo '</pre>';
			// Insert the Options
			foreach($QuestionIDs as $Index => $QuestionID) {
				$QuestionOptions = ArrayValue('Discussion/DiscussionPollsOptions'.$Index, $FormPostValues);
				//echo '<pre>'; var_dump($QuestionOptions); echo '</pre>';
				foreach($QuestionOptions as $Option) {
					$this->SQL
						->Insert('DiscussionPollQuestionOptions', array(
							'QuestionID' => $QuestionID->QuestionID,
							'PollID' => $PollID,
							'Text' => $Option)
						);
				}
				//echo '<pre>'; var_dump($QuestionID['QuestionID']); echo '</pre>';
			}
		}
		else {*/
			echo 'Updating!';
			//Update an existing poll
			
			// Get the existing poll object
			$this->Get($FormPostValues['DiscussionID']);
			$this->SQL
				->Update('DiscussionPolls p')
				->Set('PollID', $FormPostValues['DiscussionID'])
				->Set('Text', $FormPostValues['Discussion/DiscussionPollTitle'])
				->Where('p.PollID', $PollID)
				->Put();
		//}
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
	public function Delete($DiscussionID) {
		// find the Poll ID associated with the discussion ID
		$this->SQL
			->Select('p.PollID')
			->From('DiscussionPolls p')
			->Where('p.DiscussionID', $DiscussionID);		
		$PollID = $this->SQL->Get()->FirstRow()->PollID;
		
		// TODO: Optimize
		$this->SQL->Delete('DiscussionPolls', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollQuestions', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollQuestionOptions', array('PollID' => $PollID));
		$this->SQL->Delete('DiscussionPollAnswers', array('PollID' => $PollID));
	}
	
	/**
	* Closes poll
	* TODO: Future feature
	*/
	public function Close($ID) {}
}