<?php 
$id = F_Snippet::getParam("id", "");
$records = F_Snippet::getParam("records", array());
$onAfterFoodListRefresh = F_Snippet::getParam("onAfterFoodListRefresh", null);

$startingSequential = 1;

if (!defined("_getJsEntryForFoodRecord")) {
  define("_getJsEntryForFoodRecord", 1);
  
  function getJsEntryForFoodRecord($record) {
    return json_encode(array(
      "i" => (int)$record->id, 
      "n" => htmlspecialchars($record->name),
      "d" => htmlspecialchars($record->description),
      "ss" => $record->serving_size,
      "us" => $record->unit_size,
    ));
  }  
}

if (empty($records)) : ?>
var selectedFoods<?php echo $id; ?> = {};
<?php else : ?>
var selectedFoods<?php echo $id; ?> = {
  <?php
  $foodInfos = array();
  $startingFoodJs = "";
  foreach ($records as $selectedFood) {
    if (!isset($foodInfos[$selectedFood->foodid])) {
      $foodInfos[$selectedFood->foodid] = H_FoodInfo::load($selectedFood->foodid);
    }
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

var searchedFoods<?php echo $id; ?> = {};
var idCounter<?php echo $id; ?> = <?php echo $startingSequential; ?>;
var searchAjaxRequest<?php echo $id; ?> = null;

function askForUnitAmount<?php echo $id; ?>(selectedid) {
  var unit_amount = prompt("Please enter amount in units");
  if (unit_amount !== "0" && unit_amount < 1) {
    return;
  }
  
  var foodRecord = selectedFoods<?php echo $id; ?>[selectedid];
  if (!foodRecord) {
    alert("error #0");
    return;
  }
  
  jQuery('#food-amount-'+selectedid+'-field-<?php echo $id; ?>')[0].value = Math.round(100.0 * foodRecord.food.us * unit_amount)/100;
  foodAmountChanged<?php echo $id; ?>(selectedid);
  refreshFoodList<?php echo $id; ?>();
}
                                             
function refreshFoodList<?php echo $id; ?>() {
  var table = jQuery('#food-list-table-<?php echo $id; ?>');
  var h = "";
  
  for (var key in selectedFoods<?php echo $id; ?>) {
    if (!selectedFoods<?php echo $id; ?>.hasOwnProperty(key)) continue;

    var selectedFood = selectedFoods<?php echo $id; ?>[key];
    var hidden = (selectedFood.status != 1) ? true : false;
    
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
    var remove_button = "<img src='<?php 
      echo F_MediaImage::getImagePath("png/64x64/actions/dialog-cancel-3.png"); 
          ?>' style='width:24px;cursor:pointer;' "+
          "onclick='javascript:removeFood<?php echo $id; ?>(\""+key+"\");' />";
    
    h += "<tr"+(hidden ? " style='display:none;'" : "")+">" + 
      "<td>" + 
        selectedFood.food.n + "<br/><br/>" + remove_button +
      "</td>" +
      "<td>" +
        '<div class="input-append">' +
          '<input type="hidden" '+
            'name="if_fe_<?php echo $id; ?>_'+key+'_recordid" '+
            'value="'+selectedFood.recordid+'" />' +
          '<input type="hidden" '+
            'name="if_fe_<?php echo $id; ?>_'+key+'_status" '+
            'value="'+selectedFood.status+'" />' +
          '<input type="hidden" '+
            'name="if_fe_<?php echo $id; ?>_'+key+'_foodid" '+
            'value="'+selectedFood.food.i+'" />' +
          '<input class="input" type="number" step="0.10" placeholder="amount" '+
            'name="if_fe_<?php echo $id; ?>_'+key+'_amount" '+
            'id="food-amount-'+key+'-field-<?php echo $id; ?>" '+
            'value="'+amount_str+'" '+
            'style="width:90px;" '+
            'onchange="javascript:foodAmountChanged<?php echo $id; ?>(\''+key+'\');" />' +
          '<span class="add-on">grams</span>' +
        '</div>' +
        ((selectedFood.food.us > 0.1) ?
          ' <a class="btn btn-small" onclick="javascript:askForUnitAmount<?php echo $id; ?>(\''+key+'\');"' +
          ' style="margin-bottom:12px;" >units</a>'
          :
          '' 
        ) +        
        '<br/><small>' + amount_suggestion + '</small>' +
      "</td>" +
    "</tr>";
  }
  
  if (h == "") {
    h = "<tr><td style='text-align:center;'>no foods selected</td></tr>";  
  }
  
  table.html(h);
  <?php if ($onAfterFoodListRefresh) { echo "$onAfterFoodListRefresh(selectedFoods$id);"; } ?>
}

function foodAmountChanged<?php echo $id; ?>(selectedid) {
  // get food info
  var foodRecord = selectedFoods<?php echo $id; ?>[selectedid];
  if (!foodRecord) {
    alert("error #0");
    return;
  }
  
  foodRecord.amount = jQuery('#food-amount-'+selectedid+'-field-<?php echo $id; ?>')[0].value;
}

function removeFood<?php echo $id; ?>(selectedid) {
  // remove from list
  if (selectedFoods<?php echo $id; ?>[selectedid].recordid > 0) {
    selectedFoods<?php echo $id; ?>[selectedid].status = 0;  
  }
  else {
    delete selectedFoods<?php echo $id; ?>[selectedid];
  }
  
  // refresh list
  refreshFoodList<?php echo $id; ?>();
}

function addFood<?php echo $id; ?>(id) {
  // clear search table
  jQuery('#food-search-results-table-<?php echo $id; ?>').html("");
  jQuery("#food-search-input-<?php echo $id; ?>")[0].value = "";
  
  // get food info
  var food = searchedFoods<?php echo $id; ?>["i" + id];
  if (!food) {
    alert("error #0");
    return;
  }
  
  // add new record
  var startAmount = 0.0;
  if (food.ss > 0.1) {
    startAmount = food.ss;
  }
  
  selectedFoods<?php echo $id; ?>["i"+idCounter<?php echo $id; ?>] = {
    "food" : food,
    "amount" : startAmount,
    "recordid" : 0,
    "status" : 1
  };
  
  // refresh list
  refreshFoodList<?php echo $id; ?>();
  
  if (startAmount < 0.1) {
    jQuery('#food-amount-i'+idCounter<?php echo $id; ?>+'-field-<?php echo $id; ?>')[0].focus();
  }
  
  idCounter<?php echo $id; ?> ++;
}

function startFoodSearch<?php echo $id; ?>() {
  if (searchAjaxRequest<?php echo $id; ?>) {
    searchAjaxRequest<?php echo $id; ?>.abort();
  }
  searchedFoods<?php echo $id; ?> = {};
  
  var query = document.getElementById("food-search-input-<?php echo $id; ?>").value;
  
  if (query.length < 3) {
    return;
  }
  
  if (!query) {
    alert("insert a search term before starting the query");
    return;
  } 
  
  jQuery("#food-search-btn-<?php echo $id; ?>").hide();
  jQuery('#food-search-results-table-<?php echo $id; ?>').html("<tr><td><font color='black'>searching ...</font></td></tr>");
  
  searchAjaxRequest<?php echo $id; ?> = jQuery.ajax( 
    "<?php echo F_Service::getRequestUrl("lm.food.search"); ?>", 
    {
      "dataType": "json", 
      "data" : {"query" : query} 
    }  
  )
  .done(function(data) {
    if (data.error) {
      jQuery('#food-search-results-table-<?php echo $id; ?>').html("<tr><td><font color='red'>"+data.error+"</font></td></tr>");
      return;
    }
    
    var r = "";
    var size = data.list.length;
    var dataItem;
    
    for (var key=0; key<size; key++) {
      dataItem = data.list[key];
      searchedFoods<?php echo $id; ?>["i"+dataItem.i] = data.list[key];

      r += "<tr><td style='cursor:pointer'>" + 
        "<div onclick=\"javascript:addFood<?php echo $id; ?>(" + dataItem.i + ");\">"+
        "<span class='icon-arrow-right' />" + dataItem.n + 
        "<br/><!--<small>" + dataItem.d + "</small>-->" +
        "</div></td></tr>";
    }
    
    if (false && size == 1) {
      addFood<?php echo $id; ?>(dataItem.i);
    }
    else {
      jQuery('#food-search-results-table-<?php echo $id; ?>').html(r);   
    }
  })
  .fail(function() {
    jQuery('#food-search-results-table-<?php echo $id; ?>').html("");
  })
  .always(function() {
    jQuery("#food-search-btn-<?php echo $id; ?>").show();
  });
}
                              
// refresh <?php echo $id; ?> list
jQuery( document ).ready(function() {
    refreshFoodList<?php echo $id; ?>();
});
