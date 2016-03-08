<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();

if ($params->get("header") == true)
{
  echo "<tr>";
  echo "<th></th>";
  echo "<th></th>";
  echo "<th>Food</th>";
  echo "<th>Group</th>";
  echo "<th></th>";
  echo "</tr>";
  return;
}

$el = $params->get("element");
if (!$el) die("param error");

$custom_backto = $params->get("backto", null);
if ($custom_backto) {
  $urlParams = array(H_UiRouter::BACKTO_KEY => $custom_backto);
}
else {
  $urlParams = null;
}

$edit_url = H_UiRouter::getEditFoodInfoUrl($el->id, $urlParams);
$user = JFactory::getUser();
$clone_url = H_UiRouter::getAddFoodInfoUrl($el->id, $urlParams);
?>
<tr>
  <td>
    <!-- left side -->
    <?php if ($el)  { ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
      </a>
    <?php } ?>
  </td>
  <td>
    <!-- left side 2 -->
    <?php if ($el->privacy == H_FoodInfo::PRIVACY_PRIVATE)  { ?>
    <small><span class='icon-lock' title='this is private'></span></small>
    <?php } ?>
  </td>
  <td>
    <!-- food -->
    <?php echo $el->name ? 
      preg_replace("/,([^\s])/", ", $1", $el->name)
      : 
      "no name"; 
    ?>
  </td>
  <td>
    <!-- group -->
    <small>
    <?php echo $el->group; ?>
    </small>
  </td>
  <td>
    <!-- right side -->
    <a href='<?php echo $clone_url; ?>'>
      <span class='icon-plus' ></span>
      </a>
    <br/>
  </td>
</tr>