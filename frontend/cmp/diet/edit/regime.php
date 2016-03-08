<?php defined("_JEXEC") or die();

$class = H_FoodRegime::CLASS_NAME;
$user = JFactory::getUser();

$foodComponents = H_FoodComponent::loadOrderedList();

if (F_Input::exists("id"))
{
  if (!($obj = F_Table::loadClass($class, array("id" => F_Input::getInteger("id", 0)))))
  {
    echo "<p class='error'>invalid ID</p>";
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
}
else
{
  $obj = H_FoodRegime::create($foodComponents);
  $obj->userid = $user->id;
}

$userinfo = H_UserInfo::loadByUser($user->id);
if (!$userinfo)
{
  echo "<p class='error'>Please fill your user info first</p>";
  return;
}

$bodyrecord = H_BodyRecord::getMerged($user->id);
if (!$bodyrecord)
{
  echo "<p class='error'>Please fill at least one body record first</p>";
  return;
}

$dailyOut = H_DataProviderFood::getDailyCaloriesOut($user->id);

?>
<form action="<?php echo H_UiRouter::getBackto(F_SimplecomponentHelper::getUrl("diet.resume")); ?>" method="post">
<div class="form-actions">
  <button type="submit" name="action-save" class="btn btn-primary">Save</button>
  <button type="submit" name="action-cancel" class="btn">Cancel</button>
  <?php if ($obj->id) { ?>
  <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;">
    Delete</button>
  <?php } ?>
</div>
  
<table class='table table-noborders'>
  <input type="hidden" name="action" value="diet.edit.regime" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
      <td>Name</td>
      <td>
        <input class="input input-large" type="text" id="name-field" placeholder="name (e.g. 'winter stable')" name="name" value="<?php echo $obj->name; ?>" />
        <br/>
      </td>
  </tr>

  <tr>
      <td>Description</td>
      <td>
        <input class="input input-large" type="text" id="description-field" placeholder="an optional description" name="description" value="<?php echo $obj->data->get("description", ""); ?>" />
        <br/>
      </td>
  </tr>
  <?php foreach (array(1, 2) as $displayStep) { ?>
    <?php foreach ($foodComponents as $foodComponent) { 
      $is_energy = ($foodComponent->info_property == 'energy');
      if (!isset($obj->data->components[$foodComponent->id]))
      {
        $comp_spec = null;
        $in_field_val = 100;
        $monitor = ($foodComponent->default_tracked ? true : false);
      }
      else
      {
        $comp_spec = $obj->data->components[$foodComponent->id];
        $in_field_val = (int)($comp_spec->goal_percentage*100);
        $monitor = $comp_spec->monitor;
      }
      $step = ($is_energy ? 1 : 5);
      $max = ($is_energy ? 200 : 500);
      if ($displayStep == 1) {
        if (!$monitor) continue;
      }
      else {
        if ($monitor) continue;
      }
    ?>
    <tr>
        <td><?php echo $foodComponent->display_name; ?>
          <br/><br/>
          <?php if (!$is_energy) : ?>
          <input class="input" type="checkbox" name="fc_monitor_<?php echo $foodComponent->info_property; ?>" 
                 id="fc-monitor-field-<?php echo $foodComponent->info_property; ?>" value="1" <?php 
                   echo $monitor ? "checked=1" : ""; ?>
                 onchange="javascript:monitorChanged('<?php echo $foodComponent->info_property; ?>');"
                 />
          <small>monitor this component</small>
          <?php else : ?>
          <input class="input" type="hidden" 
                 name="fc_monitor_<?php echo $foodComponent->info_property; ?>" 
                 id="fc-monitor-field-<?php echo $foodComponent->info_property; ?>" 
                 value="1" <?php 
                   echo $monitor ? "checked=1" : ""; 
                 ?>
                 />
          <?php endif; ?>
        </td>
        <td id="fc-td-<?php echo $foodComponent->info_property; ?>" >
          <table>
            <tr>
              <td style='border-style:hidden!important;'>
                <a style='cursor:pointer;' 
                   onclick="javascript:componentIncrease('<?php echo $foodComponent->info_property; ?>');"
                   > 
                  <img src='<?php echo F_MediaImage::getImagePath(
                   "png/64x64/actions/arrow-up-3.png"); ?>' 
                    style='width:32px;' />
                </a>
                <br/>
                <a style='cursor:pointer;' 
                   onclick="javascript:componentDecrease('<?php echo $foodComponent->info_property; ?>');"
                   > 
                  <img src='<?php echo F_MediaImage::getImagePath(
                   "png/64x64/actions/arrow-down-3.png"); ?>' 
                    style='width:32px;' />
                </a>
              </td>
              <td style="border-style:hidden!important;text-align:center;">
                <input type="range" 
                       min="25" 
                       max="<?php echo $max; ?>" 
                       step="<?php echo $step; ?>" 
                       value="<?php echo $in_field_val; ?>" 
                       id="fc-slider-<?php echo $foodComponent->info_property; ?>" 
                       name="fc_<?php echo $foodComponent->info_property; ?>" 
                       onchange="javascript:sliderChanged('<?php echo $foodComponent->info_property; ?>');"
                       >
                <div id="fc-field-<?php echo $foodComponent->info_property; ?>"
                     style="margin-top:10px;">
                  please wait ...
                </div>
              </td>
              <td style='border-style:hidden!important;'>
              </td>
            </tr>
          </table>
          <br/>
        </td>
    </tr>
    <?php } ?>
  <?php } ?>
</table>
  
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary">Save</button>
    <button type="submit" name="action-cancel" class="btn">Cancel</button>
    <?php if ($obj->id) { ?>
  <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;">
    Delete</button>
  <?php } ?>
  </div>
</form>

<script>

var userInfo = {
  "sex" : "<?php echo $userinfo->getSex(); ?>",
  "height" : <?php echo (float)$bodyrecord->height; ?>,
  "weight" : <?php echo (float)$bodyrecord->weight; ?>,
  "bmr" : <?php echo round(H_FoodCalculator::computeBMR($userinfo, $bodyrecord)); ?>,
  "bmr_mul" : <?php echo (float)$bodyrecord->mul; ?>, 
  "daily_out" : <?php echo round((float)$dailyOut, 2); ?>
};

var foodComponents = {
  <?php 
  foreach ($foodComponents as $foodComponent) {
    echo "\"" . $foodComponent->info_property . "\" : " . json_encode($foodComponent) . ",";
  } 
  ?>
};

function componentIncrease(fc) {
  var slider_id = "fc-slider-"+fc;
  var slider = jQuery("#"+slider_id)[0];
  
  var component_value = parseInt(slider.value);
  component_value += parseInt(slider.step);
  if (component_value > parseInt(slider.max)) {
    component_value = parseInt(slider.max);
  }
  slider.value = component_value;
  sliderChanged(fc);
}

function componentDecrease(fc) {
  var slider_id = "fc-slider-"+fc;
  var slider = jQuery("#"+slider_id)[0];
  
  var component_value = parseInt(slider.value);
  component_value -= parseInt(slider.step);
  if (component_value < parseInt(slider.min)) {
    component_value = parseInt(slider.min);
  }
  slider.value = component_value;
  sliderChanged(fc);
}
  
function monitorChanged(fc) {
  var td_id = "fc-td-"+fc;
  var cb_id = "fc-monitor-field-"+fc;
  var monitoring = jQuery("#"+cb_id)[0].checked ? true : false;
  jQuery("#"+td_id)[0].style.opacity = monitoring ? 1.00 : 0.25;
}

function sliderChanged(fc) {
  var slider_id = "fc-slider-"+fc;
  var input_id = "fc-field-"+fc;
  var new_value = jQuery("#"+slider_id)[0].value;
  var input_el = jQuery("#"+input_id)[0];
  
  input_el.value = new_value;
  
  if (fc == "energy") {
    var goal =  (userInfo.daily_out) * 
              (jQuery("#fc-slider-energy")[0].value/100.0);    
  }
  else {
    var goal =  (userInfo.daily_out) * 
              (jQuery("#fc-slider-energy")[0].value/100.0) *
              ((userInfo.sex == 'm' ? foodComponents[fc].gda_m : foodComponents[fc].gda_f) / 100.0) *
              (jQuery("#"+slider_id)[0].value/100.0);    
  }

  if (goal) {
    input_el.innerHTML = new_value + " % , " + Math.round(goal * 100) / 100 + " " + foodComponents[fc].unit;
  }
  else {
    input_el.innerHTML = new_value + " %";
  }
  
  if (fc == "energy") {
    <?php foreach ($foodComponents as $foodComponent) { 
      if ($foodComponent->info_property == "energy") continue;
    ?>
      sliderChanged("<?php echo $foodComponent->info_property; ?>");
    <?php } ?>
    
    var kcaldiff = goal - (userInfo.daily_out);
    if (kcaldiff) {
      input_el.innerHTML += "<br/>" + 
        Math.round(kcaldiff) + " kcal / day <br/>" + 
        Math.round(kcaldiff*30.5*1000.0/7700.0) + " g / month ";
    }
  }
}
  
<?php foreach ($foodComponents as $foodComponent) { ?>
  monitorChanged("<?php echo $foodComponent->info_property; ?>");
  sliderChanged("<?php echo $foodComponent->info_property; ?>");
<?php } ?>
</script>
