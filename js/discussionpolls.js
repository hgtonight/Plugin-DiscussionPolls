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
jQuery(document).ready(function($) {
  // hijack the results click
  $('#DP_Results a').click(function(event) {
    event.preventDefault();

    if($(this).html() === 'Show Results') {
      // Load from ajax if they don't exist
      if($('.DP_ResultsForm').length === 0) {
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

            // Repeated here to account for slow hosts
            $('.DP_AnswerForm').fadeOut('slow', function() {
              $('.DP_ResultsForm').fadeIn('slow');
            });
          }
        });
      }
      else {
        // Bring results to front
        $('.DP_AnswerForm').fadeOut('slow', function() {
          $('.DP_ResultsForm').fadeIn('slow');
        });
      }
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
    afterConfirm: function(json, sender) {
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