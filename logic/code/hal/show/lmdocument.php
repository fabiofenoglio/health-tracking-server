<?php defined("_JEXEC") or die();

$params = F_Snippet::getParams();
$display_time = $params->get("time", null);

if ($params->get("header") == true)
{
  echo "<tr>";
  echo "<th></th>";
  echo "<th>Group</th>";
  echo "<th>Name</th>";
  echo "<th>Time</th>";
  echo "<th></th>";  
  echo "</tr>";
  return;
}

$el = $params->get("element");
if (!$el) die("param error");

$user = JFactory::getUser();

$edit_url = H_UiRouter::getEditDocumentUrl($el->id);

?>
<tr>
  <td>
    <!-- left side -->
    <?php if ($el->source == H_Data::SOURCE_USER) : ?>
    <a href='<?php echo $edit_url; ?>'><span class='icon-pencil' ></span>
    </a>
    <?php endif; ?>
  </td>
  <td>
    <!-- group -->
    <small>
    <?php echo $el->group; ?>
    </small>
  </td>
  <td>
    <!-- name -->
    <?php 
    echo strlen($el->name) > 0 ? $el->name : "???";
    ?>
    <br/>
    <?php    
    if ($el->hasAttachments()) {
      $attList = $el->getAttachments();
      if (LM_MOBILE) {
        echo "<br/><small>" . count($attList) . " files</small>";
      }
      else {
        echo "<br/>";
        $cnt = 0;
        $maxDisplayed = LM_MOBILE ? 3 : 10;

        foreach ($attList as $path) {
          $cnt ++;

          if ($cnt > $maxDisplayed) {
            echo " ... ";
            break;
          }
          if (!($cnt % 6)) {
            echo "<br/>";
          }

          $aDoc = H_Document::getAdvancedAttachment($el, $path);
          $aUrl = F_Addresses::absolutePathToRelativeUrl($aDoc->fullpath);
          $preview = H_Document::renderPreview($aDoc, array("thumbsize" => 80));
          if ($preview) {
            echo "<a href='$aUrl' target='_blank' " .
                  "style='padding-bottom:5px;' > ";
            echo $preview . " ";
            echo "</a> ";
            if (LM_MOBILE) {
              echo "<br/><br/>";
            }
          }
        }
      }
    }
    ?>
  </td>
  <td>
    <!-- time -->
    <?php  echo H_UiCells::getTimeCell($el); ?>
    <br/><br/>
  </td>
  <td>
    <!-- right side -->
  </td>
</tr>