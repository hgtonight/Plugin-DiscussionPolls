/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
jQuery(document).ready(function($) {
  // hijack the results click
  $('#DP_Results a').click(function(event) {
    event.preventDefault();

    if ($(this).html() === 'Show Results') {
      // Load from ajax if they don't exist
      if ($('.DP_ResultsForm').length === 0) {
        // Load Results from ajax
        var btn = this;
        $.ajax({
          url: $(btn).attr('href'),
          global: false,
          type: 'GET',
          data: 'DeliveryType=VIEW',
          dataType: 'json',
          success: function(Data) {
            $('.DP_AnswerForm').after(Data.html);
            $('.DP_ResultsForm').hide();
          }
        });
      }

      // Bring results to front
      $('.DP_AnswerForm').fadeOut('slow', function() {
        $('.DP_ResultsForm').fadeIn('slow');
      });

      // Change tool mode
      $(this).html('Show Poll Form');
    }
    else {
      // Bring poll form to front
      $('.DP_ResultsForm').fadeOut('slow', function() {
        $('.DP_AnswerForm').fadeIn('slow');
      });

      // Change tool mode
      $(this).html('Show Results');
    }
  });

  // hijack the delete click
  $('#DP_Remove a').popup({
    confirm: true,
    followConfirm: false,
    afterConfirm: function() {
      // Remove all poll tools and forms
      $('.DP_AnswerForm').slideUp('slow', function() {
        $(this).remove();
      });
      $('.DP_ResultsForm').slideUp('slow', function() {
        $(this).remove();
      });
      $('#DP_Tools').slideUp('slow', function() {
        $(this).remove();
      });
    }
  },
  'Are you sure you want to delete this poll?');
});