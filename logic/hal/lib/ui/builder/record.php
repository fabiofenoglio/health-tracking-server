<?php

class H_UiBuilderRecord extends F_BaseStatic
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
	
	public static function buildCommonFields($obj, $params) {
		$result = new JObject();

		// load parameters
		// $class = self::_getParam($params, "class", null, true);
		// $uniqueId = self::_getParam($params, "uid", null, true);
		$now = time();

		// name
		$in_field_val = htmlentities($obj->name, ENT_QUOTES);
		$result->name = "
			<td>
				Name
				<br/><br/>
			</td>
			<td>
				<input class='input' 
				 type='text'
				 name='if_name' id='name-field'
				 value='$in_field_val'
				/>
			</td>
		";
		
		// group
		$in_field_val = htmlentities($obj->group, ENT_QUOTES);
		$result->group = "
			<td>
				Group
				<br/><br/>
			</td>
			<td>
				<input class='input' 
				 type='text'
				 name='if_group' id='group-field'
				 value='$in_field_val'
				/>
			</td>
		";

		// time
		$result->time = "
			<td>
				Time
				<br/><br/>
			</td>
			<td>" .
			JHTML::calendar(
        date("d-m-Y H:i", ($obj->time ? $obj->time : $now)),
        'if_time',
        'time-field',
        '%d-%m-%Y %H:%M') .
			"</td>
		";

		// notes
		$in_field_val = htmlentities($obj->data->get("note", ""), ENT_QUOTES);
		$result->notes = "
			<td>
				Group
				<br/><br/>
			</td>
			<td>
				<textarea 
             rows='4'
             name='if_note' id='note-field' 
             placeholder='additional notes' 
             >$in_field_val</textarea>
			</td>
		";
		
		return $result;
	}
}