<?php

class H_Document extends H_BaseTablehelper
{
  const CLASS_NAME = "lmdocument"; 
  
  public static function getAdvancedAttachment($doc, $path) {
    $o = new JObject();
    
    $o->document = $doc;
    $o->path = $path;
    $o->fullpath = $doc->getFullAttachmentPath($path);
    $o->pathinfo = pathinfo($o->path);
    $o->extension = strtolower($o->pathinfo["extension"]);
    
    return $o;
  }
  
  public static function renderPreview($aDoc, $params = null) {
    if (in_array($aDoc->extension, array("jpg", "jpeg"))) {
      return self::renderPreviewImageThumb($aDoc, $params);
    }
  }
  
  public static function renderPreviewImageThumb($aDoc, $params = null) {
    if (!file_exists($aDoc->fullpath)) {
      self::setError("original file is missing");
      return null;
    }
    
    $thumbSize = 120;
    
    if ($params !== null) {
      if (isset($params["thumbsize"])) {
        $thumbSize = (int)$params["thumbsize"];
      }
    }
    
    $thumbPath = $aDoc->fullpath . ".thumb.$thumbSize." . $aDoc->extension;
    
    // TODO check age
    if (!file_exists($thumbPath)) {
      F_MediaImage::createThumb($aDoc->fullpath, $thumbPath, $thumbSize);  
    }
    
    if (!file_exists($thumbPath)) {
      self::setError(F_MediaImage::getError());
      return null;
    }
    
    $thumbUrl = F_Addresses::absolutePathToRelativeUrl($thumbPath);
    return "<img src='$thumbUrl' style='width:$thumbSize px;' />"; 
  }
  
}
