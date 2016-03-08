<?php defined("_JEXEC") or die(); 

$class = H_FoodInfo::CLASS_NAME;

$user = JFactory::getUser();

$filter = "userid=" . $user->id;
$filter_by_group = F_Input::exists("group");

if ($filter_by_group) {
	$group_sanitize_filter = "/[^a-zA-Z0-9-_.\s]/";
	$input_group = F_Safety::getSanitizedInput("group", $group_sanitize_filter);
	if (strlen($input_group) > 0) {
		$filter .= " AND `group` LIKE '%".$input_group."%'";
	}
	else {
		$filter .= " AND `group`=''";
	}
}

// seems useless but ready for multiple loads grouping
$foodInfos = array_merge(
	F_Table::loadClassList($class, $filter, "name ASC"),
	array()
);

$add_info_url = H_UiRouter::getAddFoodInfoUrl();
$groups = H_FoodInfo::getDifferentGroupsForUser($user->id);

?>
<h3>
	Common food information repository
</h3>

<div class="DEACT-input-append">
	<input type="text" class="input input-large" 
				 id="food-search-input"
				 placeholder="write something here (eg. 'pizza')"
				 />
	<a class="btn btn-primary" id="food-search-btn" onclick="javascript:startFoodSearch();">Search</a>
</div>  
<div id="food-search-results" >
	<table class="table table-condensed table-hover" 
				 id="food-search-results-table" >
		<!-- will be filled by jquery -->
	</table>
</div>

<script>

function startFoodSearch() {
  var query = document.getElementById("food-search-input").value;
  if (!query) {
    alert("insert a search term before starting the query");
    return;
  } 
  
  jQuery("#food-search-btn").hide();
  jQuery('#food-search-results-table').html("<tr><td><font color='black'>searching ...</font></td></tr>");
  
  var jqxhr = jQuery.ajax( 
    "<?php echo F_Service::getRequestUrl("lm.food.search"); ?>", 
    {
			"dataType" : "json",
      "data" : {"query" : query, "dataformat" : 1} 
    }  
  )
  .done(function(data) {
    if (data.error)
      {
        jQuery('#food-search-results-table').html("<tr><td><font color='red'>"+data.error+"</font></td></tr>");
      }
    else
      {
         var r = new Array(), j = -1;
         for (var key=0, size=data.list.length; key<size; key++) {
             r[++j] = data.list[key];
         }
         jQuery('#food-search-results-table').html(r.join('')); 
      }
  })
  .fail(function() {
    alert( "error" );
    jQuery('#food-search-results-table').html("");
  })
  .always(function() {
    jQuery("#food-search-btn").show();
  });
}
</script>
