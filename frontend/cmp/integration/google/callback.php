<?php defined("_JEXEC") or die();

$user = JFactory::getUser();
if ($user->guest) return;

$userinfo = H_UserInfo::loadCurrent();
if (!$userinfo) {
  F_Log::showError("internal error #0");
  return;
}

// get Code
if (!F_Input::exists("code")) {
  $error = F_Input::getString("error", "unknown error");
  F_Log::showError($error);
  return;
}

$code = F_Input::getRaw("code");

$client = H_IntegrationGoogle::getClient();
$client->authenticate($code);
$token = $client->getAccessToken();
$userinfo->setGoogleAccessToken($token);

if (!$userinfo->store()) {
  F_Log::showError($userinfo->getError());
  return;
}

$googleInfo = H_IntegrationGoogleUserinfo::requestUserInfo($user->id);
$userinfo->setGoogleInfo($googleInfo);

if (!$userinfo->store()) {
  F_Log::showError($userinfo->getError());
  return;
}

F_Log::showInfo("Google Integration setup completed successfully!");

$url = H_UiRouter::build("user.settings", array(H_UiRouter::BACKTO_KEY => "main"));

$pic = isset($googleInfo->picture) ? $googleInfo->picture : null;
?>
<table class='table table-noborders' >
  <tr>
    <td>
    <?php if ($pic) : ?>
      <img src='<?php echo $pic; ?>' style='max-width:64px;' />
    <?php endif; ?>
    </td>
    <td>
      <strong>
    <?php echo $userinfo->getDisplayName(); ?>
      </strong>, 
      you are now connected to your google account!
      <br/><br/>
      <a href='<?php echo $url; ?>'>click here</a> 
      to go back to your profile page
    </td>
  </tr>
</table>