<?php

class H_UiLang extends F_BaseStatic
{
  static $cleanDbFoodNameArray = null;
  
  public static function cleanDbFoodName($raw) {
    // currently disabled
    return $raw;
    
    if (self::$cleanDbFoodNameArray === null) {
      self::buildCleanDbFoodNameArray();
    }
    
    $raw = preg_replace("/,([^\s])/", " $1", $raw);
    $raw = preg_replace("/\/([^\s])/", "/ $1", $raw);
    $raw = explode(" ", $raw);
    $name = "";
    $pWord = null;
    
    foreach ($raw as $word) {
      if (isset(self::$cleanDbFoodNameArray[$word])) {
        $word = self::$cleanDbFoodNameArray[$word];
      }
      
      if ($name != "") {
        if ($pWord !== null && ($pWord == "With" || $pWord == "Without")) {
          $name .= " " . strtolower($word);
        }
        else {
          if ($word == "With" || $word == "Without") {
            $name .= ", " . strtolower($word);
          }
          else {
            $name .= ", " . ucfirst(strtolower($word));  
          }
        }
      }
      else {
        $name .= ucfirst(strtolower($word));
      }
      
      $pWord = $word;
    }
    
    return $name;
  }
  
  public static function notAllowed($text = null) {
    if ($text === null) $text = "you can't >:[";
    F_Log::showError($text);
    return false;
  }
  
  public static function notFound($text = null) {
    if ($text === null) $text = "not found :(";
    F_Log::showError($text);
    return false;
  }
  
  public static function getFromGoogleFit() {
    return "<small><a href='https://fit.google.com/' target='_blank'>from Google Fit</a></small>";
  }
  
  public static function getAdjectivesList() {
    return array("wonderful", "interesting", "magnificent", 
                 "amazing", "beautiful", "mighty");
  }
  
  public static function getRandomAdjective() {
    $a = self::getAdjectivesList();
    $k = array_rand($a);
    return $a[$k];
  }
  
  public static function getBMRMulDescription($mul) {
    $steps = array(1.1125, 1.2875, 1.4625, 1.6375, 1.8125);
    $comments = array(
      "essentialy unmoving all the day",
      "low intensity activities and leisure activities (primarily sedentary)",
      "light exercise: leisurely walking for 30-50 minutes 3-4 days/week, golfing, house chores",
      "moderate exercise 3-5 days per week, 60-70% MHR for 30-60 minutes/session",
      "considerably active: exercising 6-7 days/week at moderate to high intensity (70-85% MHR) for 45-60 minutes/session",
      "extremely active: engaged in heavy/intense exercise like heavy manual labor, heavy lifting, endurance athletes, and competitive team sports athletes 6-7 days/week for 90 + minutes/session"
    );
    
    $i = 0;
    $num_voices = count($steps);
    
    for ($i = 0; $i < $num_voices; $i ++) {
      if ($mul < $steps[$i]) break;
    }

    if ($i < count($comments))
      return $comments[$i];
    else
      return $comments[$num_voices - 1];
  }
  
  public static function buildCleanDbFoodNameArray() {
    self::$cleanDbFoodNameArray = array(
      "ALLPURP" => "All purpose",
      "AL" => "Aluminum",
      "APPL" => "Apple",
      "APPLS" => "Apples",
      "APPLSAUC" => "Applesauce",
      "APPROX" => "Approximate",
      "APPROX" => "Approximately",
      "ARM&BLD" => "Arm and blade",
      "ART" => "Artificial",
      "C" => "Ascorbic acid VIT",
      "ASPRT" => "Aspartame",
      "ASPRT-SWTND" => "Aspartame-sweetened",
      "BABYFD" => "Baby food",
      "BKD" => "Baked",
      "BBQ" => "Barbequed",
      "BSD" => "Based",
      "BNS" => "Beans",
      "BF" => "Beef",
      "BEV" => "Beverage",
      "BLD" => "Boiled",
      "BNLESS" => "Boneless",
      "BTLD" => "Bottled",
      "BTTM" => "Bottom",
      "BRSD" => "Braised",
      "BRKFST" => "Breakfast",
      "BRLD" => "Broiled",
      "BTTRMLK" => "Buttermilk",
      "CA" => "Calcium",
      "CAL" => "Calorie, calories",
      "CND" => "Canned",
      "CARB" => "Carbonated",
      "CNTR" => "Center",
      "CRL" => "Cereal",
      "CHS" => "Cheese",
      "CHICK" => "Chicken",
      "CHOC" => "Chocolate",
      "CHOIC" => "Choice",
      "CHOL" => "Cholesterol",
      "CHOL-FREE" => "Cholesterol-free",
      "CHOPD" => "Chopped",
      "CINN" => "Cinnamon",
      "COATD" => "Coated",
      "COCNT" => "Coconut",
      "COMM" => "Commercial",
      "COMMLY" => "Commercially",
      "CMDTY" => "Commodity",
      "COMP" => "Composite",
      "CONC" => "Concentrate",
      "CONCD" => "Concentrated",
      "COND" => "Condensed",
      "CONDMNT" => "Condiment, condiments",
      "CKD" => "Cooked",
      "CTTNSD" => "Cottonseed",
      "CRM" => "Cream",
      "CRMD" => "Creamed",
      "DK" => "Dark",
      "DECORT" => "Decorticated",
      "DEHYD" => "Dehydrated",
      "DSSRT" => "Dessert, desserts",
      "DIL" => "Diluted",
      "DOM" => "Domestic",
      "DRND" => "Drained",
      "DRSNG" => "Dressing",
      "DRK" => "Drink",
      "DRUMSTK" => "Drumstick",
      "ENG" => "English",
      "ENR" => "Enriched",
      "EQ" => "Equal",
      "EVAP" => "Evaporated",
      "XCPT" => "Except",
      "EX" => "Extra",
      "FLANKSTK" => "Flank steak",
      "FLAV" => "Flavored",
      "FLR" => "Flour",
      "FD" => "Food",
      "FORT" => "Fortified",
      "FR" => "French fried FRENCH",
      "FR" => "French fries FRENCH",
      "FRSH" => "Fresh",
      "FRSTD" => "Frosted",
      "FRSTNG" => "Frosting",
      "FRZ" => "Frozen",
      "GRDS" => "Grades",
      "GM" => "Gram",
      "GRN" => "Green",
      "GRNS" => "Greens",
      "HTD" => "Heated",
      "HVY" => "Heavy",
      "HI-MT" => "Hi-meat",
      "HI" => "High",
      "HR" => "Hour",
      "HYDR" => "Hydrogenated",
      "IMITN" => "Imitation",
      "IMMAT" => "Immature",
      "IMP" => "Imported",
      "INCL" => "Include, includes",
      "INCL" => "Including",
      "FORMULA" => "Infant formula INF",
      "ING" => "Ingredient",
      "INST" => "Instant",
      "JUC" => "Juice",
      "JR" => "Junior",
      "KRNLS" => "Kernels",
      "LRG" => "Large",
      "LN" => "Lean",
      "LN" => "Lean only",
      "LVND" => "Leavened",
      "LT" => "Light",
      "LIQ" => "Liquid",
      "LO" => "Low",
      "LOFAT" => "Low fat",
      "MARSHMLLW" => "Marshmallow",
      "MSHD" => "Mashed",
      "MAYO" => "Mayonnaise",
      "MED" => "Medium",
      "MESQ" => "Mesquite",
      "MIN" => "Minutes",
      "MXD" => "Mixed",
      "MOIST" => "Moisture",
      "NAT" => "Natural",
      "NZ" => "New Zealand",
      "NONCARB" => "Noncarbonated",
      "NFDM" => "Nonfat dry milk",
      "NFDMS" => "Nonfat dry milk solids",
      "NFMS" => "Nonfat milk solids",
      "NFS" => "Not Further Specified",
      "NUTR" => "Nutrients",
      "NUTR" => "Nutrition",
      "OZ" => "Ounce",
      "PK" => "Pack",
      "FR" => "Par fried PAR",
      "PARBLD" => "Parboiled",
      "PART" => "Partial",
      "PART" => "Partially",
      "FR" => "Partially fried PAR",
      "PAST" => "Pasteurized",
      "PNUT" => "Peanut",
      "PNUTS" => "Peanuts",
      "PO4" => "Phosphate",
      "P" => "Phosphorus",
      "PNAPPL" => "Pineapple",
      "PLN" => "Plain",
      "PRTRHS" => "Porterhouse",
      "K" => "Potassium",
      "PDR" => "Powder",
      "PDR" => "Powdered",
      "PRECKD" => "Precooked",
      "PREHTD" => "Preheated",
      "PREP" => "Prepared",
      "PROC" => "Processed",
      "CD" => "Product code PROD",
      "PROP" => "Propionate",
      "PROT" => "Protein",
      "PUDD" => "Pudding, puddings",
      "RTB" => "Ready-to-bake",
      "RTC" => "Ready-to-cook",
      "RTD" => "Ready-to-drink",
      "RTE" => "Ready-to-eat",
      "RTF" => "Ready-to-feed",
      "RTH" => "Ready-to-heat",
      "RTS" => "Ready-to-serve",
      "RTU" => "Ready-to-use",
      "RECON" => "Reconstituted",
      "RED" => "Reduced",
      "RED-CAL" => "Reduced-calorie",
      "REFR" => "Refrigerated",
      "REG" => "Regular",
      "REHTD" => "Reheated",
      "REPLCMNT" => "Replacement",
      "REST-PREP" => "Restaurant-prepared",
      "RTL" => "Retail",
      "RST" => "Roast",
      "RSTD" => "Roasted",
      "RND" => "Round",
      "SNDWCH" => "Sandwich",
      "SAU" => "Sauce",
      "SCALLPD" => "Scalloped",
      "SCRMBLD" => "Scrambled",
      "SD" => "Seed",
      "SEL" => "Select",
      "SHK&SIRL" => "Shank and sirloin",
      "SHRT" => "Short",
      "SHLDR" => "Shoulder",
      "SIMMRD" => "Simmered",
      "SKN" => "Skin",
      "SML" => "Small",
      "NA" => "Sodium",
      "SOL" => "Solids",
      "SOLN" => "Solution",
      "SOYBN" => "Soybean",
      "SPL" => "Special",
      "SP" => "Species",
      "SPRD" => "Spread",
      "STD" => "Standard",
      "STMD" => "Steamed",
      "STWD" => "Stewed",
      "STK" => "Stick",
      "STKS" => "Sticks",
      "STR" => "Strained",
      "SUB" => "Substitute",
      "SMMR" => "Summer",
      "SUPP" => "Supplement",
      "SWT" => "Sweet",
      "SWTND" => "Sweetened",
      "SWTNR" => "Sweetener",
      "TSP" => "Teaspoon",
      "TSTD" => "Toasted",
      "TODD" => "Toddler",
      "UNCKD" => "Uncooked",
      "UNCRMD" => "Uncreamed",
      "UNDIL" => "Undiluted",
      "UNENR" => "Unenriched",
      "UNHTD" => "Unheated",
      "UNPREP" => "Unprepared",
      "UNSPEC" => "Unspecified",
      "UNSWTND" => "Unsweetened",
      "VAR" => "Variety, varieties",
      "VEG" => "Vegetable, vegetables",
      "A" => "Vitamin A VIT",
      "C" => "Vitamin C VIT",
      "H20" => "Water",
      "WHTNR" => "Whitener",
      "WHL" => "Whole",
      "WNTR" => "Winter",
      "W/" => "With",
      "WO/" => "Without",
      "YEL" => "Yellow",
    );
  }
}
