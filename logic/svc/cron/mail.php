<?php
defined("_JEXEC") or die();

class CronSvc_Mail extends F_CronSvc
{
    public function execute($params)
    {
        if ($params !== null) $params = null;
        $call = $this->activity;
        
        $lock = F_SafetyLock::waitLock(F_MailHelper::LOCKID, 60);
        try
        {
            $result = $this->___run();
        } 
        catch (Exception $ex) 
        {
            $call->addLog("runtime exception : " . $ex->getMessage() . "", F_Log::ERROR);
            $result = null;
        }
        
        $lock->release();

        return $result;
    }
    
    private function ___run()
    {
        $call = $this->activity;
        $call->setStatus(F_CronHelper::RESULT_SUCCESS);
        
        if (!F_Config::getJifCfg("sendgrid_activate", false)) return;
        
        // Avoid timeout
        set_time_limit(0);
        
        $this->maxMailICanSend = (int)F_ConfigCron::MAX_MAILS_PER_CALL;
        if ($this->maxMailICanSend < 1) $this->maxMailICanSend = 1;
        $this->mailSent = 0;

        $allMails = F_MailHelper::getAll("priority DESC");

        foreach ($allMails as $mail)
        {
            if (!F_MailHelper::is_ready_to_send($mail)) continue;
            
            $this->___process_mail($mail);
            
            if ($this->mailSent >= $this->maxMailICanSend) break;
        }
    }
    
    private function ___process_mail($mail)
    {
        $call = $this->activity;
        
        // Check if system prebuilt email
        if (isset($mail->data->mail))
        {
            return $this->___process_prebuilt_mail($mail);
        }

        // Check to have a sender
        if (($usrfrom = $this->___get_sender($mail)) === FALSE) return;

        // Get a second mail to store errors and a third mail to store successes
        $notSentMail =  $this->___mail_getnew($mail, F_MailHelper::STATUS_NOTSENT);
        $notSentMail->timescheduled = time() + F_ConfigMail::NOTSENT_DELAY;
        
        // Unpack all dests
        $unpackedMails = $this->___mail_unpackdest($mail);
        $mail->getDest()->clear();
        $mail->getDest()->emails = $unpackedMails;

        // Process emails
        foreach ($unpackedMails as $destMail)
        {
            if ($this->mailSent++ >= $this->maxMailICanSend) break;

            // Remove from original mail and save if not empty
            unset($mail->getDest()->emails[$destMail]);
            if (! $mail->getDest()->isEmpty()) $mail->store();

            $sentMail = $this->___mail_getnew($mail, F_MailHelper::STATUS_SENT);
            array_push($sentMail->getDest()->emails, $destMail);
            
            if (F_MailSender::processOutgoing($sentMail, $destMail))
            {
                $sentMail->details = "sent time: " . date("j M Y H:i", time());
                $sentMail->store();
            }
            else
            {
                array_push($notSentMail->getDest()->emails, $destMail);

                $str = "invio fallito; " . JError::getError();
                if (!substr_count($notSentMail->details, $str)) $notSentMail->details .= $str . "<br/>";
                $call->addLog("Error sending mail to " . $destMail . ". will retry again", F_Log::ERROR);
                $call->setStatus(F_CronHelper::RESULT_WARNING);
            }
        }

        // save remaining bundle or delete if finished
        if (!($mail->getDest()->isEmpty() ? $mail->delete() : $mail->store())) 
            $call->addLog($mail->getError(), F_Log::ERROR);

        // if email has been splitted, save chunk email bundles
        if (! $notSentMail  ->getDest()->isEmpty()) $notSentMail->store();
    }
    
    private function ___process_prebuilt_mail($mail)
    {
        $call = $this->activity;

        if (F_MailSender::processOutgoing($mail, null))
        {
            $mail->details = "sent time: " . date("j M Y H:i", time());
            $mail->state = F_MailHelper::STATUS_SENT;
        }
        else
        {
            $mail->details .= "invio fallito; " . JError::getError() . "<br/>";
            $mail->state = F_MailHelper::STATUS_NOTSENT;
            $mail->timescheduled = time() + F_ConfigMail::NOTSENT_DELAY;
            $call->addLog("Error sending prebuilt mail. will retry again", F_Log::ERROR);
            $call->setStatus(F_CronHelper::RESULT_WARNING);
        }
        
        $mail->store();
    }

    private function ___mail_getnew($from_mail, $status = F_MailHelper::STATUS_NOTSENT)
    {
        // Get a second mail to store errors
        $newmail = clone $from_mail;
        $newmail->set_id(0);
        $newmail->state = $status;
        $newmail->getDest()->clear();
        $newmail->details = "";
        return $newmail;
    }

    private function ___get_sender($mail)
    {
        $call = $this->activity;

        if ($mail->from != F_MailHelper::SENDER_SYSTEM)
        {
            if (!($usrfrom = F_User::getUserById((int)$mail->from)))
            {
                $call->setStatus(F_CronHelper::RESULT_WARNING);
                $mail->state = F_MailHelper::STATUS_REFUSED;
                $mail->details = "Impossibile mandare una mail quando non e' noto l'utente" .
                        " che la vuole inviare";
                $mail->store();
                $call->addLog("Can't send mail from missing user (\"" . $mail->subject . "\")",
                        F_Log::ERROR);

                return FALSE;
            }
            if (empty($usrfrom->email))
            {
                $call->setStatus(F_CronHelper::RESULT_WARNING);
                $mail->state = F_MailHelper::STATUS_REFUSED;
                $mail->details = "Impossibile mandare una mail da un utente il cui indirizzo" .
                        " email non e' noto";
                $mail->store();
                $call->addLog("Can't send mail from user without email (\"" . $mail->subject . "\")",
                        F_Log::ERROR);

                return FALSE;
            }
            
            return $usrfrom;
        }
        else
            return F_MailHelper::SENDER_SYSTEM;
    }

    private function ___mail_unpackdest($mail)
    {
        $allDests = $mail->getDest();
        $mails = array();

        // Import send groups
        if (!empty($allDests->groups))
        {
            foreach ($allDests->groups as $groupid)
            {
                $usersInThatGroup = F_User::getGroupUsersIds((int)$groupid);
                if (empty($usersInThatGroup)) continue;

                foreach ($usersInThatGroup as $userid)
                {
                    if (($emailstr = $this->___mail_get_user_email($userid)))
                        $mails[$emailstr] = $emailstr;
                }
            }
        }

        // Import single users
        if (!empty($allDests->users))
        {
            foreach ($allDests->users as $userid) 
            {
                if (($emailstr = $this->___mail_get_user_email($userid)))
                    $mails[$emailstr] = $emailstr;
            }
        }

        // Import emails
        if (!empty($allDests->emails))
        {
            foreach ($allDests->emails as $emailstr) 
                $mails[$emailstr] = $emailstr;
        }

        // Import specials
        if (!empty($allDests->specials))
        {
            // TODO
        }

        return $mails;
    }

    private function ___mail_get_user_email($userid, $username = null)
    {
        $call = $this->activity;

        if (is_numeric($userid))
            $usr = F_User::getUserById((int)$userid);
        else
            $usr = F_User::getUserByUsername($userid);
        
        if (!$usr)
        {
            $call->setStatus(F_CronHelper::RESULT_WARNING);
            $call->addLog("Can't send mail to missing user ".$userid." : user not found", F_Log::ERROR);
            return null;
        }
        if (empty($usr->email))
        {
            $call->setStatus(F_CronHelper::RESULT_WARNING);
            $call->addLog("Can't send mail to user without email ".$usr->username."", F_Log::ERROR);
            return null;
        }

        return $usr->email;
    }
}