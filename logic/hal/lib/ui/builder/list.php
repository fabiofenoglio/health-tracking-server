<?php

class H_UiBuilderList extends F_BaseStatic
{
	private static function _getParam($params, $key, $default = null, $die = false) {
		if (!isset($params[$key])) {
			if (is_callable($default)) {
				return $default();
			}
			else {
				if ($default === null && $die) {
					die("param not found: " . $key);
				}
				return $default;  
			}
		}
		return $params[$key];
	}
	
	public static function build($params) {
		$result = new JObject();
		$result->html = "";
		$result->htmls = array();
		$result->list = null;
		$result->params = $params;

		// load parameters
		$userId =               self::_getParam($params, "userId", null);
		$class =                self::_getParam($params, "class", null, true);
		$addRecordUrl =         self::_getParam($params, "addRecordUrl", null, true);
		$showParams =           self::_getParam($params, "elementShowParams", array());
		$queryFilter =          self::_getParam($params, "queryFilter", null);
		$querySort =            self::_getParam($params, "querySort", "time DESC");
		$queryBuilder =         self::_getParam($params, "queryBuilder", null);
		$preDisplayFilter =     self::_getParam($params, "preDisplayFilter", null);
		$displayClassName =     self::_getParam($params, "displayClassName", $class::CLASS_NAME);
		$preShowParamsEditor =  self::_getParam($params, "preShowParamsEditor", null);
		$postShowParamsEditor = self::_getParam($params, "postShowParamsEditor", null);
		$queryCompleted = 			self::_getParam($params, "onQueryCompleted", null);
		$showAddNew = 					self::_getParam($params, "showAddNew", true);

		// url input
		$beforeTime =   F_Input::getInteger("from", 0);
		$maxDaysToDisplay = F_Input::getInteger("maxdays", 31);

		// common data
		if ($userId === null) {
			$userId = JFactory::getUser()->id;
		}
		$now = time();

		// Add fixed show params
		if (empty($showParams)) {
			$showParams = array();
		}

		$showParams = array_merge($showParams, array(
			"autoList" => true,
			"class" => $class
		));

		// build query
		$filterArray = array(
			"userid=" . $userId, 
			"time>=" . ($now - F_UtilsTime::A_DAY * $maxDaysToDisplay)
		);
		if ($beforeTime) {
			$filterArray[] = "time <=" . $beforeTime;
		}
		if (!empty($queryFilter)) {
			if (is_array($queryFilter)) {
				$filterArray = array_merge($filterArray, $queryFilter);  
			}
			else {
				$filterArray[] = $queryFilter;
			}
		}

		if ($queryBuilder) {
			$filterArray = $queryBuilder($filterArray);
		}

		// load elements
		$records = $class::query($filterArray, $querySort);
		$result->list = $records;
		if ($queryCompleted !== null && is_callable($queryCompleted)) {
			$queryCompleted($records);
		}

		// prepare pagination
		$lastDisplayedTime = null;
		$displayedCounter = 0;
		$breakWithMoreToSee = false;
		
		if ($showAddNew) {
			$token = "
				<p>
					<a href='$addRecordUrl' 
						class='btn btn-success btn-large'
						style='float:right;margin:30px;'
						>
						<span class='icon-plus' >
						</span> ".self::_getParam($params, "addRecordText", "new")."
					</a>
				</p>
			";
			
			$result->htmls["add_button"] = $token;
			$result->html .= $token;
		}

		if (empty($records)) {
			$token = "
				<p> 
				<br/><hr/><br/>
					sorry, there's nothing here :(
				<br/><hr/><br/>
				</p>
			";
			$result->htmls["content"] = $token;
			$result->htmls["pagination"] = null;
			$result->html .= $token;
		}
		else {
			$token = "";
			$token .= "<table class='table table-noborders'>";
			$token .= F_Content::call_element_list_header($displayClassName, $showParams);
			
			$showParams["previous"] = null;
			$showParams["next"] = null;
			$showParams["list"] = $records;

			$recordKeys = array_keys($records);
			$recordKeysCount = count($recordKeys);

			foreach ($recordKeys as $recordKeyCount => $recordKey) {
				$record = $records[$recordKey];

				if ($recordKeyCount > 0) {
					$showParams["previous"] = $records[$recordKeys[$recordKeyCount - 1]];
				}
				if ($recordKeyCount < ($recordKeysCount - 1)) {
					$showParams["next"] = $records[$recordKeys[$recordKeyCount + 1]];
				}

				if ($maxDaysToDisplay > 0 && $displayedCounter >= $maxDaysToDisplay) {
					$breakWithMoreToSee = true;
					break;
				}

				$displayThis = true;

				if ($preDisplayFilter !== null && is_callable($preDisplayFilter)) {
					$preDisplayFilterResult = $preDisplayFilter($record, $showParams);
					if ($preDisplayFilterResult === false) {
						$displayThis = false;
					}
					else if ($preDisplayFilterResult !== null && is_object($preDisplayFilterResult)) {
						$record = $preDisplayFilterResult;
					}
				}

				if (! $displayThis) {
					continue;
				}

				if ($preShowParamsEditor !== null && is_callable($preShowParamsEditor)) {
					$showParamsEditorResult = $preShowParamsEditor($showParams);
					if (is_array($showParamsEditorResult)) {
						$showParams = $showParamsEditorResult;
					}
				}

				$token .= F_Content::call_element_list_display($record, $displayClassName, $showParams);
				$showParams["previous"] = $record;

				if ($postShowParamsEditor !== null && is_callable($postShowParamsEditor)) {
					$showParamsEditorResult = $postShowParamsEditor($showParams);
					if (is_array($showParamsEditorResult)) {
						$showParams = $showParamsEditorResult;
					}
				}

				$lastDisplayedTime = $record->time;
				$displayedCounter ++;	  
			}
			$token .= "</table>";
			
			$result->htmls["content"] = $token;
			$result->html .= $token;
			
			$token = "";

			if ($beforeTime) {
				$url = JUri::getInstance(); $url->setVar("from", 0);
				$token .= "<a href='$url' class='btn'>first page</a> ";
			}

			if ($breakWithMoreToSee) {
				$url = JUri::getInstance(); $url->setVar("from", $lastDisplayedTime);
				$token .= "<a href='$url' class='btn'>next $maxDaysToDisplay</a> ";
			}
			
			$result->htmls["pagination"] = $token <> "" ? $token : null;
			$result->html .= $token;
		}
		
		return $result;
	}
}
