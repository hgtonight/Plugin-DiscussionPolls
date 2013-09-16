<?php if(!defined('APPLICATION')) exit(); 
function DiscussionPollQuestionForm($PollForm,$DiscussionPoll,$Disabled,$Closed){
?>
  <div class="P" id="DP_Form">
  <?php
  if(!C('Plugins.DiscussionPolls.DisablePollTitle', FALSE)) {
    echo $PollForm->Label('Discussion Poll Title', 'DP_Title');
    echo Wrap($PollForm->TextBox('DP_Title', array_merge($Disabled, array('maxlength' => 100, 'class' => 'InputBox BigInput'))), 'div', array('class' => 'TextBoxWrapper'));
  }
  echo Anchor(' ', '/plugin/discussionpolls/', array('id' => 'DP_PreviousQuestion'));

  $QuestionCount = 0;
  // set and the form data for existing questions and render a form
  foreach($DiscussionPoll->Questions as $Question) {
    echo '<fieldset id="DP_Question' . $QuestionCount . '" class="DP_Question">';
    // TODO: Figure out how to get SetValue to work with arrays
    //$Sender->Form->SetValue('DiscussionPollsQuestions['.$QuestionCount.']', $Question->Title);
    echo $PollForm->Label(
            'Question #' . ($QuestionCount + 1), 'DP_Questions' . $QuestionCount
    );
    echo Wrap(
            $PollForm->TextBox(
                    'DP_Questions[]', array_merge($Disabled, array(
                'value' => $Question->Title,
                'id' => 'DP_Questions' . $QuestionCount,
                'maxlength' => 100,
                'class' => 'InputBox BigInput'
            ))), 'div', array('class' => 'TextBoxWrapper')
    );

    $j = 0;
    foreach($Question->Options as $Option) {
      echo $PollForm->Label(
              'Option #' . ($j + 1), 'DP_Options' . $QuestionCount . '.' . $i
      );

      echo Wrap(
              $PollForm->TextBox(
                      'DP_Options' . $QuestionCount . '[]', array_merge($Disabled, array(
                  'value' => $Option->Title,
                  'id' => 'DP_Options' . $QuestionCount . '.' . $i,
                  'maxlength' => 100,
                  'class' => 'InputBox BigInput'
              ))), 'div', array('class' => 'TextBoxWrapper')
      );
      $j++;
    }

    $QuestionCount++;
    ?>
    </fieldset>
    <?php
  }

  // If there is no data, render a single question form with 2 options to get started
  if(!$QuestionCount) {
    DiscussionPollQuestionFields($PollForm);
  }

  // the end of the form
  if(!$Closed) {
    echo Anchor('Add a Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DP_NextQuestion'));
    echo Anchor('Add an option', '/plugin/discussionpolls/addoption', array('id' => 'DP_AddOption'));
  }
  else if($QuestionCount > 1) {
    echo Anchor('Next Question', '/plugin/discussionpolls/addquestion/', array('id' => 'DP_NextQuestion'));
  }
  ?>
  </div>
  <?php
}

function DiscussionPollQuestionFields($PollForm){
  ?>
  <fieldset id="DP_Question0" class="DP_Question">
  <?php
    echo $PollForm->Label('Question #1', 'DP_Questions0');
    echo Wrap(
          $PollForm->TextBox(
                  'DP_Questions[]', array(
              'id' => 'DP_Questions0',
              'maxlength' => 100,
              'class' => 'InputBox BigInput'
                  )
          ), 'div', array('class' => 'TextBoxWrapper')
  );

  for($i = 0; $i < 2; $i++) {
    echo $PollForm->Label(
            'Option #' . ($i + 1), 'DP_Options0.' . $i
    );
    echo Wrap(
            $PollForm->TextBox(
                    'DP_Options0[]', array(
                'id' => 'DP_Options0.' . $i,
                'maxlength' => 100,
                'class' => 'InputBox BigInput'
                    )
            ), 'div', array('class' => 'TextBoxWrapper')
    );
  }
  ?>
  </fieldset>
  <?php    
}
