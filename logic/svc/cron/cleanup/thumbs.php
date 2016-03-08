<?php
defined("_JEXEC") or die();

class CronSvc_CleanupThumbs extends F_CronSvc
{
    private $delcnt = 0;
    private $now;
    
    public function execute($params)
    {
        if ($params !== null) $params = null;
        $call = $this->activity;

        $folder = rtrim(F_MediaImage::getMainThumbsPath(), "\\/");
        
        if (!file_exists($folder)) return F_CronHelper::RESULT_SUCCESS;

        $this->now = time();
        
        if (!$this->___cleanThumbsFolder(strlen($folder) + 1, $folder))
        {
            $call->addLog("Error cleaning thumbs folder: " . $this->getError(), F_log::ERROR);
            return F_CronHelper::RESULT_ERROR;
        }
        else
        {
            if ($this->delcnt) $call->addEcho($this->delcnt . " anteprime obsolete eliminate");
            return F_CronHelper::RESULT_SUCCESS;
        }
    }
    
    private function ___cleanThumbsFolder($dir, $folder)
    {
        if (!($handle = @opendir($folder)))
        {
            $this->setError("could not open directory");
            return false;
        }

        while (($entry = readdir($handle)) !== false)
        {
            // Skip useless files
            if ($entry == "." || $entry == "..") continue;
            $path = $folder . "/" . $entry;
            
            if (!is_dir($path))
            {
                // Skip non-jpg files
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if (strtolower($ext) == "jpg")
                {
                    // Get mod time
                    $time = fileatime($path);
                    if (($this->now - $time) > F_ConfigCron::THUMBS_MAX_LIFETIME)
                    {
                        // Delete thumb
                        if (F_Io::deleteFile($path)) $this->delcnt ++;
                        else
                        {
                            $this->setError(F_Io::getError());
                            return false;
                        }
                    }
                }
            }
            else if (!$this->___cleanThumbsFolder($dir, $path)) return false;
        }

        closedir($handle);
        return true;
    }
}

?>