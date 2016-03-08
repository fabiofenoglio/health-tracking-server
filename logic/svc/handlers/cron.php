<?php defined("_JEXEC") or die();

class ServiceHandler_Cron extends F_ServiceHandler
{
    public function execute()
    {
        $this->response->format = F_ServiceResponse::FORMAT_JSON;
        $this->response->content = new JObject();
        $response_content = $this->response->content;
        $response_content->status = 1;
        $response_content->message = "";

        // Check requested activities
        $force = F_Input::exists("force");
        $in_val = F_Input::getRaw("req", null);
        if (empty($in_val))
        {
            $acts = F_CronHelper::getAllActivities();
        }
        else
        {
            $requested = explode(".",
                        F_Safety::sanitize(F_Input::getRaw("req", ""), F_Safety::NUM_PT)
                    );
            $query = "id IN (" . F_UtilsArray::toSingleString($requested, ",") . ")";
            $acts = F_CronHelper::getActivities($query);
        }

        if ($acts === null)
        {
            $response_content->message .= "sql error : " . F_Table::getError();
            $response_content->status = 0;
        }
        else
        {
            // Execute activities
            $done = 0;
            $now = time();

            foreach ($acts as $act)
            {
                $list_lock = F_SafetyLock::waitLock(F_CronHelper::LOCKID_LISTING, 10);
                $needed = $force || ($act->isEnabled() && $act->needlaunch());
                if ($needed) 
                {
                    $act->updateTiming($now, false);
                }
                $list_lock->release();
                if (!$needed) continue;

                // Acquire lock
                $lock_code = "cronsvc::" . $act->path;
                if (F_SafetyLock::codeIsLocked($lock_code))
                {
                    // Script is still executing
                    continue;
                }
                $lock = F_SafetyLock::waitLock($lock_code, F_ConfigCron::ACTIVITY_TIMEOUT);
                
                $start_time = microtime(true);
                $done ++;

                // Execute and get output
                $response_content->message .= "executing " . $act->title . "<br/>";
                ob_start();
                $result = $act->execute();
                var_dump($result);
                $response_content->message .= "run time " . 
                    round((microtime(true) - $start_time) * 1000.0, 3) . " ms<br/>";
                $response_content->message .= "result ><br/>";
                $response_content->message .= ob_get_clean() . "<br/>";

                foreach ($act->getEcho() as $e)     $response_content->message .= "echo > " . $e . "<br/>";
                foreach ($act->getLogs() as $log)   F_Log::log($log[0], $log[1], F_CronHelper::LOGID);

                // Update instances
                $act->data->set("last_result", $result);
                $act->store();

                // Release lock
                $lock->release();
            }

            if ($done < 1)  $response_content->message .= "nothing to do";
            else            $response_content->status = 2;
        }
        
        if (F_Input::exists("rawoutput")) 
        {
            $this->response->format = F_ServiceResponse::FORMAT_RAW;
            $this->response->content = $response_content->message;
        }
    }
}