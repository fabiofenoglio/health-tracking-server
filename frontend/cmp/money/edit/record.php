<?php defined("_JEXEC") or die();

$class = H_MoneyRecord;
$user = JFactory::getUser();

if (F_Input::exists("id"))
{
  if (!($obj = $class::load(F_Input::getInteger("id", 0))))
  {
    return H_UiLang::notFound();
  }
  if ((int)$obj->userid !== (int)$user->id) {
    return H_UiLang::notAllowed();
  }
}
else if (F_Input::exists("clone")) {
  if (!($obj = 
        $class::queryOne(
          array("userid" => $user->id, 
                "id" => F_Input::getInteger("clone", 0)
          )
        )   
     ))
  {
      return H_UiLang::notFound();
  }
  $obj->id = null;
  $obj->time = time();
  $obj->source = H_Data::SOURCE_USER;
}
else
{
  // clone with default last values
  $obj = H_BodyRecord::loadLast($user->id);
  if (!$obj) {
    // no previous data
    $obj = $class::create();
    $obj->userid = $user->id;
  }
  else {
    // clone last
    $obj->id = null;
  }
  
  $obj->time = time();
  $obj->source = H_Data::SOURCE_USER;
}

$commonFields = H_UiBuilderRecord::buildCommonFields($obj);

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getMoneyRecordsUrl()); ?>" method="post">
<table class='table table-noborders'>
  <input type="hidden" name="action" value="money.edit.record" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
    <?php echo $commonFields->name; ?>
  </tr>
  <tr>
    <td>Amount</td>
    <td>
      <div class="input-append">
        <input class="input" 
               type="number" 
               step="0.01" 
               name="if_amount" id="amount-field" 
               value="<?php echo sprintf("%.2f", $obj->amount); ?>" />
        <span class="add-on">€</span>
      </div>
      <br/><br/>
    </td>
  </tr>
  <tr>
    <?php echo $commonFields->group; ?>
  </tr>
  <tr>
    <?php echo $commonFields->time; ?>
  </tr>
  <tr>
    <?php echo $commonFields->notes; ?>
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
</script>