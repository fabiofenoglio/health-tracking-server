<?php
defined("_JEXEC") or die();

class CronSvc_ExternalData extends F_CronSvc
{
    public function execute($params)
    {
        if ($params !== null) $params = null;

        return F_CronHelper::RESULT_SUCCESS;
    }
}
?>