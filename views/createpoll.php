<?php if (!defined('APPLICATION')) exit();
/*	Copyright 2013 Zachary Doll All rights reserved */
echo 'Poll creation form!';

echo '<div class="P" id="DiscussionPollsForm">';
			//$this->Form->InputPrefix = 'Discussion';
			echo $this->Form->Label('Discussion Poll Title', 'DiscussionPollTitle');
			echo Wrap($this->Form->TextBox('DiscussionPollTitle', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			
			echo Anchor(' ', '/plugin/discussionpolls/', array('id' => 'DPPreviousQuestion'));
			// render the questions forms
			echo '<fieldset id="DPQuestion0" class="DiscussionPollsQuestion">';
			echo $this->Form->Label('Question #1', 'DiscussionPollsQuestions');
			echo Wrap($this->Form->TextBox('DiscussionPollsQuestions[0]', array('id' => 'DiscussionPollsQuestions0', 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			
			// start with two options 
			for($i = 0; $i < 2; $i++) {
				echo $this->Form->Label('Option #'.($i + 1), 'DiscussionPollsOptions0.'.$i);
				echo Wrap($this->Form->TextBox('DiscussionPollsOptions0['.$i.']', array('id' => 'DiscussionPollsOptions0.'.$i, 'maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
			}
			echo '</fieldset>';
			echo Anchor('Add a Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DPNextQuestion'));
			echo Anchor('Add an option', '/plugin/discussionpolls/addoption', array('id' => 'DPAddOption'));
		echo '</div>';
//echo '<pre>'; var_dump($this); echo '</pre>';

