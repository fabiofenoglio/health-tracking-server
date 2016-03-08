<?php

class ServiceHandler_LmFoodSearch extends F_ServiceHandler
{
	public function execute()
	{
		$this->response->format = F_ServiceResponse::FORMAT_JSON;
		$this->response->code = F_Header::STATUS_OK;
		$this->response->content = new JObject;

		$class = H_FoodInfo::CLASS_NAME;

		$query = F_Safety::getSanitizedInput("query", "pizza", "/[^a-zA-Z0-9-_.,\s]/");
		if (!$query)
		{
			$this->response->content->error = "invalid query";
			$this->response->code = F_Header::STATUS_BAD_REQUEST;
			return;
		}

		$user = JFactory::getUser();
		if ($user->guest) {
			$this->response->content->error = "you are not allowed >:[";
			$this->response->code = F_Header::STATUS_FORBIDDEN;
			return;
		}

		if (strlen($query) < 3) {
			$this->response->content->error = "invalid query";
			$this->response->code = F_Header::STATUS_BAD_REQUEST;
			return;
		}
		
		$query = strtolower($query);
		$maxCnt = 200;
		
		$where = "((1=1)";
		foreach (explode(" ", $query) as $query_term) {
			$where .= " AND (LOWER(name) LIKE \"%$query_term%\" OR LOWER(description) LIKE \"%$query_term%\")";
		}
		$where .= ") AND " .
			"(privacy=".H_FoodInfo::PRIVACY_PUBLIC." OR userid=".$user->id.")";

		$list = H_FoodInfo::query($where, "id DESC");

		if (! $list)
		{
				$this->response->content->error = "no matches in the database :(";
				return;
		}
		
		foreach ($list as $k => $v) {
			$v->name = $v->getDisplayName();
		}

		$this->response->content->list = array();

		$format = F_Input::getInteger("dataformat", 0);
		$callParams = array("backto" => H_UiRouter::getCommonFoodInfosUrl());
		$cnt = 0;
		
		foreach ($list as $result)
		{
			if ($format == 1) {
				$this->response->content->list[] = F_Content::call_element_list_display($result, H_FoodInfo::CLASS_NAME, $callParams);
			}
			else {
				$o = array(
					"i" => (int)$result->id, 
					"n" => $this->sanitizeBeforeSending($result->name),
					"d" => $this->sanitizeBeforeSending($result->description),
					"ss" => $result->serving_size,
					"us" => $result->unit_size,
				);

				$this->response->content->list[] = $o;
			}
			
			if (++$cnt >= $maxCnt) {
				break;
			}
		}
		
		return;
	}
	
	private function sanitizeBeforeSending($raw) {
		return F_Safety::sanitize($raw, "/[^a-zA-Z0-9-_.\s!$\?,]/", "-");
	}
}
