/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
jQuery(document).ready(function($){
	var Closed = gdn.definition('DiscussionPollClosed');
	var QuestionString = gdn.definition('DiscussionPollEmptyQuestion');
	
	// If there is a question string defined, this is a new poll
	if(QuestionString != 'DiscussionPollEmptyQuestion') {
		// Hide the form initially
		$('#DiscussionPollsForm').hide();
		
		// Uncheck attach poll initially
		$('#Form_AttachDiscussionPoll').prop('checked', false);
	}
	else {
		// Hide the extra questions and make the buttons make sense
		if($('fieldset.DiscussionPollsQuestion').length > 1) {
			// Hide all but the first question form
			$('fieldset.DiscussionPollsQuestion:gt(0)').hide();
			
			$('#DPNextQuestion').text('Next Question');
		}
	}
	
	// Show/hide the form when the attach poll box is checked
	$('#Form_AttachDiscussionPoll').change( function(event) {
		event.preventDefault();
		$('#DiscussionPollsForm').slideToggle();
	});
	
	
	// Add an another option field
	$('#DPAddOption').click( function(event) {
		event.preventDefault();
		if(Closed) {
			$(this).hide();
		}
		else {
			// find the current question
			var questionSet = $('fieldset.DiscussionPollsQuestion:visible');
			// clone the labels and input
			var label = $(questionSet).children('label:last').clone();
			var inputWrapper = $(questionSet).children('.TextBoxWrapper:last').clone();
			
			// find the option number
			var optionNum = $(label).text().replace(/Option #(\d+)/g, '$1');
			
			// Change the input id
			$(inputWrapper).children().attr('id', function() {
				return $(this).attr('id').replace(/\.(\d+)/, '.' + optionNum);
			});
			
			// clear the textbox
			$(inputWrapper).children().val('');
			
			// Change the id on the label
			$(label).attr('for', function() {
				return $(this).attr('for').replace(/-dot-(\d+)/, '-dot-' + optionNum);
			});
			
			optionNum++;
			
			// change the label text
			$(label).text('Option #' + optionNum);
			
			// prepend the new inputs and slide them in
			$(questionSet).append(label);
			$(questionSet).append(inputWrapper);
			
			// animate in
			$('fieldset.DiscussionPollsQuestion:visible').children('label:last').hide().slideDown();
			$('fieldset.DiscussionPollsQuestion:visible').children('.TextBoxWrapper:last').hide().slideDown();
		}
	});
	
	// Move to the next question
	$('#DPNextQuestion').click( function(event) {
		event.preventDefault();
		if(Closed) {
			// find the current question
			var questionNum = $('fieldset.DiscussionPollsQuestion:visible').attr('id').replace(/DPQuestion(\d+)/g, '$1');
			var nextQuestionNum = parseInt(questionNum) + 1;
			if($('#DPQuestion' + nextQuestionNum).length == 0) {
				// Don't animate since there isn't another question
				$('#DPNextQuestion').text('');
			}
			else {
				// animate to the next question
				$('#DPQuestion' + questionNum).fadeOut(400, function() {
					$('#DPQuestion' + nextQuestionNum).fadeIn();
					// Update previous question text
					$('#DPPreviousQuestion').text('Previous Question');
					
					nextQuestionNum++;
					if($('#DPQuestion' + nextQuestionNum).length == 0) {
						$('#DPNextQuestion').text('');
					}
				});
			}
		}
		else {
			// find the current question
			var questionNum = $('fieldset.DiscussionPollsQuestion:visible').attr('id').replace(/DPQuestion(\d+)/g, '$1');
			var nextQuestionNum = parseInt(questionNum) + 1;
			if($('#DPQuestion' + nextQuestionNum).length == 0) {
				// Add another question field since there are no others
				
				// Use a definition so we can maintain compatibility with 2.0.18.8
				// I relish the day I can just support the new form/model system introduced in 2.1
				var newQuestion = $(QuestionString).insertAfter('#DPQuestion' + questionNum);
				
				// Hide and change the main id immediately
				$(newQuestion).hide().attr('id', 'DPQuestion' + nextQuestionNum);
				
				// Change the subids on labels
				$('#DPQuestion' + nextQuestionNum + ' label').attr('for', function() {
					return $(this).attr('for').replace(/(\d+)/, nextQuestionNum);
				});
				
				// Change the question label text
				$('#DPQuestion' + nextQuestionNum + ' label:first').html(function() {
					return $(this).html().replace(/(\d+)/, nextQuestionNum + 1);
				});
				
				
				// Change the subids on inputs
				$('#DPQuestion' + nextQuestionNum + ' input').attr('id', function() {
					return $(this).attr('id').replace(/(\d+)/, nextQuestionNum);
				});
				
				// Change the names on inputs
				$('#DPQuestion' + nextQuestionNum + ' input').attr('name', function() {
					return $(this).attr('name').replace(/(\d+)/, nextQuestionNum);
				});
				// TODO: This was the original string I used that broke 2.0 compatibility
				/* $('#DPQuestion' + questionNum).after(
					'<fieldset id="DPQuestion' + nextQuestionNum + '" class="DiscussionPollsQuestion">' +
						'<label for="Form_DiscussionPollsQuestions0">Question #' + (nextQuestionNum + 1) + '</label>' +
						'<div class="TextBoxWrapper">' +
							'<input id="DiscussionPollsQuestions0" name="DiscussionPollsQuestions[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
						'</div>' +
						'<label for="Form_DiscussionPollsOptions' + nextQuestionNum + '-dot-0">Option #1</label>' +
						'<div class="TextBoxWrapper">' +
							'<input id="DiscussionPollsOptions' + nextQuestionNum + '.0" name="DiscussionPollsOptions' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
						'</div>' +
						'<label for="Form_DiscussionPollsOptions' + nextQuestionNum + '-dot-1">Option #2</label>' +
						'<div class="TextBoxWrapper">' +
							'<input id="DiscussionPollsOptions' + nextQuestionNum + '.1" name="DiscussionPollsOptions' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
						'</div>' +
					'</fieldset>'
				);
				$('#DPQuestion' + nextQuestionNum).hide(); */
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
		}
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