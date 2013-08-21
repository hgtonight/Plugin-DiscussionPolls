/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
jQuery(document).ready(function($) {
  var Closed = gdn.definition('DP_Closed'); // This determines if the poll is closed and will be used in future releases
  var QuestionString = gdn.definition('DP_EmptyQuestion'); // This is an empty form question; it is used to sidestep differences between 2.0 and 2.1 form functions
  var ExistingPoll = false; // Is this a pre-existing poll page? Assume it is a new page

  // If there is a question string defined, this is a new poll
  if (QuestionString !== 'DP_EmptyQuestion') {
    // Hide the form initially
    $('#DP_Form').hide();

    // Uncheck attach poll initially
    $('#Form_DP_Attach').prop('checked', false);
  }
  else {
    ExistingPoll = true;
    // Hide the extra questions and make the buttons make sense
    if ($('fieldset.DP_Question').length > 1) {
      // Hide all but the first question form
      $('fieldset.DP_Question:gt(0)').hide();

      $('#DP_NextQuestion').text('Next Question');
    }
  }

  // Show/hide the form when the attach poll box is checked
  $('#Form_DP_Attach').change(function(event) {
    event.preventDefault();
    if ($(this).is(':checked')) {
      $('#DP_Form').slideDown();
      if (ExistingPoll) {
        // TODO: Inspect for negative side effects of removing the inform messages container
        $('.InformMessages').fadeOut(500, function() {
          $(this).remove();
        });
      }
    } else {
      $('#DP_Form').slideUp();
      if (ExistingPoll) {
        gdn.informMessage('<span class="InformSprite Gears"></span> Poll will be removed permanently if you save the discussion!', 'Dismissable HasSprite');
      }
    }

    //$('#DiscussionPollsForm').slideToggle();		
  });


  // Add an another option field
  $('#DP_AddOption').click(function(event) {
    event.preventDefault();
    if (Closed) {
      $(this).hide();
    }
    else {
      // find the current question
      var questionSet = $('fieldset.DP_Question:visible');
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
      $('fieldset.DP_Question:visible').children('label:last').hide().slideDown();
      $('fieldset.DP_Question:visible').children('.TextBoxWrapper:last').hide().slideDown();
    }
  });

  // Move to the next question
  $('#DP_NextQuestion').click(function(event) {
    event.preventDefault();
    if (Closed) {
      // find the current question
      var questionNum = $('fieldset.DP_Question:visible').attr('id').replace(/DP_Question(\d+)/g, '$1');
      var nextQuestionNum = parseInt(questionNum) + 1;
      if ($('#DP_Question' + nextQuestionNum).length === 0) {
        // Don't animate since there isn't another question
        $('#DP_NextQuestion').text('');
      }
      else {
        // animate to the next question
        $('#DP_Question' + questionNum).fadeOut(400, function() {
          $('#DP_Question' + nextQuestionNum).fadeIn();
          // Update previous question text
          $('#DP_PreviousQuestion').text('Previous Question');

          nextQuestionNum++;
          if ($('#DP_Question' + nextQuestionNum).length === 0) {
            $('#DP_NextQuestion').text('');
          }
        });
      }
    }
    else {
      // find the current question
      var questionNum = $('fieldset.DP_Question:visible').attr('id').replace(/DP_Question(\d+)/g, '$1');
      var nextQuestionNum = parseInt(questionNum) + 1;
      if ($('#DP_Question' + nextQuestionNum).length === 0) {
        // Add another question field since there are no others

        // Use a definition so we can maintain compatibility with 2.0.18.8
        // I relish the day I can just support the new form/model system introduced in 2.1
        var newQuestion = $(QuestionString).insertAfter('#DP_Question' + questionNum);

        // Hide and change the main id immediately
        $(newQuestion).hide().attr('id', 'DP_Question' + nextQuestionNum);

        // Change the subids on labels
        $('#DP_Question' + nextQuestionNum + ' label').attr('for', function() {
          return $(this).attr('for').replace(/(\d+)/, nextQuestionNum);
        });

        // Change the question label text
        $('#DP_Question' + nextQuestionNum + ' label:first').html(function() {
          return $(this).html().replace(/(\d+)/, nextQuestionNum + 1);
        });


        // Change the subids on inputs
        $('#DP_Question' + nextQuestionNum + ' input').attr('id', function() {
          return $(this).attr('id').replace(/(\d+)/, nextQuestionNum);
        });

        // Change the names on inputs
        $('#DP_Question' + nextQuestionNum + ' input').attr('name', function() {
          return $(this).attr('name').replace(/(\d+)/, nextQuestionNum);
        });
        // TODO: This was the original string I used that broke 2.0 compatibility
        /* $('#DP_Question' + questionNum).after(
         '<fieldset id="DP_Question' + nextQuestionNum + '" class="DP_Question">' +
         '<label for="Form_DP_Questions0">Question #' + (nextQuestionNum + 1) + '</label>' +
         '<div class="TextBoxWrapper">' +
         '<input id="DP_Questions0" name="DP_Questions[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
         '</div>' +
         '<label for="Form_DP_Options' + nextQuestionNum + '-dot-0">Option #1</label>' +
         '<div class="TextBoxWrapper">' +
         '<input id="DP_Options' + nextQuestionNum + '.0" name="DP_Options' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
         '</div>' +
         '<label for="Form_DP_Options' + nextQuestionNum + '-dot-1">Option #2</label>' +
         '<div class="TextBoxWrapper">' +
         '<input id="DP_Options' + nextQuestionNum + '.1" name="DP_Options' + nextQuestionNum + '[]" value="" maxlength="100" class="InputBox BigInput" type="text">' +
         '</div>' +
         '</fieldset>'
         );
         $('#DPQuestion' + nextQuestionNum).hide(); */
      }

      // animate to the next question
      $('#DP_Question' + questionNum).fadeOut(400, function() {
        $('#DP_Question' + nextQuestionNum).fadeIn();
        // Update previous question text
        $('#DP_PreviousQuestion').text('Previous Question');

        nextQuestionNum++;
        if ($('#DP_Question' + nextQuestionNum).length === 0) {
          $('#DP_NextQuestion').text('Add a Question');
        }
      });
    }
  });

  // Move to the previous question
  $('#DP_PreviousQuestion').click(function(event) {
    event.preventDefault();
    // find the current question
    var questionNum = $('fieldset.DP_Question:visible').attr('id').replace(/DP_Question(\d+)/g, '$1');
    var previousQuestionNum = parseInt(questionNum) - 1;
    if (previousQuestionNum >= 0) {
      // animate to the previous question
      $('#DP_Question' + questionNum).fadeOut(400, function() {
        $('#DP_Question' + previousQuestionNum).fadeIn();
      });

      $('#DP_NextQuestion').text('Next Question');
    }

    if (previousQuestionNum === 0) {
      $(this).text(' ');
    }
  });

});