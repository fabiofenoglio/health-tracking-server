<?php defined("_JEXEC") or die();

class ServiceHandler_Api extends F_ServiceHandler
{
    public function execute()
    {
        // retrieve desired input version
        $rawInput = F_Input::getRaw(F_Api::PK_VERSION, F_Api::getLastVersion());
        $version = F_ApiDispatcher::parseRequiredVersion($rawInput);

        if (! $version)
        {
            F_Log::logError("API req stopped (missing version $rawInput)", F_Api::LOGID);
            $this->response->code = F_Header::STATUS_INTERNAL_SERVER_ERROR;
            return;
        }

        if ($version != $rawInput)
            F_Log::logWarning("Required vr $rawInput dispatched to $version", F_Api::LOGID);

        // Parse API request
        define("_INAPI", 1);
        $nApi = new F_ApiCall($version);
        $nApi->load();
        
        // Lock APP
        if (isset($nApi->app) && isset($nApi->app->id))
            $app_lock = F_SafetyLock::waitLock("api_app_id_" . $nApi->app->id, 
                    F_ConfigApi::MAX_EXECUTION_TIME);
        else $app_lock = null;
        
        // Parse api call if valid
        if ($nApi->isValid)
        {
            // Execute required command
            $nApi->execute();
        }

        // Get response and commit bandwidth
        $api_response = $nApi->getResponse();
        $encoded = $api_response->encoded();

        // Send response
        if (($statCode = $api_response->getStatusCode()) != 200) $this->response->code = $statCode;
        
        $this->response->content = $encoded;
        $this->response->format = F_ServiceResponse::FORMAT_RAW;

        // Commit bandwidth
        $bandw = strlen($encoded);
        
        if ($nApi->isValid && !$nApi->isPublic && $nApi->userQuota !== null)
        {   
            $nApi->userQuota->addBandWidth($bandw);
            $nApi->userQuota->store();
        }
        
        // Commit bandwidth to app
        $now = time();

        if ($nApi->app !== null)
        {
            if ($nApi->app->getUsageStartTime(0) > 0 && 
                    (($now - $nApi->app->getUsageStartTime(0)) > 
                        F_ConfigApi::APP_STAT_PERIOD * F_UtilsTime::A_DAY))
            {
                $nApi->app->setCallNumber(1);
                $nApi->app->setBandwidth($bandw);
                $nApi->app->setUsageStartTime($now);            
            }
            else
            {
                $nApi->app->setCallNumber($nApi->app->getCallNumber(0) + 1);
                $nApi->app->setBandwidth($nApi->app->getBandwidth(0) + $bandw);
                if ($nApi->app->getUsageStartTime(-1) < 0) $nApi->app->setUsageStartTime($now);
            }

            $nApi->app->setLastUsage($now);
            $nApi->app->store();
        }
        
        // Release APP lock
        if ($app_lock) $app_lock->release();
        
        // If log is requested, write it
        $nApi->sendLog();
    }
}