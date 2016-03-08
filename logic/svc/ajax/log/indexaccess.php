<?php

class AjaxProvider_LogIndexAccess extends F_AjaxProvider
{
    public function execute()
    {
        $response = $this->response;

        if (!isset($_SERVER["HTTP_REFERER"]))
            $page = F_Safety::sanitize(F_Input::getRaw("level"), F_Safety::URI);
        else
            $page = $_SERVER["HTTP_REFERER"];

        if (! F_Safety::verifyStrLen($page, 0, 100))
        {
            $response->status = 0;
            $response->message = "<font color='red'>invalid parameter length</font>";
            return $response->status;
        }

        $lock = F_SafetyLock::waitLock("AJAX_DIRECT_ACCESS_LOG_LOCK", 5);

        $timing_data = F_DataGeneric::loadOrCreate("AJAX_DIRECTINDEXACCESS_LOG_DATA");
        $key = "lw" . F_Safety::sanitize($_SERVER["REMOTE_ADDR"],
                F_Safety::CLASSNAME,
                "_");

        $val = $timing_data->getd($key, 0);

        $props = $timing_data->getProperties(true);

        if (count($props) >= 200)
        {
            $timing_data->reset();
            $timing_data->setd($key, $val);
        }

        if (time() - $val < 15)
        {
            $response->status = 2;
            $response->message =
                    "richiesta registrata come <b>ulteriore</b> avviso di sicurezza";
        }
        else
        {
            $timing_data->setd($key, time());
            if ($timing_data->store())
            {
                $response->status = 1;
                $response->message = "richiesta registrata come avviso di sicurezza";
                F_Log::logWarning("Tentativo di accesso diretto intercettato in " . $page .
                        " da " . $_SERVER["REMOTE_ADDR"],
                    F_Safety::LOGID);
            }
            else
            {
                $response->status = 0;
                $response->message = "<font color='red'>internal error</font>";
            }
        }

        $lock->release();
        return $response->status;
    }
}

?>