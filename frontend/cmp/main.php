<?php defined("_JEXEC") or die();

$links = array(
	"user.settings" => "User Settings",
	"diet.resume" => "Diet Resume",
  "diet.list.records" => "Food Records",
	"diet.detail.foodplan" => "Food Plan",
  "diet.list.regimes" => "Diet Regimes",
	"body.list.records" => "Body records",
	"diet.list.foodinfos" => "Food Repository",
	"diet.list.foodinfos_common" => "Common Food Repository",
	"activity.list.records" => "Activities"
);
/*
"body.list.calories" => "Calories records",
*/

?>

	<p>
		<?php foreach ($links as $module => $text) { ?>
			<a href="<?php echo H_UiRouter::build($module); ?>" class="btn btn-large" style="margin:10px;">
				<span class='icon-list'></span>
				<?php echo $text; ?>
			</a>
			<?php } ?>
	</p>