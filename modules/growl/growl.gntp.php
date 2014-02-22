<?php

/*
Title:      Growl GNTP
URL:        http://github.com/jamiebicknell/Growl-GNTP
Author:     Jamie Bicknell
Twitter:    @jamiebicknell
*/

/*
Copyright (c) 2012-2014 Jamie Bicknell

This software is provided 'as-is', without any express or implied
warranty. In no event will the authors be held liable for any damages
arising from the use of this software.

Permission is granted to anyone to use this software for any purpose,
including commercial applications, and to alter it and redistribute it
freely, subject to the following restrictions:

   1. The origin of this software must not be misrepresented; you must not
   claim that you wrote the original software. If you use this software
   in a product, an acknowledgment in the product documentation would be
   appreciated but is not required.

   2. Altered source versions must be plainly marked as such, and must not be
   misrepresented as being the original software.

   3. This notice may not be removed or altered from any source
   distribution.
*/


class Growl {
    
    private $port = 23053;
    private $time = 5;
    
    public function Growl($host, $pass) {
        $this->host = $host;
        $this->pass = $pass;
        $this->salt = md5(uniqid());
        $this->application = '';
        $this->notification = '';
    }
    
    public function createHash() {
        $pass_hex = bin2hex($this->pass);
        $salt_hex = bin2hex($this->salt);
        $pass_bytes = pack('H*',$pass_hex);
        $salt_bytes = pack('H*',$salt_hex);
        return strtoupper('md5:'.md5(md5($pass_bytes.$salt_bytes,true)).'.'.$salt_hex);
    }
    
    public function setApplication($application, $notification) {
        $this->application = $application;
        $this->notification = $notification;
    }
    
    public function registerApplication($icon = NULL) {
        $data  = 'GNTP/1.0 REGISTER NONE ' . $this->createHash() . "\r\n";
        $data .= 'Application-Name: ' . $this->application . "\r\n";
        if($icon!=NULL) {
            $data .= 'Application-Icon: ' . $icon . "\r\n";
        }
        $data .= 'Notifications-Count: 1' . "\r\n\r\n";
        $data .= 'Notification-Name: ' . $this->notification . "\r\n";
        $data .= 'Notification-Enabled: True' . "\r\n";
        $data .= "\r\n\r\n";
        $data .= 'Origin-Software-Name: growl.gntp.php' . "\r\n";
        $data .= 'Origin-Software-Version: 1.0' . "\r\n";
        $this->send($data);
    }
    
    public function notify($title, $text = '', $icon = NULL, $url = NULL) {
        $data  = 'GNTP/1.0 NOTIFY NONE ' . $this->createHash() . "\r\n";
        $data .= 'Application-Name: ' . $this->application . "\r\n";
        $data .= 'Notification-Name: ' . $this->notification . "\r\n";
        $data .= 'Notification-Title: ' . $title . "\r\n";
        $data .= 'Notification-Text: ' . $text . "\r\n";
        $data .= 'Notification-Sticky: False' . "\r\n";
        if($icon!=NULL) {
            $data .= 'Notification-Icon: ' . $icon . "\r\n";
        }
        if($url!=NULL) {
            $data .= 'Notification-Callback-Target-Method: GET' . "\r\n";
            $data .= 'Notification-Callback-Target: ' . $url . "\r\n";
        }
        $data .= "\r\n\r\n";
        $data .= 'Origin-Software-Name: growl.gntp.php' . "\r\n";
        $data .= 'Origin-Software-Version: 1.0' . "\r\n";
        $this->send($data);
    }
    
    public function send($data) {
        $fp = fsockopen($this->host,$this->port,$errno,$errstr,$this->time);
        if(!$fp) {
            echo $errstr . ' (' . $errno . ')';
        }
        else {
            fwrite($fp,$data);
            fread($fp,12);
            fclose($fp);
        }
    }
    
}
