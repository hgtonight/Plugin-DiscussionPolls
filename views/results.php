<?php if(!defined('APPLICATION')) exit(); 
function DiscussionsPollResults($Poll){
?>
  <div class="DP_ResultsForm">
    <?php
    if(GetValue('Title',$Poll))
      echo  $Poll->Title;
    else
      echo Wrap(T('Plugins.DiscussionPolls.NotFound', 'Poll not found'), 'span');
    ?>
      <ol class="DP_ResultQuestions">
    <?php
    if(!GetValue('Title',$Poll)){
      //do nothing
    } else if(!count($Poll->Questions)){
      echo Wrap(T('Plugins.DiscussionPolls.NoReults', 'No results for this poll'), 'span');
    }else 
    foreach($Poll->Questions as $Question) {
    ?>
        <li class="DP_ResultQuestion">
    <?php    
      echo Wrap($Question->Title, 'span');
      echo Wrap(sprintf(Plural($Question->CountResponses, '%s vote', '%s votes'), $Question->CountResponses), 'span', array('class' => 'Number DP_VoteCount'));

      // k is used to have different option bar colors
      $k = $Question->QuestionID % 10;
    ?>
          <ol class="DP_ResultOptions">
    <?php
      foreach($Question->Options as $Option) {
        echo Wrap($Option->Title, 'div');
        $Percentage = number_format(($Option->CountVotes / $Question->CountResponses * 100), 2);
        if($Percentage < 10) {
          $Percentage = $Percentage . '%';
          // put the text on the outside
          ?>
          <span class="DP_Bar DP_Bar-<?php echo $k ?>" style="width: <?php echo $Percentage ?>">&nbsp</span>
          <?php
          echo $Percentage;
        }
        else {
          $Percentage = $Percentage . '%';
          // put the text on the inside
          ?>
          <span class="DP_Bar DP_Bar-<?php echo $k ?>" style="width: <?php echo $Percentage ?>"><?php echo $Percentage ?></span>
          <?php
        }

        echo Wrap($string, 'li', array('class' => 'DP_ResultOption'));

        $k++;
        $k = $k % 10;
      }
    ?>
        </ol>
      </li>
    <?php
    }
    ?>
    </ol>
  </div>
    <?php
}
