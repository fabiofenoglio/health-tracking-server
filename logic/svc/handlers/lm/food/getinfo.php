<?php

class ServiceHandler_LmFoodGetinfo extends F_ServiceHandler
{
    public function execute()
    {
        $this->response->format = F_ServiceResponse::FORMAT_JSON;
        $this->response->code = F_Header::STATUS_OK;
        $this->response->content = new JObject;

				$class = H_FoodInfo::CLASS_NAME;
			
        $query = F_Input::getInteger("id", null);
        if (!$query)
        {
					$this->response->content->error = "invalid ID";
					$this->response->code = F_Header::STATUS_BAD_REQUEST;
					return;
        }
        
				$obj = F_Table::loadClass($class, array("id" => $query));
				
        if (! $obj)
        {
					$this->response->content->error = "not found";
					$this->response->code = F_Header::STATUS_NOT_FOUND;
					return;
        }
			
				$user = JFactory::getUser();
      	if ($user->guest) {
					$this->response->content->error = "you are not allowed >:[";
					$this->response->code = F_Header::STATUS_FORBIDDEN;
          return;
				}
			
				if (!H_FoodInfo::userCanView($obj, $user->id)) {
					$this->response->content->error = "you can't >:[";
					$this->response->code = F_Header::STATUS_FORBIDDEN;
					return;
				}
			
				unset($obj->data);
				unset($obj->_errors);
			
				$this->response->content = get_object_vars($obj);
    }
}
