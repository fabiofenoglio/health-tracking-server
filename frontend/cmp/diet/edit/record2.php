<?php defined("_JEXEC") or die();

$class = H_FoodRecord::CLASS_NAME;
$user = JFactory::getUser();

$input_time = null;
$input_group = null;

/*
Possible inputs:
list of record id [records=1,5,123,5346]
*/
$records = array();
$foodInfos = array();

$copy = F_Input::getInteger("copy", 0) ? true : false;

$inputRecords = F_Input::getRaw("records", null);
if ($inputRecords) {
  $inputRecords = explode(",", $inputRecords);
  foreach ($inputRecords as $inputRecordId) {
    $inputRecord = H_FoodRecord::load($inputRecordId);
    if ($inputRecord->userid != $user->id) {
      return H_UiLang::notAllowed();
    }
    
    if ($input_group === null && $inputRecord->group) {
      $input_group = $inputRecord->group;
    }
    if ($input_time === null && $inputRecord->time) {
      $input_time = $inputRecord->time;
    }
    
    if ($inputRecord->foodid > 0 && !isset($foodInfos[$inputRecord->foodid])) {
      $foodInfos[$inputRecord->foodid] = H_FoodInfo::load($inputRecord->foodid);
    }
    
    if ($copy) {
      $inputRecord->id = null;
    }
    
    if ($inputRecord->foodid > 0) {
      $records[] = $inputRecord;  
    }
  }
}

if ($copy || $input_group === null) {
  $input_group = H_FoodRecord::guessGroupByTime($user->id);
}

if ($copy) {
  $input_time = time();
}

$groups = H_FoodRecord::getAverageGroupTimes($user->id);

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getFoodRecordsUrl()); ?>" method="post">
<table class='table table-noborders'>
  <input type="hidden" name="action" value="diet.edit.record2" />

  <tr>
      <td style='border-top-style: hidden;'>
        Foods
      </td>
      <td style='border-top-style: hidden;'>
        <div class="input-append">
          <input type="text" class="input" id="food-search-input" 
                 onkeydown="javascript:startFoodSearch();" 
                 onpaste="javascript:startFoodSearch();" 
                 oninput="javascript:startFoodSearch();" 
          />
          <a class="btn" id="food-search-btn" onclick="javascript:startFoodSearch();">Search</a>
        </div>  
        <div id="food-search-results" >
          <table class="table table-condensed table-hover" id="food-search-results-table" >
            <!-- will be filled by jquery -->
          </table>
        </div>
      </td>
  </tr>
  <tr>
      <td colspan='2' style='border-top-style: hidden;'>
        <table id="food-list-table" class="table">
          <tr><td>no foods selected</td></tr>
        </table>
      </td>
  </tr>
  <tr>
      <td style='border-top-style: hidden;'>
        Group
    </td>
      <td style='border-top-style: hidden;'>
        <input class="input" type="text" id="group-field" placeholder="group (e.g. breakfast, lunch, ...)" name="group" value="<?php echo htmlspecialchars($input_group); ?>" />
          <small><div id="group-suggestion">
            <?php
            foreach ($groups as $group) {
              $group = htmlspecialchars($group->group);
              echo "<a class='btn btn-small' onclick='setFoodGroup(\"$group\")'>$group</a> ";
            }
            ?>
          </div></small>
        <br/>
      </td>
  </tr>
  <tr>
      <td style='border-top-style: hidden;'>
        Time
      </td>
      <td style='border-top-style: hidden;'>
        <?php
        echo JHTML::calendar(
          date("d-m-Y H:i", $input_time),
          'time',
          'time-picker',
          '%d-%m-%Y %H:%M'
        );
        
        echo " <small style='cursor:pointer;'>".
          "<a class='btn btn-small' onclick='javascript:setFoodTimeNow();'".
          "style='margin-bottom:12px;' >now</a></small>";
        ?>
      </td>
  </tr>
</table>

<div class="form-actions">
  <button type="submit" name="action-save" class="btn btn-primary">Save</button>
  <button type="submit" name="action-cancel" class="btn">Cancel</button>

  <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;">
    Delete all</button>
</div>
</form>

<script>
<?php 
$startingSequential = 1;
function getJsEntryForFoodRecord($record) {
  return json_encode(array(
    "i" => (int)$record->id, 
    "n" => htmlspecialchars($record->name),
    "d" => htmlspecialchars($record->description),
    "ss" => $record->serving_size,
    "us" => $record->unit_size,
  ));
}

if (empty($records)) : ?>
var selectedFoods = {};
<?php else : ?>
var selectedFoods = {
  <?php
  $startingFoodJs = "";
  foreach ($records as $selectedFood) {
    $foodInfo = $foodInfos[$selectedFood->foodid];
    $startingFoodJs .= "i$startingSequential : {
      \"food\" : ".getJsEntryForFoodRecord($foodInfo).",
      \"amount\" : $selectedFood->amount,
      \"recordid\" : ".($selectedFood->id ? $selectedFood->id : 0).",
      \"status\" : 1
    }, ";
    $startingSequential ++;
  }
  echo rtrim($startingFoodJs, ", ");
  ?>
};  
<?php endif; ?>

var searchedFoods = {};
var idCounter = <?php echo $startingSequential; ?>;
var searchAjaxRequest = null;
  
function setFoodTimeNow() {
  jQuery("#time-picker")[0].value = "<?php echo date("d-m-Y H:i", time()) ?>";
}

function askForUnitAmount(selectedid) {
  var unit_amount = prompt("Please enter amount in units");
  if (unit_amount !== "0" && unit_amount < 1) {
    return;
  }
  
  var foodRecord = selectedFoods[selectedid];
  if (!foodRecord) {
    alert("error #0");
    return;
  }
  
  jQuery('#food-amount-'+selectedid+'-field')[0].value = foodRecord.food.us * unit_amount;
  foodAmountChanged(selectedid);
  refreshFoodList();
}
  
function refreshFoodList() {
  var table = jQuery('#food-list-table');
  var h = "";
  
  for (var key in selectedFoods) {
    if (!selectedFoods.hasOwnProperty(key)) continue;

    var selectedFood = selectedFoods[key];
    if (selectedFood.status != 1) continue;
    
    var amount_suggestion = "";
    if (selectedFood.food.us > 0.01 && selectedFood.food.us != selectedFood.food.ss) {
      selectedFood.food.us = parseFloat(selectedFood.food.us);
      amount_suggestion += "one unit is " + selectedFood.food.us + " grams<br/>";
    }
    if (selectedFood.food.ss > 0.01) {
      selectedFood.food.ss = parseFloat(selectedFood.food.ss);
      amount_suggestion += "serving size is " + selectedFood.food.ss + " grams";
      if (selectedFood.food.us > 0.01) {
        var eq_units = Math.round(selectedFood.food.ss / selectedFood.food.us, 2);
        if (eq_units != 1) {
          amount_suggestion += " ("+eq_units+" units)";    
        }
      }
    }
    
    var amount_str = parseFloat(selectedFood.amount);
    if (amount_str < 0.1) amount_str = "";
    var remove_button = "<img src='<?php echo F_MediaImage::getImagePath(
          "png/64x64/actions/dialog-cancel-3.png"); 
          ?>' style='width:24px;cursor:pointer;' "+
          "onclick='javascript:removeFood(\""+key+"\");' />";
    
    h += "<tr>" + 
      "<td>" + 
        selectedFood.food.n + "<br/><br/>" + remove_button +
      "</td>" +
      "<td>" +
        '<div class="input-append">' +
          '<input type="hidden" '+
            'name="if_fe_'+key+'_recordid" '+
            'value="'+selectedFood.recordid+'" />' +
          '<input type="hidden" '+
            'name="if_fe_'+key+'_status" '+
            'value="'+selectedFood.status+'" />' +
          '<input type="hidden" '+
            'name="if_fe_'+key+'_foodid" '+
            'value="'+selectedFood.food.i+'" />' +
          '<input class="input" type="number" step="0.10" placeholder="amount" '+
            'name="if_fe_'+key+'_amount" '+
            'id="food-amount-'+key+'-field" '+
            'value="'+amount_str+'" '+
            'style="width:90px;" '+
            'onchange="javascript:foodAmountChanged(\''+key+'\');" />' +
          '<span class="add-on">grams</span>' +
        '</div>' +
        ' <a class="btn btn-small" onclick="javascript:askForUnitAmount(\''+key+'\');"' +
        ' style="margin-bottom:12px;" >units</a>' +
        '<br/><small>' + amount_suggestion + '</small>' +
      "</td>" +
    "</tr>";
  }
  
  if (h == "") {
    h = "<tr><td style='text-align:center;'>no foods selected</td></tr>";  
  }
  
  table.html(h);
}

function foodAmountChanged(selectedid) {
  // get food info
  var foodRecord = selectedFoods[selectedid];
  if (!foodRecord) {
    alert("error #0");
    return;
  }
  
  foodRecord.amount = jQuery('#food-amount-'+selectedid+'-field')[0].value;
}

function removeFood(selectedid) {
  // remove from list
  selectedFoods[selectedid].status = 0;
  
  // refresh list
  refreshFoodList();
}

function addFood(id) {
  // clear search table
  jQuery('#food-search-results-table').html("");
  jQuery("#food-search-input")[0].value = "";
  
  // get food info
  var food = searchedFoods["i" + id];
  if (!food) {
    alert("error #0");
    return;
  }
  
  // add new record
  var startAmount = 0.0;
  if (food.ss > 0.1) {
    startAmount = food.ss;
  }
  
  selectedFoods["i"+idCounter] = {
    "food" : food,
    "amount" : startAmount,
    "recordid" : 0,
    "status" : 1
  };
  
  // refresh list
  refreshFoodList();
  
  if (startAmount < 0.1) {
    // focus amount input field
    jQuery('#food-amount-i'+idCounter+'-field')[0].focus();
  }
  
  idCounter ++;
  
  // scroll back
  // location.href = "#";
  // location.href = "#food-list-table";
}

function startFoodSearch() {
  if (searchAjaxRequest) {
    searchAjaxRequest.abort();
  }
  searchedFoods = {};
  
  var query = document.getElementById("food-search-input").value;
  
  if (query.length < 3) {
    return;
  }
  
  if (!query) {
    alert("insert a search term before starting the query");
    return;
  } 
  
  // jQuery("#food-search-input")[0].value = "";
  jQuery("#food-search-btn").hide();
  jQuery('#food-search-results-table').html("<tr><td><font color='black'>searching ...</font></td></tr>");
  
  searchAjaxRequest = jQuery.ajax( 
    "<?php echo F_Service::getRequestUrl("lm.food.search2"); ?>", 
    {
      "dataType": "json", 
      "data" : {"query" : query} 
    }  
  )
  .done(function(data) {
    if (data.error) {
      jQuery('#food-search-results-table').html("<tr><td><font color='red'>"+data.error+"</font></td></tr>");
      return;
    }
    
    var r = "";
    var size = data.list.length;
    var dataItem;
    
    for (var key=0; key<size; key++) {
      dataItem = data.list[key];
      searchedFoods["i"+dataItem.i] = data.list[key];

      r += "<tr><td style='cursor:pointer'>" + 
        "<div onclick=\"javascript:addFood(" + dataItem.i + ");\">"+
        "<span class='icon-arrow-right' />" + dataItem.n + 
        "<br/><!--<small>" + dataItem.d + "</small>-->" +
        "</div></td></tr>";
    }
    
    if (false && size == 1) {
      addFood(dataItem.i);
    }
    else {
      jQuery('#food-search-results-table').html(r);   
    }
  })
  .fail(function() {
    jQuery('#food-search-results-table').html("");
  })
  .always(function() {
    jQuery("#food-search-btn").show();
  });
}

function setFoodGroup(group) {
  jQuery("#group-field")[0].value = group;
}

refreshFoodList();

</script>
