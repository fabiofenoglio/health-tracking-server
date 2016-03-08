<?php

class H_UiBuilderFoodRecordList extends F_BaseStatic
{
	public static function checkInputFoodListAuthorization($userId, $input) {
		foreach ($input->foodRecords as $loaded) {
			if ($loaded->userid != $userId) {
        return false;
      }
		}
		foreach ($input->foodInfos as $loaded) {
			if ($loaded->userid != $userId && $loaded->privacy != H_FoodInfo::PRIVACY_PUBLIC) {
        return false;
      }
		}
		return true;
	}
	
	public static function collectInputFoodList() {
		$r = new JObject();

		$r->input = array();
		$r->foodRecords = array();
		$r->foodInfos = array();

		// list records
		foreach ($_REQUEST as $requestVarK => $requestVarV) {
			if (! F_UtilsString::startsWith($requestVarK, "if_fe_")) {
				continue;
			}

			$expl = explode("_", substr($requestVarK, 6));
			if (count($expl) < 3) {
				continue;
			}

			$foodEntryTableId = F_Safety::sanitize($expl[0], F_Safety::ALPHA_NUM);
			$foodEntryId = F_Safety::sanitize($expl[1], F_Safety::ALPHA_NUM);
			$tokenPropertyName = F_Safety::sanitize($expl[2], F_Safety::ALPHA_NUM_PT_SCORES_SLASH);

			if (!isset($r->input[$foodEntryTableId])) {
				$r->input[$foodEntryTableId] = array();
			}
			$input_fe = $r->input[$foodEntryTableId];

			if (!isset($input_fe[$foodEntryId])) {
				$input_fe[$foodEntryId] = new JObject();
			}

			$input_fe[$foodEntryId]->set($tokenPropertyName, $requestVarV);

			if ($tokenPropertyName == "recordid" && !isset($r->foodRecords[$requestVarV]) && $requestVarV > 0) {
				$loaded = H_FoodRecord::load($requestVarV);
				$r->foodRecords[$requestVarV] = $loaded;
			}
			else if ($tokenPropertyName == "foodid" && !isset($r->foodInfos[$requestVarV])) {
				$loaded = H_FoodInfo::load($requestVarV);
				$r->foodInfos[$requestVarV] = $loaded;
			}

			$r->input[$foodEntryTableId] = $input_fe;
		}

		return $r;
	}

	public static function buildInputFoodList($id, $startingRecords = null, $params = null) {
		if ($startingRecords === null || !is_array($startingRecords)) {
			$startingRecords = array();
		}
		if ($params === null || !is_array($params)) {
			$params = array();
		}

		$id = strtolower($id);
		$o = new JObject();

		$o->html_search_button = '
				<div class="input-append">
						<input type="text" 
									 class="input" 
									 id="food-search-input-'.$id.'" 
									 onkeydown="javascript:startFoodSearch'.$id.'();" 
									 onpaste="javascript:startFoodSearch'.$id.'();" 
									 oninput="javascript:startFoodSearch'.$id.'();" 
									 placeholder="write here to start searching"
						/>
						<a class="btn" id="food-search-btn-'.$id.'" onclick="javascript:startFoodSearch'.$id.'();">Search</a>
					</div>
					';

		$o->html_search_result = '
					<div id="food-search-results-'.$id.'" >
						<table class="table table-condensed table-hover" id="food-search-results-table-'.$id.'" >
						</table>
					</div>
					';

		$o->html_list = '
					<table id="food-list-table-'.$id.'" class="table table-striped">
						<tr><td>no foods selected ...</td></tr>
					</table>
					';

		$snippetParams = array_merge($params, array("id" => $id, "records" => $startingRecords));
		
		$o->js = F_Snippet::generateJScript("food.recordlist", $snippetParams);

		return $o;
	}
}
