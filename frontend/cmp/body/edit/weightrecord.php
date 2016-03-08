<?php defined("_JEXEC") or die();

$class = H_BodyRecord::CLASS_NAME;
$user = JFactory::getUser();

// clone with default last values
$obj = H_BodyRecord::loadLast($user->id);
if (!$obj) {
  $obj = F_Table::create($class);
}
else {
  $obj->id = null;
}

$obj->userid = $user->id;
$obj->time = time();
$obj->source = H_BodyRecord::SOURCE_USER;
$obj->height = null;
$obj->mul = null;
if ($obj->weight < 1.0) {
  $obj->weight = H_BodyRecord::DEFAULT_WEIGHT;
}

?>
<div class="custom-center">
  <form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getBodyRecordsUrl()); ?>" 
        method="post"
  >
    <input type="hidden" name="action" value="body.edit.weightrecord" />
    <input type="hidden" name="if_weight" id="weight-field"
           value="<?php echo $obj->weight; ?>" />

    <a style="font-size:300%;cursor:pointer;"
       onclick="javascript:weightrecord_up();">
      <img src='<?php echo F_MediaImage::getImagePath("png/64x64/actions/arrow-up-3.png"); ?>' 
           style='width:64px;' />
    </a>
    <br/>

    <font style="font-size:300%;">
      <div id="weight-display" style="display:inline;">
        0.00
      </div> kg
    </font>

    <br/>
    <a style="font-size:300%;cursor:pointer;"
       onclick="javascript:weightrecord_down();">
      <img src='<?php echo F_MediaImage::getImagePath("png/64x64/actions/arrow-down-3.png"); ?>' 
           style='width:64px;margin-left:-15px;' />
    </a>

    <div class="form-actions">
      <button type="submit" name="action-save" 
              class="btn btn-primary btn-block btn-large">Save</button>
      <br/>
      <button type="submit" name="action-cancel" 
              class="btn btn-block">Cancel</button>
    </div>

</form>
  
  <br/>
  or
  <br/>
  <a class="btn" href="<?php echo H_UiRouter::getAddBodyRecordUrl(
    array(H_UiRouter::BACKTO_KEY => H_UiRouter::getBodyRecordsUrl())); ?>" >
  add more things
  </a>
</div>

<style>

.custom-center {
  text-align: center;
  margin: auto;
  width: 80%;
  max-width: 500px;
  padding: 10px;
}
</style>

<script>
  
var weightrecord_value;
var weightrecord_step = 0.1;

function weightrecord_up() {
  weightrecord_value += weightrecord_step;
  weightrecord_updateVisualValue(weightrecord_value);
}

function weightrecord_down() {
  weightrecord_value -= weightrecord_step;
  if (weightrecord_value < 1.0) weightrecord_value = 1.0;
  weightrecord_updateVisualValue(weightrecord_value);
}

function weightrecord_updateVisualValue(val) {
  jQuery("#weight-display")[0].innerHTML = Math.round(val * 100) / 100.0;
  jQuery("#weight-field")[0].value = val;
  weightrecord_value = val;
}
  
weightrecord_updateVisualValue(<?php echo round($obj->weight, 2); ?>);
  
</script>