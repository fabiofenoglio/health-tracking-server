<?php defined("_JEXEC") or die();

class ServiceHandler_Ajax extends F_ServiceHandler
{
    public function execute()
    {
        $response = $this->response;
        $response->format = F_ServiceResponse::FORMAT_JSON;
        $response->content = new JObject();
        
        $content = $response->content;
        $content->result = "";

        // Get required service
        $req = F_Safety::sanitize(F_Input::getRaw("provider"), F_Safety::FILENAME);

        if (empty($req))
        {
            $this->response->code = F_Header::STATUS_BAD_REQUEST;
            $content->status = F_AjaxResponse::STATUS_ERROR;
            $content->message = "no provider requested";
            F_Log::logError($content->message, F_Ajax::LOGID, true);
            return;
        }

        $class = "AjaxProvider_" . F_Safety::sanitize($req, F_Safety::CLASSNAME);
        $path = JIF_PATH_SERVICES . "/ajax/" . str_replace(".", "/", $req) . ".php";

        if (!file_exists($path))
        {
            $this->response->code = F_Header::STATUS_NOT_FOUND;
            $content->status = F_AjaxResponse::STATUS_ERROR;
            $content->message = "invalid provider requested";
            F_Log::logError($content->message, F_Ajax::LOGID, true);
            return;
        }

        // Include ajax snippet file
        F_Content::getbuffered($path);

        if (!class_exists($class))
        {
            $this->response->code = F_Header::STATUS_INTERNAL_SERVER_ERROR;
            $content->status = F_AjaxResponse::STATUS_ERROR;
            $content->message = "internal error 1";
            F_Log::logError($content->message, F_Ajax::LOGID, true);
            return;
        }

        $instance = new $class($content);

        if (!$instance || !is_a($instance, "F_AjaxProvider"))
        {
            $this->response->code = F_Header::STATUS_INTERNAL_SERVER_ERROR;
            $content->status = F_AjaxResponse::STATUS_ERROR;
            $content->message = "internal error 2";
            F_Log::logError($content->message, F_Ajax::LOGID, true);
            return;
        }

        // Run ajax call
        $content->status = F_AjaxResponse::STATUS_OK;
        $content->result = $instance->execute();
    }
}

?>