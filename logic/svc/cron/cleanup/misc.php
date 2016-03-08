<?php
defined("_JEXEC") or die();

class CronSvc_CleanupMisc extends F_CronSvc
{
    private $delcnt = 0;
    private $now;
    
    public function execute($params)
    {
        if ($params !== null) $params = null;
        $call = $this->activity;

        $folder = F_Addresses::cleanSlashes(F_MediaText::getImageTextFolder());
        
        $this->now = time();
        
        if (!$this->___cleanImageTextFolder($folder))
        {
            $call->addLog("Error cleaning textimage folder: " . $this->getError(), F_Log::ERROR);
            return F_CronHelper::RESULT_ERROR;
        }
        else
        {
            if ($this->delcnt) $call->addEcho($this->delcnt . " immagini di testo offuscato eliminate");
            return F_CronHelper::RESULT_SUCCESS;
        }
    }
    
    private function ___cleanImageTextFolder($folder)
    {
        if (!($handle = @opendir($folder)))
        {
            $this->setError("could not open directory");
            return true;
        }

        while (($entry = readdir($handle)) !== false)
        {
            // Skip useless files
            if ($entry == "." || $entry == "..") continue;
            $path = $folder . "/" . $entry;
            
            if (!is_dir($path))
            {
                // Skip non-png files
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if (strtolower($ext) == "png")
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
            else if (!$this->___cleanImageTextFolder($path)) return false;
        }

        closedir($handle);
        return true;
    }
}