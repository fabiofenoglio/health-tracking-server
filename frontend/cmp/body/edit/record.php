<?php defined("_JEXEC") or die();

$class = H_BodyRecord::CLASS_NAME;
$user = JFactory::getUser();
$merged = H_BodyRecord::getMerged($user->id);

if (F_Input::exists("id"))
{
  if (!($obj = F_Table::loadClass($class, array("id" => F_Input::getInteger("id", 0)))))
  {
    echo "<p class='error'>invalid ID</p>";
    return;
  }
  if ((int)$obj->userid !== (int)$user->id) {
    echo "<p class='error'>you can't!</p>";
    return;
  }
}
else if (F_Input::exists("clone")) {
  if (!($obj = F_Table::loadClass($class, array("userid" => $user->id, "id" => F_Input::getInteger("clone", 0)))))
  {
      echo "<p class='error'>invalid ID</p>";
      return;
  }
  $obj->id = null;
  $obj->time = time();
  $obj->source = H_BodyRecord::SOURCE_USER;
}
else
{
  // clone with default last values
  $obj = H_BodyRecord::loadLast($user->id);
  if (!$obj) {
    // no previous data
    $obj = F_Table::create($class);
    $obj->userid = $user->id;
    $obj->time = time();
    $obj->source = H_BodyRecord::SOURCE_USER;
    $obj->height = $merged->height;
    $obj->mul = $merged->mul;
  }
  else {
    // clone last
    $obj->id = null;
    $obj->time = time();
    $obj->source = H_BodyRecord::SOURCE_USER;
    $obj->height = $merged->height;
    $obj->mul = $merged->mul;
  }
}

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getBodyRecordsUrl()); ?>" method="post">
<table class='table table-noborders'>
  <input type="hidden" name="action" value="body.edit.record" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
      <td>Weight</td>
      <td>
        <div class="input-append">
          <?php 
          $in_field_val = "".round((float)$obj->weight, 3);
          if (strstr($in_field_val, ".") === false) {
            $in_field_val .= ".0";
          }
          ?>
          <input class="input" 
                 type="number" 
                 step="0.1"
                 name="if_weight" id="weight-field" value="<?php echo $in_field_val; ?>" />
          <span class="add-on">kg</span>
        </div>
        <br/><br/>
      </td>
  </tr>
  <tr>
      <td>Height</td>
      <td>
        <div class="input-append">
          <input class="input" 
                 type="number" 
                 step="1" 
                 name="if_height" id="height-field" value="<?php echo (int)$obj->height; ?>" />
          <span class="add-on">cm</span>
        </div>
        <br/><br/>
      </td>
  </tr>
  <tr>
      <td>Daily Untracked Activity Level</td>
      <td>
        <?php 
        // mul va da 1.0 a 2.0
        // slider da 0 a 1000
        $in_field_val = (int)(($obj->mul - 1.0) * 1000.0);
        ?>
        <input type="range" 
                 min="0" max="1000" 
                 step="25" 
                 value="<?php echo $in_field_val; ?>" 
                 id="mul-field-slider" 
                 name="if_mul" 
                 onchange="javascript:mulSliderChanged();"
                 >
        <div id="mul-field-text" >
            please wait ...
        </div>
        <br/>
        <small><span class='icon-warning'></span> Please, use this to compensate only for untracked activities.</small>
        <br/>
      </td>
  </tr>
  <tr>
      <td>Time</td>
      <td>
        <?php
        echo JHTML::calendar(
                  date("d-m-Y H:i", ($obj->time ? $obj->time : time())),
                  'if_time',
                  'time-field',
                  '%d-%m-%Y %H:%M');
        ?>
      </td>
  </tr>
</table>
  
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary">Save</button>
    <button type="submit" name="action-cancel" class="btn">Cancel</button>
    
    <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;">
      Delete</button>
  </div>
</form>

<script>

function getMulComment(mul) {
  var steps = [1.1125, 1.2875, 1.4625, 1.6375, 1.8125];
  var comments = [
    "essentialy unmoving all the day",
    "low intensity activities and leisure activities (primarily sedentary)",
    "light exercise: leisurely walking for 30-50 minutes 3-4 days/week, golfing, house chores",
    "moderate exercise 3-5 days per week, 60-70% MHR for 30-60 minutes/session",
    "considerably active: exercising 6-7 days/week at moderate to high intensity (70-85% MHR) for 45-60 minutes/session",
    "extremely active: engaged in heavy/intense exercise like heavy manual labor, heavy lifting, endurance athletes, and competitive team sports athletes 6-7 days/week for 90 + minutes/session"
  ];

  var i = 0;
  
  for (i = 0; i < steps.length; i ++) {
    if (mul < steps[i]) break;
  }
  
  if (i < comments.length)
    return comments[i];
  else
    return comments[comments.length - 1];
}
  
function mulSliderChanged() {
  var sliderId = "mul-field-slider";
  var divId = "mul-field-text";
  
  // mul va da 1.0 a 2.0
  // slider da 0 a 1000
  var mul = 1.0 + (2.0 - 1.0) * jQuery("#"+sliderId)[0].value / 1000.0;
  
  jQuery("#"+divId)[0].innerHTML = mul + " x <br/>" + "<small>" + getMulComment(mul) + "</small>";
}

mulSliderChanged();

</script>