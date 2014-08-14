<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

// SubsonicClient inspired from https://github.com/webeight/SubExt

class BeetsClient {
    protected $_serverUrl;
    protected $_serverPort;
    protected $_creds;
    protected $_commands = array(
        'item' => 'item/%d',
        'itemFile' => 'item/%d/file',
        'itemQuery' => 'item/query/%s',
        'album' => 'album/%d',
        'albumQuery' => 'album/query/%s',
        'artist' => 'artist/'
    );

    function __construct($serverUrl) {
        $this->setServer($serverUrl);
    }

    public function queryBeets($action, $argument = '', $rawAnswer=false) {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            
            $url = $this->getServer() . '/' . $this->getCommand($action, $argument);
            
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_PORT => intval($this->_serverPort)
            );
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $answer = curl_exec($ch);
            curl_close($ch);
            if($rawAnswer) {
                return $answer;
            }
            else {
                return $this->parseResponse($answer);
            }
        }
        else {
            return $this->error("Error: Invalid beets command: " . $action);
        }
    }

    public function setServer($server, $port=null) {
        $protocol = "";
        if (preg_match("/^https\:\/\//", $server)) {
            $protocol = "https://";
        }
        if (empty($protocol)) {
            if(!preg_match("/^http\:\/\//", $server)) {
                $server = "http://". $server;
            }
            $protocol = "http://";
        }
        preg_match("/\:\d{1,6}$/", $server, $matches);
        if(count($matches)) {
            // If theres a port on the url, remove it and save it for later use.
            $server = str_replace($matches[0], "", $server);
            $_port = str_replace(":", "", $matches[0]);
        }
        if($port == null && isset($_port)) {
            // If port parameter not set but there was one on the url, use the one from the url.
            $port = $_port;
        }
        else if($port == null) {
            $port = ($protocol == "https") ? '443' : '80';
        }
        $this->_serverUrl = $server;
        $this->_serverPort = $port;
    }

    public function getServer() {
        return $this->_serverUrl . ":" . $this->_serverPort;
    }

    protected function error($error, $data=null) {
        error_log($error ."\n". print_r($data, true));
        return (object) array("success"=>false, "error"=>$error, "data"=>$data);
    }

    protected function parseResponse($response) {
        $jsonArr = json_decode($response, true);
        if(is_array($jsonArr)) {
            return $jsonArr;
        }
        else {
            return $this->error("Invalid response from server!", $jsonArr);
        }        
    }
    
    public function getCommand($command, $param) {
        $string = sprintf($this->_commands[$command], rawurlencode($param));
        if($param === null) {
            $string = rtrim($string, '0');
        }
        return $string;
    }

    public function isCommand($command) {
        return array_key_exists($command, $this->_commands);
    }

    public function __call($action, $arguments) {
        $argument = count($arguments) ? (string) $arguments[0] : null;
        return $this->queryBeets($action, $argument);
    }
}

?>