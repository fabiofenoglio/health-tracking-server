<?php
defined("_JEXEC") or die();

class CronSvc_MaintenanceData extends F_CronSvc
{
    public function execute($params)
    {
        if ($params !== null) $params = null;
        
        $lock = F_SafetyLock::waitLock(F_Maintenance::LOCKID, 60);
        $result = $this->___do_analysis();
        $lock->release();

        return $result;
    }

    private function ___do_analysis()
    {
        $call = $this->activity;
        $an = F_Maintenance::getAnalysis();

        if (! $an->load(true))
        {
            $call->addLog("error loading maintenance report", F_Log::ERROR);
            return F_CronHelper::RESULT_ERROR;
        }

        if (! $an->requiresFix()) 
            return F_CronHelper::RESULT_SUCCESS;
        
        if (!$an->fix())
        {
            $call->addLog("fixing process error", F_Log::ERROR, F_CronHelper::LOGID);
            return F_CronHelper::RESULT_ERROR;
        }

        if ($an->reload() && $an->requiresFix())
        {
            $call->addLog("fixing post-check reported errors", F_Log::ERROR);
            return F_CronHelper::RESULT_ERROR;
        }

        return F_CronHelper::RESULT_SUCCESS;
    }
}
?>