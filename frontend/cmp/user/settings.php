<?php defined("_JEXEC") or die();
const GSTATUS_NOT_CONNECTED = 1;
const GSTATUS_CONNECTED = 2;
const GSTATUS_PROBLEM = 3;
?>
<h3>
  You
</h3>
<?php

$googleStatus = GSTATUS_NOT_CONNECTED;
$user = JFactory::getUser();
$userInfo = H_UserInfo::loadCurrent();

$accessToken = $userInfo->getGoogleAccessToken();

if (!$accessToken) {
  $googleStatus = GSTATUS_NOT_CONNECTED;
  $client = H_IntegrationGoogle::getClient();
  $authorize_url = $client->createAuthUrl();
  $connect_img = F_MediaImage::getImagePath("png/googleplussignin.png");
  
  $loginPrompt = "You are not connected to your google account!<br/>".
        "Please, connect it so that I can access your Google Fit data<br/><br/>".
        "<a href='$authorize_url'>".
        "<img src='$connect_img' alt='click here' style='max-width:200px;' />".
        "</a>";
}
else {
  if (!($client = H_IntegrationGoogle::getClient($user->id))) {
    F_Log::showError(H_IntegrationGoogle::getError());
    $googleStatus = GSTATUS_PROBLEM;
    $userInfo->invalidateGoogleToken();
    $userInfo->store();
  }
  else {
    $googleInfo = $userInfo->getGoogleInfo();
    if ($googleInfo) {
      $googleStatus = GSTATUS_CONNECTED;
    }
    else {
      $googleStatus = GSTATUS_PROBLEM;
    }
  }
}

// Some common shit
define("nl", "<br/>");

if ($googleStatus == GSTATUS_CONNECTED) {
  $lastFitImport = $userInfo->data->get("last_google_fit_import", null);
  $pic = isset($googleInfo->picture) ? $googleInfo->picture : null;
}
else {
  $lastFitImport = null;
  $pic = null;
}

if (!$pic) {
  $pic = F_MediaImage::getImagePath("png/128x128/" . H_UserInfo::DEFAULT_USER_PICTURE);
}

$sex = $userInfo->getSex();

?>
<div id='div-userinfo'>
  <form action="<?php echo H_UiRouter::getUserInfoUrl(); ?>" method="post">
  <input type="hidden" name="action" value="user.edit.settings" />
    
  <table class='table table-noborders' >
    <!-- Google Integration -->
    <tr>
      <td>
      Your Account
      </td>
      <td>
      <?php if ($pic) : ?>
        <p>
        <img src='<?php echo $pic; ?>' style='max-width:64px;' />
        </p>
      <?php 
        endif;
        
        if ($googleStatus == GSTATUS_CONNECTED) {
          echo $googleInfo->name ? $googleInfo->name : $userInfo->getDisplayName() . "";
          echo "<br/><small>";
          echo "connected to Google as ".$googleInfo->email."";
          echo "<br clear='all' /><br/>";
          
          if ($lastFitImport) {
            $imgurl = F_MediaImage::getImagePath("png/32x32/actions/view-calendar-tasks.png");
            echo "<img src='$imgurl' style='max-width:16px;' /> ";
            echo " last GoogleFit import " . date("Y-m-d H:i:s", $lastFitImport);
          }
          echo "</small>";
        }
        else if ($googleStatus == GSTATUS_PROBLEM) {
          echo $loginPrompt;
        }
        else {
          echo $loginPrompt;
        }
      ?>
      <br/><br/>
      </td>
    </tr>
    <?php if ($googleStatus == GSTATUS_NOT_CONNECTED) : ?>
    <!-- Sex -->
    <tr>
      <td>
        Sex
      </td>
      <td>
        <input type="radio" name="if_sex" value="m" <?php  
               echo ($sex == H_UserInfo::SEX_MALE ? "checked" : "");
               ?>
               id="if_field_sex_m"
               onchange="javascript:sexChanged();"
        > 
        <img src='<?php echo F_MediaImage::getImagePath("png/16x16/symbols/male.png"); ?>' 
             style='max-width:16px;' />
        Male <br/><br/>
        
        <input type="radio" name="if_sex" value="f" <?php  
               echo ($sex == H_UserInfo::SEX_FEMALE ? "checked" : "");
               ?>
               id="if_field_sex_f"
               onchange="javascript:sexChanged();"
         > 
        <img src='<?php echo F_MediaImage::getImagePath("png/16x16/symbols/female.png"); ?>' 
             style='max-width:16px;' />
        Female <br/><br/>
        
        <input type="radio" name="if_sex" value="u" <?php  
               echo ($sex == H_UserInfo::SEX_UNKNOWN ? "checked" : "");
               ?>
               id="if_field_sex_u"
               onchange="javascript:sexChanged();"
         > 
        <img src='<?php echo F_MediaImage::getImagePath("png/16x16/status/user-offline.png"); ?>' 
             style='max-width:16px;' />
        Not your business <br/>
        <div id='div-sex-unknown-warning'></div>
        <br/>
        <br/><br/>
      </td>
    </tr>
    <?php endif; ?>
    
    <!-- birthday -->
    <tr>
      <td>
        Birthday
      </td>
      <td>
        <small>
        I need to know it, age is an important factor for optimal computations.
        </small>
        <br/><br/>
        <?php
        echo JHTML::calendar(
          date("d-m-Y", ($userInfo->birthdate ? $userInfo->birthdate : "01-01-1990")),
          'if_birthdate',
          'if_field_birthdate',
          '%d-%m-%Y');
        ?>
      </td>
    </tr>
  </table>
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary">Save</button>
  </div>
  </form>
</div>

<script>

var has_selected_nyb = false;
  
function sexChanged() {
  var newSex = "";
  if (jQuery("#if_field_sex_m")[0].checked)
    newSex = "m";
  else if (jQuery("#if_field_sex_f")[0].checked)
    newSex = "f";
  else if (jQuery("#if_field_sex_u")[0].checked)
    newSex = "u";
  else
    return;
  
  if (newSex == 'u') {
    has_selected_nyb = true;
    jQuery("#div-sex-unknown-warning")[0].innerHTML = 
     "<br/><font color='red'><small>ok, but I would really need to know it for my calculations!</small></font>";
  }
  else {
    if (has_selected_nyb) {
      has_selected_nyb = false;
      jQuery("#div-sex-unknown-warning")[0].innerHTML = 
        "<br/><font color='green'><small>thank you :)</small></font>";
    }
    else {
      jQuery("#div-sex-unknown-warning")[0].innerHTML = "";
    }
  }
}
  
</script>