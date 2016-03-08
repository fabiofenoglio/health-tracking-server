<?php
defined("_INAPI") or die();

// TODO remove output data that should be hidden (after test)

class ApiCmd_UserDisk extends F_ApiCmd
{
    private $_userdisk;
    
    private $_max_path_len = 240;
    
    public function execute()
    {
        $action = F_Safety::sanitize(F_Input::getRaw("action"), F_Safety::ALPHA_NUM_SCORES);
        $method_name = "do_" . $action;

        if ($action === null || !method_exists($this, $method_name))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "wrong or missing action");

        $this->_userdisk = $this->api_call->getUserDisk();
        if (!$this->_userdisk)
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::INTERNAL_ERROR, null, "internal error #1");
        
        return $this->$method_name();
    }
    
    private function do_file_put()
    {
        $arg1 = F_SafetyPath::sanitizeAndMakeDirectPath(F_Input::getRaw("path"));
        if (! F_Safety::verifyStrLen($arg1, 1, $this->_max_path_len))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "invalid path length");
        
        $path_to_file = $this->_userdisk->getAbsoluteFilePath($arg1, true);
        
        $upl = F_Input::getUploadedFile("file");
        if (! $upl->valid)
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR,
                    null,
                    "you did not send a valid file");
        
        $space_needed = filesize($upl->actualPath);
        if (file_exists($path_to_file)) $space_needed -= filesize($path_to_file);
        if ($space_needed > 0 && !$this->api_call->userQuota->hasFreeDataSpace($space_needed))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::QUOTA_LIMIT_EXCEEDED,
                    null,
                    "disk quota exceeded");
        
        if (! $upl->moveto($path_to_file))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::INTERNAL_ERROR,
                    null,
                    "error replacing server data");
        
        $this->api_call->userQuota->addDataSize($space_needed);
        $this->api_call->userQuota->commit();
        
        return $this->api_call->SetResultAndData(
                F_ApiResponse::SUCCESS,
                null,
                "OK");
    }
    
    private function do_file_get()
    {
        $arg1 = F_SafetyPath::sanitizeAndMakeDirectPath(F_Input::getRaw("path"));
        if (! F_Safety::verifyStrLen($arg1, 1, $this->_max_path_len))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "invalid path length: " . $arg1);
        
        $path_to_file = $this->_userdisk->getAbsoluteFilePath($arg1, false);

        if (!file_exists($path_to_file))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "file not found: " . $arg1);

        $token = F_Download::generateDownloadToken($path_to_file, 
                basename($path_to_file), 120);
        
        if (!$token)
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::INTERNAL_ERROR,
                    null,
                    "internal error #5");
        
        return $this->api_call->SetResultAndData(
                F_ApiResponse::SUCCESS,
                null,
                F_Download::getTokenDownloadUrl($token->code));
    }
    
    private function do_file_read()
    {
        $max_numb = 1024*1024;
        $def_numb = 1024;
        
        $arg1 = F_SafetyPath::sanitizeAndMakeDirectPath(F_Input::getRaw("path"));
        $numb = F_Input::getInteger("length", $def_numb, 1, $max_numb);
        $offset = F_Input::getInteger("start", 0);
        
        if (! F_Safety::verifyStrLen($arg1, 1, $this->_max_path_len))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "invalid path length: " . $arg1);
        
        $path_to_file = $this->_userdisk->getAbsoluteFilePath($arg1, false);

        if (!file_exists($path_to_file))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "file not found: " . $arg1);

        $handle = F_Io::lockFile($path_to_file, F_Io::MODE_R);
        if (!$handle)
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::INTERNAL_ERROR, null, "internal error #2");
        
        if (fseek($handle, $offset) !== 0)
        {
            F_Io::releaseFile($handle);
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::INTERNAL_ERROR, null, "internal error #3");
        }

        $content = fread($handle, $numb);
        F_Io::releaseFile($handle);
        
        if ($content === false)
            return $this->api_call->SetResultAndData( 
                    F_ApiResponse::INTERNAL_ERROR, null, "internal error #4");
        
        return $this->api_call->SetResultAndData(
                F_ApiResponse::SUCCESS, null, base64_encode($content));
    }
    
    private function do_file_exists()
    {
        $arg1 = F_SafetyPath::sanitizeAndMakeDirectPath(F_Input::getRaw("path"));
        
        if (! F_Safety::verifyStrLen($arg1, 1, $this->_max_path_len))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "invalid path length: " . $arg1);
        
        $path_to_file = $this->_userdisk->getAbsoluteFilePath($arg1, false);

        return $this->api_call->SetResultAndData(
                F_ApiResponse::SUCCESS, null,
                (file_exists($path_to_file) ? "1" : "0"));
    }
    
    private function do_dir_exists()
    {
        $arg1 = F_SafetyPath::sanitizeAndMakeDirectPath(F_Input::getRaw("path"));
        
        if (! F_Safety::verifyStrLen($arg1, 1, $this->_max_path_len))
            return $this->api_call->SetResultAndData(  
                    F_ApiResponse::GENERIC_ERROR, null, "invalid path length: " . $arg1);
        
        $path_to_dir = $this->_userdisk->getAbsoluteDirectory($arg1, false);

        return $this->api_call->SetResultAndData(
                F_ApiResponse::SUCCESS, null,
                (file_exists($path_to_dir) ? "1" : "0"));
    }
    
    private function do_test()
    {
        return $this->api_call->SetResultAndData(F_ApiResponse::SUCCESS, null, "OK");
    }
}
