<?php defined("_JEXEC") or die();

$class = H_Document;

if (F_Input::exists("action-cancel"))
{
  // F_Log::showWarning("changes discarded");
  return;
}

if (F_Input::exists("action-delete"))
{
  F_SimplecomponentHelper::show("cmp.actions.docs.delete.doc");
  return;
}

if (F_Input::exists("action-save"))
{
  $action = "save";
}
else
{
  F_Log::showError("unsupported operation request");
  return;
}

$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  // new object
  $obj = $class::create();
  $obj->userid = $user->id;
  $obj->source = H_Data::SOURCE_USER;
}
else {
  // load object
  $obj = $class::load(F_Input::getInteger("id"));
  if (!$obj || $obj->source != H_Data::SOURCE_USER) {
    return H_UiLang::notFound();
  }
}

if ((int)$obj->userid !== (int)$user->id) {
  return H_UiLang::notAllowed();
}

// save data
$obj->name =    F_Input::getRaw("if_name", "???");
$obj->group =   F_Input::getRaw("if_group", "???");
$obj->time =    strtotime(F_Input::getRaw("if_time"));

$obj->data->note = F_Input::getRaw("if_note", null);
if ($obj->data->note == "") $obj->data->note = null;

// delete predocs

$newAtt = array();
if ($obj->hasAttachments()) {
  $i = -1;
  foreach ($obj->getAttachments() as $path) {
    ++$i;
    if (F_Input::getInteger("if_predoc" . $i, '1') == '1') {
      $newAtt[] = $path;
      continue;
    }
    $path = $obj->getFullAttachmentPath($path);
    @unlink($path);
  }
}
else {
  $i = 0;
}

// handle snapshots
foreach ($_POST as $requestVarK => $requestVarV) {
  if (! F_UtilsString::startsWith($requestVarK, "if_docsnap")) {
    continue;
  }

  $destinationName = null;
  $fullDestinationPath = null;
  while ($destinationName === null || file_exists($fullDestinationPath)) {
    $destinationName = F_UtilsRandom::generateUniqueTimeCode() . ".jpg";
    $fullDestinationPath = $obj->getFullAttachmentPath($destinationName);
  }  
  
  // write file
  F_Io::ensureDirectoryExists(dirname($fullDestinationPath));
  
	$imgData = str_replace(' ', '+', $requestVarV);
	$imgData = substr($imgData, strpos($imgData,",") + 1);
	$imgData = base64_decode($imgData);
	
	// Write $imgData into the image file
	$fileHandler = fopen($fullDestinationPath, 'w');
	fwrite($fileHandler, $imgData);
	fclose($fileHandler);
  
  $newAtt[] = $destinationName;
}

// handle docs
foreach ($_FILES as $requestVarK => $requestVarV) {
  if (! F_UtilsString::startsWith($requestVarK, "if_docfile")) {
    continue;
  }

  $index = substr($requestVarK, 10);
  $uploaded = F_Input::getUploadedFile($requestVarK);
  if (!$uploaded->valid) {
    continue;
  }
  
  $destinationName = $uploaded->name;
  $fullDestinationPath = $obj->getFullAttachmentPath($destinationName);
  while (file_exists($fullDestinationPath)) {
    $destinationName = F_UtilsRandom::generateUniqueTimeCode() . "." . pathinfo($uploaded->name, PATHINFO_EXTENSION);  
    $fullDestinationPath = $obj->getFullAttachmentPath($destinationName);
  }  
  
  // move file
  F_Io::ensureDirectoryExists(dirname($fullDestinationPath));
  if (!$uploaded->moveto($fullDestinationPath)) {
    F_Log::showError($uploaded->getError());
    return;
  }
 
  $newAtt[] = $destinationName;
}

$obj->data->attachments = $newAtt;

if ($obj->store()) {
  F_Log::showInfo("changes saved", "message");
}
else {
  F_Log::showError("error saving changes :(");
}