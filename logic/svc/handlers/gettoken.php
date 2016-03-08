<?php defined("_JEXEC") or die();

class ServiceHandler_GetToken extends F_ServiceHandler
{
    public function execute()
    {
        $this->response->format = F_ServiceResponse::FORMAT_FILE;
        $this->response->content = null;

        $code = F_Safety::getSanitizedInput("token", "", F_Safety::ALPHA_NUM_PT);

        if (! F_Safety::verifyStrLen($code, 2, 150))
            return $this->tokendownloader_err("missing token", 400, "unknown");

        if (!($el = F_Download::getDownloadToken($code)))
            return $this->tokendownloader_err("invalid token", 404, $code);

        if ($el->isExpired())
        {
            F_Log::logWarning("Removing expired token " . $code);
            if (!$el->delete())
                F_Log::logError("error removing expired token " . $code . " (" . $el->flags .")");

            return $this->tokendownloader_err("token expired", 403, $code);
        }

        if (!$el->isPublic())
        {
            if (!$el->userAllowed(JFactory::getUser()))
                return $this->tokendownloader_err("access denied", 401, $code);
        }

        ob_start();
        $result = $el->download();
        $content = ob_get_clean();
        if (!$result)
            return $this->tokendownloader_err("internal error", 500, $code);

        $this->response->content = $content;
        
        if (!$el->delete())
            F_Log::logError("error removing token " . $code . " (" . $el->flags .")");

        return true;
    }

    private function tokendownloader_err($logstring, $errorCode, $code)
    {
        F_Log::logError("tokendownloader_svc: " . $logstring . " (" . $code . ")", null, true);
        $this->response->code = $errorCode;
        $this->response->format = F_ServiceResponse::FORMAT_RAW;
        $this->response->content = $logstring;
        return false;
    }
}

?>