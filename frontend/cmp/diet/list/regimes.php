<?php defined("_JEXEC") or die(); 

$class = H_FoodRegime::CLASS_NAME;

$user = JFactory::getUser();

$regimes = H_FoodRegime::loadByUser($user->id);
$add_regime_url = H_UiRouter::getAddFoodRegimeUrl();

?>
<h3>
	Your <?php echo H_UiLang::getRandomAdjective(); ?> diet regimes
</h3>

<p>
	<a href="<?php echo $add_regime_url; ?>"
		 class="btn btn-success btn-large"
		 style="float:right;margin-bottom:30px;"
		 >
		<span class='icon-plus' ></span> add a new one
	</a>
</p>

<table class='table table-noborders'>
<?php echo F_Content::call_element_list_header($class); ?>
<?php 
foreach ($regimes as $regime) { 
	echo F_Content::call_element_list_display($regime, $class);
}
?>
</table>
<?php
if (empty($regimes)) {
	?>
	<p style='text-align:center;'>Sorry, there's nothing here :(
		<br/><br/>
		maybe
		<br/>
		<a href="<?php echo $add_regime_url; ?>"
			 class="btn btn-success"
			 style="margin-bottom:30px;"
			 >
			<span class='icon-plus' ></span> create one ?
		</a>
	</p>
	<?php
}
