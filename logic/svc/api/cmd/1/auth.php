<?php
defined("_INAPI") or die();

class ApiCmd_Auth extends F_ApiCmd
{
    public function execute()
    {
        return $this->api_call->SetResultAndData(F_ApiResponse::SUCCESS);
    }
}

?>