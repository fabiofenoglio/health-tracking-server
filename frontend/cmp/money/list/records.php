<?php defined("_JEXEC") or die(); 

$params = array(
  "class" =>                H_MoneyRecord,
  "addRecordUrl" =>         H_UiRouter::getAddMoneyRecordUrl(),
  "addRecordText" =>        "Add a transaction",
  "defaultMaxDays" =>       31,
  "elementShowParams" =>    null,
  "queryFilter" =>          null,
  "querySort" =>            null,
  "queryBuilder" =>         null,
  "userId" =>               null,
  "preDisplayFilter" =>     null,
  "displayClassName" =>     null,
  "preShowParamsEditor" =>  null,
  "postShowParamsEditor" => null,
);
?>
<h3>
	<?php echo "Your ".H_UiLang::getRandomAdjective()." money records"; ?>
</h3>
<?php
F_Snippet::show("hal.show.generic_list", $params);