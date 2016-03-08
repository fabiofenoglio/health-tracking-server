<?php defined("_JEXEC") or die();

class ServiceHandler_ConnectionCheck extends F_ServiceHandler
{
    public function execute()
    {
        $this->response->format = F_ServiceResponse::FORMAT_RAW;
        
        if (!F_Input::exists(F_ServiceConnection::CHECK_CODE_KEY))
        {
            $this->response->content = "1";
        }
        else 
        {
            $key = (int)F_Input::getInteger(F_ServiceConnection::CHECK_CODE_KEY);

            if ($key < 1)
                $this->response->content = "1";
            else
                $this->response->content = $key;
        }
    }
}