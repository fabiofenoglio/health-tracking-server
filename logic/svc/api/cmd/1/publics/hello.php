<?php

class ApiCmd_Public_Hello extends F_ApiCmd
{
    public function execute()
    {
        return $this->api_call->SetResult(F_ApiResponse::SUCCESS, "Hello !");
    }
}

?>