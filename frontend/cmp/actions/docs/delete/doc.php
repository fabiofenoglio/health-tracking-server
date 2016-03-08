<?php defined("_JEXEC") or die();

$class = H_Document;
$user = JFactory::getUser();

$input_id = (int)F_Input::getInteger("id");
if (!$input_id) {
  return H_UiLang::notFound();
}

$obj = $class::load(F_Input::getInteger("id"));
if (!$obj) {
  return H_UiLang::notFound();
}

if ((int)$obj->userid !== (int)$user->id) {
  return H_UiLang::notAllowed();
}

if ($obj->source != H_Data::SOURCE_USER) {
  return H_UiLang::notAllowed();
}

if ($obj->hasAttachments()) {
  foreach ($obj->getAttachments() as $path) {
    $path = $obj->getFullAttachmentPath($path);
    @unlink($path);
  }
}

if ($obj->delete()) {
  F_Log::showInfo("item deleted", "message");
}
else {
  F_Log::showError("error deleting item :(");
}