<?php defined("_JEXEC") or die();

$class = H_Document;
$user = JFactory::getUser();
$now = time();

if (F_Input::exists("id"))
{
  if (!($obj = $class::load(F_Input::getInteger("id", 0))))
  {
    return H_UiLang::notFound();
  }
  if ((int)$obj->userid !== (int)$user->id) {
    return H_UiLang::notAllowed();
  }
  if ($obj->source != H_Data::SOURCE_USER) {
    return H_UiLang::notAllowed();
  }
}
else
{
  // clone with default last values
  $obj = $class::create();
  $obj->userid = $user->id;
  $obj->source = H_Data::SOURCE_USER;
  $obj->time = time();
  
  $obj->name = date("d M Y", $now) . " doc";
}

F_Snippet::insertJScript("webcam2");
$commonFields = H_UiBuilderRecord::buildCommonFields($obj);

?>
<form action="<?php echo H_UiRouter::getBackto(H_UiRouter::getDocumentsUrl()); ?>" 
      method="post"
      enctype="multipart/form-data"
>
<table class='table table-noborders'>
  <input type="hidden" name="action" value="docs.edit.doc" />
  <input type="hidden" name="id" value="<?php echo $obj->id ? $obj->id : 0; ?>" />
  
  <tr>
    <?php echo $commonFields->name; ?>
  </tr>
  <tr>
    <?php echo $commonFields->group; ?>
  </tr>
  <tr>
    <td>
      Files
      <br/><br/>
    </td>
    <td>
      <div id="pre-files-list">
      <?php if ($obj->hasAttachments()) :
        $preDocIndex = 0;
        ?>
        <?php foreach ($obj->getAttachments() as $path) : 
          $aDoc = H_Document::getAdvancedAttachment($obj, $path);
          $aUrl = F_Addresses::absolutePathToRelativeUrl($aDoc->fullpath);
        ?>
          <div id='file-pre-div-<?php echo $preDocIndex; ?>'>
            <img 
              src='<?php echo F_MediaImage::getImagePath(
                "png/64x64/actions/dialog-cancel-3.png"); 
                ?>' 
              style='width:24px;cursor:pointer;'
              onclick='javascript:removePreFile(<?php echo $preDocIndex; ?>);'
            >
            <input type="hidden" id="predoc<?php echo $preDocIndex; ?>-value" name="if_predoc<?php echo $preDocIndex; ?>" value="1" />
            <a href='<?php echo $aUrl; ?>' target='_blank' > 
              <?php echo $aDoc->path; ?>

              <?php 
              $preview = H_Document::renderPreview($aDoc);
              if ($preview) {
                echo "<br/>" . $preview;
              }
              ?>
            </a>
            <br/><br/></div>
        <?php 
        $preDocIndex ++;
        endforeach; ?>
      <?php endif; ?>
      </div>
      <div id="files-list" >
      </div>
      <a onclick='javascript:addFileUploadInput();' 
          class='btn btn-success x-btn-small'>
        <span class='icon-plus' ></span>
        add another one
      </a>
      <br/><br/>
      <a onclick='javascript:activateSnapshot();' 
          class='btn btn-success x-btn-small'
          id='snapshot-activate-button' >
        <span class='icon-camera' ></span>
        take a photo
      </a>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <div id="snapshot-container" style="display:none; text-align:center;" >
          <div id="snapshot-preview" x-style="width:320px; height:240px;"></div>  
          <br/>
          <a href="javascript:docTakeDirectSnapshot();"
             class="btn btn-large btn-block"
             >
            <span class='icon-camera' ></span>
          </a>
          <div id="snapshot-comment" >
          </div>
          <br/>
          <br/>
          <div id="snapshot-list" >
            <!-- here goes the list -->
          </div>
        </div>
    </td>
  </tr>
  <tr>
    <?php echo $commonFields->time; ?>
  </tr>
  <tr>
    <?php echo $commonFields->notes; ?>
  </tr>
</table>
  
  <div class="form-actions">
    <button type="submit" name="action-save" class="btn btn-primary"
            >Save</button>
    <button type="submit" name="action-cancel" class="btn"
            >Cancel</button>
    
    <?php if ($obj->id) : ?>
    <button type="submit" name="action-delete" class="btn btn-danger" style="float:right;"
            >Delete</button>
    <?php endif; ?>
  </div>
</form>

<script>
Webcam.set({
  width: 240,
  height: 240*1.33,
  dest_width: 600,
  dest_height: 600*1.33,
  image_format: 'jpeg',
  jpeg_quality: 60,
  force_flash: false,
  flip_horiz: false,
  fps: 20
});

var fileUploadIndex = 0;
  
function activateSnapshot() {
  jQuery("#snapshot-activate-button")[0].remove();
  
  var snapshotDiv = jQuery("#snapshot-container")[0];
  snapshotDiv.style.display = "block";
  
  // selecting camera device
  console.log(Webcam.cameraIDs);
  
  if (Webcam.cameraIDs.length > 1) {
      Webcam.cameraID = 1;
  }
  
  Webcam.attach('#snapshot-preview');
}

function docTakeDirectSnapshot() {
  Webcam.snap( function(data_uri) {
    var newHtml = 
      "<div id='singlesnapshot-container-"+fileUploadIndex+"'>" +
        "<img src='<?php echo F_MediaImage::getImagePath(
            "png/64x64/actions/dialog-cancel-3.png"); 
            ?>' style='width:24px;cursor:pointer;' " +
        "onclick='javascript:docRemoveDirectSnapshot("+fileUploadIndex+");' " +
        "> " + 
        "<div id='singlesnapshot-preview-"+fileUploadIndex+"'><img src='"+data_uri+"'/></div>" +
        "<input type='hidden' id='singlesnapshot-data-"+fileUploadIndex+"' name='if_docsnap"+fileUploadIndex+"' value='"+data_uri+"' />" +
      "<br/></div>";

    fileUploadIndex++;
    var snapshotDiv = jQuery("#snapshot-list")[0];
    snapshotDiv.insertAdjacentHTML("beforeEnd", newHtml);
    
    jQuery("#snapshot-comment")[0].innerHTML = "picture " + fileUploadIndex +
      " taken";
    setTimeout(function() {
      jQuery("#snapshot-comment")[0].innerHTML = "";
    }, 500);
  });
}

function docRemoveDirectSnapshot(id) {
  Webcam.snap( function(data_uri) {
    document.getElementById('singlesnapshot-container-'+id).remove();
  } );
}

function removePreFile(id) {
  var value_input = jQuery("#predoc"+id+"-value")[0];
  var div = jQuery("#file-pre-div-"+id)[0];
  div.hidden = true;
  value_input.value = 0;
}
  
function removeFileUpload(id) {
  var div = jQuery("#file-upload-div-"+id)[0];
  div.remove();
}
  
function addFileUploadInput() {
  var files_div = jQuery("#files-list")[0];
  var html_to_add = "<div id='file-upload-div-"+fileUploadIndex+"'>" +
      "<img src='<?php echo F_MediaImage::getImagePath(
          "png/64x64/actions/dialog-cancel-3.png"); 
          ?>' style='width:24px;cursor:pointer;' " +
      "onclick='javascript:removeFileUpload("+fileUploadIndex+");' " +
      "> " + 
      "<input type='file' name='if_docfile"+fileUploadIndex+"' >" +
      "<br/><br/></div>";
  
  fileUploadIndex ++;
  
  files_div.insertAdjacentHTML("beforeEnd", html_to_add);
}
  
addFileUploadInput();
</script>