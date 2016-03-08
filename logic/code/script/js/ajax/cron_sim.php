<?php
$cron_tick_interval = F_Config::getJifCfg("cron_emulate_interval", 0);
$cron_url = F_CronHelper::getCronServiceRunnerUrl();

if ($cron_tick_interval < 5) $cron_tick_interval = 5;
$cron_tick_interval *= (60 * 1000);
?>

function cron_sim_call()
{
    var jqxhr = jQuery.ajax( "<?php echo $cron_url; ?>" )
    .done(function(result,status,xhr) {})
    .fail(function() {})
    .always(function() {});
}

setInterval(cron_sim_call, <?php echo $cron_tick_interval; ?>);