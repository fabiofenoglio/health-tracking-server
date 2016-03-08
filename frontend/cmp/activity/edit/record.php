<?php defined("_JEXEC") or die();

$class = H_ActivityRecord::CLASS_NAME;
$user = JFactory::getUser();

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
  $obj->source = H_ActivityRecord::SOURCE_USER;
}
else
{
  // clone with default last values
  $obj = H_ActivityRecord::loadLast($user->id);
  if (!$obj) {
    // no previous data
    $obj = F_Table::create($class);
    $obj->userid = $user->id;
    $obj->time = time();
    $obj->source = H_ActivityRecord::SOURCE_USER;
    $obj->data->note = null;
  }
  else {
    // clone last
    $obj->id = null;
    $obj->time = time();
    $obj->relative_fatigue = 100;
    $obj->source = H_ActivityRecord::SOURCE_USER;
    $obj->data->note = null;
  }
}

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getActivityRecordsUrl()); ?>" method="post">
<table class='table table-noborders'>
  <input type="hidden" name="action" value="activity.edit.record" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
      <td>Activity</td>
      <td>
        <?php 
        $in_field_val = $obj->activity;
        ?>
        <input class="input" type="text" 
               name="if_activity" id="activity-field" 
               placeholder="activity name" 
               value="<?php echo $in_field_val; ?>" />
        <br/><br/>
      </td>
  </tr>
  <tr>
      <td>Duration</td>
      <td>
        <?php 
        $in_field_val = floor($obj->duration / 3600.0);
        ?>
        <div class="input-append">
          <input class="input input-small inline" type="text" 
                 name="if_duration_h" id="duration-h-field" 
                 value="<?php echo $in_field_val; ?>" />
          <span class="add-on">hours</span>
        </div>
        <?php 
        $in_field_val = (int)(($obj->duration - $in_field_val * 3600) / 60);
        ?>
        <div class="input-append">
          <input class="input input-small inline" type="text" 
                 name="if_duration_m" id="duration-m-field" 
                 value="<?php echo $in_field_val; ?>" />
          <span class="add-on">minutes</span>
        </div>
        <br/><br/>
      </td>
  </tr>
  <tr>
      <td>Calories</td>
      <td>
        <?php 
        $in_field_val = round($obj->calories, 2);
        if ($in_field_val < 0.1) $in_field_val = "";
        ?>
        <div class="input-append">
          <input class="input" type="text" 
                 name="if_calories" id="calories-field" 
                 placeholder="calories burned"
                 value="<?php echo $in_field_val; ?>" />
          <span class="add-on">kcal</span>
        </div>
        <br/><br/>
      </td>
  </tr>
  <!--
  <tr>
      <td>Relative Fatigue Level
        <br/><small>optional</small>
      </td>
      <td>
        <?php 
        $in_field_val = (int)($obj->relative_fatigue);
        ?>
        <input type="range" 
                 min="0" max="300"
                 step="5" 
                 value="<?php echo $in_field_val; ?>" 
                 id="fatigue-field-slider" 
                 name="if_relative_fatigue" 
                 onchange="javascript:fatigueSliderChanged();"
                 >
        <div id="fatigue-field-text" >
            please wait ...
        </div>
        <br/>
      </td>
  </tr>
  -->
  <tr>
      <td>Start Time</td>
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
  <tr>
      <td>Notes</td>
      <td>
        <?php 
        $in_field_val = $obj->data->get("note", "");
        ?>
        <textarea 
               rows="4"
               name="if_note" id="note-field" 
               placeholder="additional notes" 
               ><?php echo $in_field_val; ?></textarea>
        <br/><br/>
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
  
function fatigueSliderChanged() {
  var sliderId = "fatigue-field-slider";
  var divId = "fatigue-field-text";
  
  // mul va da 1.0 a 2.0
  // slider da 0 a 1000
  var v = jQuery("#"+sliderId)[0].value;
  
  jQuery("#"+divId)[0].innerHTML = v + " %";
}

fatigueSliderChanged();
  
</script>