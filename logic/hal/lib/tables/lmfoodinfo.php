<?php

class JTableLmfoodinfo extends F_TableModel
{
  public $privacy;
  public $userid;
  public $name;
  public $description;
  public $group;
  public $unit_size;
  public $serving_size;
  public $water;
  public $energy;
  public $protein;
  public $lipid;
  public $ash;
  public $carbohydrate;
  public $sugar;
  public $fiber;
  public $calcium;
  public $iron;
  public $magnesium;
  public $phosphorus;
  public $potassium;
  public $sodium;
  public $zinc;
  public $copper;
  public $manganese;
  public $selenium;
  public $vit_c;
  public $thiamin;
  public $riboflavin;
  public $niacin;
  public $panto_acid;
  public $vit_b6;
  public $folate;
  public $folic_acid;
  public $food_folate;
  public $folate_dfe;
  public $choline;
  public $vit_b12;
  public $vit_a_iu;
  public $vit_a_rae;	
  public $retinol;
  public $alpha_carot;	
  public $beta_carot;	
  public $beta_crypt;	
  public $lycopene;	
  public $lut_zea;	
  public $vit_e;	
  public $vit_d;	
  public $vit_d_iu;	
  public $vit_k;	
  public $fat_sat;	
  public $fat_mono;	
  public $fat_poly;
  public $cholesterol;
  public $salt;

  public function getDisplayName() {
    if ($this->userid > 0) {
      return $this->name;
    }
    return H_UiLang::cleanDbFoodName($this->name);
  }

  function postClear()
  {
    // nope
  }
}