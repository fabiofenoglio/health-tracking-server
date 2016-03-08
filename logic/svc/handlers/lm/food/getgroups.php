<?php

class ServiceHandler_LmFoodGetgroups extends F_ServiceHandler
{
    public function execute()
    {
        $this->response->format = F_ServiceResponse::FORMAT_JSON;
        $this->response->code = F_Header::STATUS_OK;
        $this->response->content = new JObject;

				$class = H_FoodRecord::CLASS_NAME;
			
				$user = JFactory::getUser();
				if ($user->guest) {
					$this->response->content->error = "you are not allowed >:[";
					$this->response->code = F_Header::STATUS_FORBIDDEN;
          return;
				}
			
				$sql_query = "SELECT DISTINCT `group` FROM " . F_Table::getClassTable($class) . 
					" WHERE userid=" .$user->id;
			
				$list = F_Table::doQuery($sql_query);
        
        if (! $list)
        {
            $this->response->content->error = "no matches in the database :(";
            return;
        }
			
				$this->response->content->list = array();
			
				foreach ($list as $result)
				{
					$this->response->content->list[] = $result->group;
				}
    }
}
