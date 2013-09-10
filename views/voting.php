<?php if(!defined('APPLICATION')) exit(); 
function DiscussionPollAnswerForm($PollForm,$Poll,$PartialAnswers){
?>
<div class="DP_AnswerForm">
    <?php 
    echo $Poll->Title;
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
          if(GetValue($Question->QuestionID,$PartialAnswers)==$Option->OptionID){
            //fill in partial answer
            echo Wrap($PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID,'checked'=>'checked')), 'li');
          }else{
            echo Wrap($PollForm->Radio('DP_Answer' . $m, $Option->Title, array('Value' => $Option->OptionID)), 'li');
          }
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


