/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
jQuery(document).ready(function($){
	// Hide the form initially
	$('#DiscussionPollsForm').hide();
	
	// Uncheck attach poll initially
	$('#Form_AttachDiscussionPoll').prop('checked', false);
	
	// Show/hide the form when the attach poll box is checked
	$('#Form_AttachDiscussionPoll').change( function(event) {
		event.preventDefault();
		$('#DiscussionPollsForm').slideToggle();
	});
	
	// Add an another option field
	$('#DPAddOption').click( function(event) {
		event.preventDefault();
		// find the current question
		var questionSet = $('fieldset.DiscussionPollsQuestion:visible');
		// clone the labels and input
		var label = $(questionSet).children('label:last').clone();
		var inputWrapper = $(questionSet).children('.TextBoxWrapper:last').clone();
		
		// find the option number
		var optionNum = $(label).text().replace(/Option #(\d+)/g, '$1');
		
		// change the input id
		var newInputID = $(inputWrapper).children().attr('id').replace(/(DiscussionPollOption\d+)\.(\d+)/g, '$1.' + optionNum);
		$(inputWrapper).children().attr('id', newInputID);
		
		optionNum++;
		
		// change the label
		$(label).text('Option #' + optionNum);
		
		// prepend the new inputs and slide them in
		$(questionSet).append(label);
		$(questionSet).append(inputWrapper);
		
		// animate in
		$('fieldset.DiscussionPollsQuestion:visible').children('label:last').hide().slideDown();
		$('fieldset.DiscussionPollsQuestion:visible').children('.TextBoxWrapper:last').hide().slideDown();
	});
	
	// Move to the next question
	$('#DPNextQuestion').click( function(event) {
		event.preventDefault();
		// find the current question
		var questionNum = $('fieldset.DiscussionPollsQuestion:visible').attr('id').replace(/DPQuestion(\d+)/g, '$1');
		var nextQuestionNum = parseInt(questionNum) + 1;
		if($('#DPQuestion' + nextQuestionNum).length == 0) {
			// Add another question field since there are no others
			$('#DPQuestion' + questionNum).after('<fieldset id="DPQuestion' + nextQuestionNum + '" class="DiscussionPollsQuestion"><label for="Form_DiscussionPollsQuestions">Question #' + (nextQuestionNum + 1) + '</label><div class="TextBoxWrapper"><input id="DiscussionPollsQuestions' + nextQuestionNum + '" name="Discussion/DiscussionPollsQuestions[]" value="" maxlength="100" class="InputBox BigInput" type="text"></div><label for="Form_DiscussionPollsOptions' + nextQuestionNum + '-dot-0">Option #1</label><div class="TextBoxWrapper"><input id="DiscussionPollsOptions' + nextQuestionNum + '.0" name="Discussion/DiscussionPollsOptions' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text"></div><label for="Form_DiscussionPollsOptions' + nextQuestionNum + '-dot-1">Option #2</label><div class="TextBoxWrapper"><input id="DiscussionPollsOptions' + nextQuestionNum + '.1" name="Discussion/DiscussionPollsOptions' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text"></div></fieldset>');
			$('#DPQuestion' + nextQuestionNum).hide();
		}
		
		// animate to the next question
		$('#DPQuestion' + questionNum).fadeOut(400, function() {
			$('#DPQuestion' + nextQuestionNum).fadeIn();
			// Update previous question text
			$('#DPPreviousQuestion').text('Previous Question');
			
			nextQuestionNum++;
			if($('#DPQuestion' + nextQuestionNum).length == 0) {
				$('#DPNextQuestion').text('Add a Question');
			}
		});		
	});
	
	// Move to the previous question
	$('#DPPreviousQuestion').click( function(event) {
		event.preventDefault();
		// find the current question
		var questionNum = $('fieldset.DiscussionPollsQuestion:visible').attr('id').replace(/DPQuestion(\d+)/g, '$1');
		var previousQuestionNum = parseInt(questionNum) - 1;
		if(previousQuestionNum >= 0) {
			// animate to the previous question
			$('#DPQuestion' + questionNum).fadeOut(400, function() {
				$('#DPQuestion' + previousQuestionNum).fadeIn();
			});
			
			$('#DPNextQuestion').text('Next Question');
		}
		
		if(previousQuestionNum == 0) {
			$(this).text(' ');
		}
	});
		
});