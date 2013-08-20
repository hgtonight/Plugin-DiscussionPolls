/* Copyright 2013 Zachary Doll All rights reserved. Do not distribute.*/
jQuery(document).ready(function($) {
	// hijack the results click
	$('#DP_Results a').click( function(event) {
		event.preventDefault();
		
		if($(this).html() == 'Show Results') {
			// Load from ajax if they don't exist
			if($('.DiscussionPollsResultsForm').length == 0) {
				// Load Results from ajax
				var btn = this;
				$.ajax({
					url: $(btn).attr('href'),
					global: false,
					type: 'GET',
					data: 'DeliveryType=VIEW',
					dataType: 'html',
					success: function(Data) {
						$('.DiscussionPollsAnswerForm').after(Data);
						$('.DiscussionPollsResultsForm').hide();
						}
					});
			}
			
			// Bring results to front
			$('.DiscussionPollsAnswerForm').fadeOut('slow', function() {
				$('.DiscussionPollsResultsForm').fadeIn('slow');
			});
			
			// Change tool mode
			$(this).html('Show Poll Form');
		}
		else {
			// Bring poll form to front
			$('.DiscussionPollsResultsForm').fadeOut('slow', function() {
				$('.DiscussionPollsAnswerForm').fadeIn('slow');
			});
			
			// Change tool mode
			$(this).html('Show Results');
		}
	});
	
	// hijack the delete click
	$('#DP_Remove a').click( function(event) {
		event.preventDefault();
		$.popup({
			confirm: true,
			followConfirm: false,
			afterConfirm: function(json, sender) {
				$(sender).parents('tr').remove();
			}
		}, 'Are you sure you want to delete this poll?');
	});
});