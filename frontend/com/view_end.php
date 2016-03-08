<?php defined("_JEXEC") or die();

// insert name in menu
$userInfo = H_UserInfo::loadCurrent();
$displayName = $userInfo->getDisplayName();
$googleInfo = $userInfo->getGoogleInfo();
if ($googleInfo) {
  $pic = isset($googleInfo->picture) ? $googleInfo->picture : null;  
}
else {
  $pic = null;
}

?>
<script>
var titleEl = jQuery(".g-menu-item-111 .g-menu-item-container .g-menu-item-content .g-menu-item-title")[0];
titleEl.innerHTML = "<?php echo $displayName; ?>";
<?php if ($pic) : ?>
titleEl.innerHTML = "<img src='<?php echo $pic; ?>' style='width:32px;' /> " + titleEl.innerHTML;
<?php endif; ?>
</script>
<?php

// NOW ONLY ADMIN
if (!F_Config::checkAuthorization(JFactory::getUser(), "core.admin")) {
  return;
}

F_Snippet::insertJScript("hideshowdiv");

$rawCache = H_Caching::getRaw();
if (empty($rawCache->cache)) return;

function cacheDumpObj($obj) {
  static $cnt = 0;
  
  $divId = "cache-debug-div-".($cnt++);
  
  echo "<a onclick='javascript:toggleDivVisibility(\"$divId\");'>";
  echo spl_object_hash($obj);
  echo "</a> <div id='$divId' style='display:none;'>";
  echo "<br/>";
  H_Debugger::dump($obj);
  echo "</div>";
  
  $cnt ++;
}

$cnt = 0;
$voice_count = count($rawCache->cache);

?>
<br/><hr/><br/>
<div style='text-align:center;' >
  <a onclick='javascript:toggleDivVisibility("footer-cache-debug");'
     style='cursor:pointer;'
     class='btn'>
    caching debug
    <?php if ($voice_count > 0) { echo "<br/><small>$voice_count tokens</small>"; } ?>
  </a>
</div>
<div id='footer-cache-debug' style='display:none;'>
  <pre>
  <?php var_dump($rawCache->stats); ?>
  </pre>
  <br/>
  <table class='table table-noborders'>
    <?php foreach ($rawCache->cache as $k => $v) : ?>
    <?php if (is_object($v[0]) || is_array($v[0])) {
      $divId = "cache-debug-div-".($cnt++);
      $k = str_replace(".", " ", $k);
      ?>
      <tr>
        <td>
          <?php 
          echo "<a onclick='javascript:toggleDivVisibility(\"$divId\");'>";
          echo $k;
          echo "</a>" 
          ?>
        </td>
        <td>
          <?php 
          echo "<a onclick='javascript:toggleDivVisibility(\"$divId\");'>";
          echo spl_object_hash($v[0]);
          echo "</a>" 
          ?>
        </td>
      </tr> 
      <tr>
        <td colspan=2>
          <?php
          echo "<div id='$divId' style='display:none;'>";
          echo "<br/>";
          H_Debugger::dump($v[0], 4);
          echo "</div>";
          ?>
        </td>
      </tr>  
      <?php
    }
    else {
      ?>
      <tr>
        <td>
          <?php echo $k; ?>
        </td>
        <td>
          <?php echo var_export($v[0], true); ?>
        </td>
      </tr>  
      <?php
    }
    ?>

    <?php endforeach; ?>
  </table>
</div>
