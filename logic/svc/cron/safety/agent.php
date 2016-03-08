<?php
defined("_JEXEC") or die();

class CronSvc_SafetyAgent extends F_CronSvc
{
    public function execute($params)
    {
        if ($params !== null) $params = null;
        // $call = $this->activity;

        // Nothing to do
        return F_CronHelper::RESULT_SUCCESS;
    }
}

?>