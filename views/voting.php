<?php if(!defined('APPLICATION')) exit(); 
function DiscussionPollAnswerForm($PollForm,$Poll){
?>
<div class="DP_AnswerForm">
    <?php 
    echo $PollForm->Title;
    echo $PollForm->Open(array('action' => Url('/discussion/poll/submit/'), 'method' => 'post'));
    echo $PollForm->Errors();

    $m = 0;
    // Render poll questions
    ?>
    <ol class="DP_AnswerQuestions">
    <?php
    foreach($Poll->Questions as $Question) {
      ?>
      <li class="DP_AnswerQuestion">
      <?php
      echo $PollForm->Hidden('DP_AnswerQuestions[]', array('value' => $Question->QuestionID));
      echo Wrap($Question->Title, 'span');
      ?>
        <ol class="DP_AnswerOptions">
        <?php
        foreach($Question->Options as $Option) {
          echo Wrap($PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID)), 'li');
        }
        ?>
        </ol>
      </li>
      <?php
      $m++;
    }
    ?>
    </ol>
    <?php
    echo $PollForm->Close('Submit');
    ?>
</div>
<?php
}


