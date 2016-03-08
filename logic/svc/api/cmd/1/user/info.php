<?php
defined("_INAPI") or die();

class ApiCmd_Userinfo extends F_ApiCmd
{
    public function execute()
    {
        $api = $this->api_call;
        $userInfo = new JObject();

        foreach (array("name", "username", "email") as $prop)
            $userInfo->set($prop, $api->user->get($prop));

        foreach (array("max_bandwidth", "max_data_size") as $prop)
            $userInfo->set($prop, $api->userQuota->get($prop));

        $userInfo->set("bandwidth", $api->userQuota->getBandWidth());
        $userInfo->set("data_size", $api->userQuota->getDataSize());

        return $api->SetResultAndData(  F_ApiResponse::SUCCESS,
                                        null,
                                        json_encode($userInfo));
    }
}

?>