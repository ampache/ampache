<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
/*
 Copyright 2009, 2010 Timothy John Wood, Paul Arthur MacIain

 This file is part of php_musicbrainz
 
 php_musicbrainz is free software: you can redistribute it and/or modify
 it under the terms of the GNU Lesser General Public License as published by
 the Free Software Foundation, either version 2.1 of the License, or
 (at your option) any later version.
 
 php_musicbrainz is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Lesser General Public License for more details.
 
 You should have received a copy of the GNU Lesser General Public License
 along with php_musicbrainz.  If not, see <http://www.gnu.org/licenses/>.
*/
interface IWebService {
    public function get ($entity, $id, $include, $filter, $version = '1');
    public function post($entity, $id, $data, $version = '1');
}

class mbWebService implements IWebService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $nonce;
    private $realm;
    private $pathPrefix;
    private $lastError;
    private $fSock;
    private $lastResponse = "";
    private $lastHeaders  = array();

    /*
     * Constructor
     * 
     */
    public function __construct($host='musicbrainz.org', $port=80, $pathPrefix='/ws', $username='', $password='', $realm='musicbrainz.org') {
        $this->host = $host;
        $this->port = $port;
        $this->pathPrefix = $pathPrefix;
        $this->username = $username;
        $this->password = $password;
        $this->realm = $realm;
        $this->fSock = -1;
    }

    private function connect() {
        if ($this->fSock != -1) {
            return true;
        }

        $this->fSock = fsockopen($this->host, $this->port, $errno, $this->lastError, 30);

        if ($this->fSock == false) {
            $this->fSock = -1;
            return false;
        }

        return true;
    }

    private function close() {
        if ($this->fSock != -1) {
            fclose($this->fSock);
            $this->fSock = -1;
            return true;
        }
        else {
            $this->lastError = "Trying to close closed socket.";
            return false;
        }
    }

    private function parseHeaders($string) {
        $lines = explode("\n", $string);
        $this->lastHeaders = array();

        foreach ($lines as $key => $line) {
            if ($key == 0) { // Status line
                if (!preg_match("/^HTTP\/(\d+)\.(\d+) (\d+) .+$/", $line, $matches)) {
                    $this->lastHeaders = array();
                    return false;
                }
                else {
                    $this->lastHeaders['HTTP_major_version'] = $matches[1];
                    $this->lastHeaders['HTTP_minor_version'] = $matches[2];
                    $this->lastHeaders['HTTP_status'] = $matches[3];
                }
            }
            else if ($line == "\r") { // Empty line
                $new_string = "";
                for ($i = $key+1; $i < sizeof($lines); $i++) {
                    $new_string .= $lines[$i] . "\n";
                }
                return $new_string;
            }
            else if (!preg_match("/^([^:]+): (.+)\r$/", $line, $matches)) {
                // Not a header
                $this->lastHeaders = array();
                return false;
            }
            else { // A header
                $this->lastHeaders[$matches[1]] = $matches[2];
            }
        }

        // Something failed (like having no body), so we clear and return false
        $this->lastHeaders = array();
        return false;
    }

    private function getHeaders() {
        return $this->lastHeaders;
    }

    private function runRequest($method, $uri, $post_data='') {
        if ( ! ($this->connect() && 
          $this->sendRequest($method, $uri, $post_data))) {
            return false;
        }
                
        $this->lastResponse = $this->getResponse();

        if ($this->lastHeaders['HTTP_status'] == 401) {
            // We need to authenticate
            $authrequest = $this->lastHeaders['WWW-Authenticate'];
            preg_match('/nonce="(.+)"/', $authrequest, $matches);
            $this->nonce = $matches[1];

            $this->close();
            
            if ( ! ($this->connect() && 
              $this->sendRequest($method, $uri, $post_data))) {
                return false;
            }
            $this->lastResponse = $this->getResponse();
        }
        
        $this->close();

        if (isset($this->lastHeaders['HTTP_status']) && $this->lastHeaders['HTTP_status'] != 200) {
            return false;
        }
        
        return $this->lastResponse;
    }
        

    private function sendRequest($method, $uri, $post_data='') {
        if ($this->fSock == -1) {
            $this->lastError = "Trying to write to closed socket.";
            return false;
        }

        fwrite($this->fSock, "$method $uri HTTP/1.1\r\n");
        fwrite($this->fSock, "Host: " . $this->host . "\r\n");
        fwrite($this->fSock, "Accept: */*\r\n");
        fwrite($this->fSock, "User-Agent: php_musicbrainz/1.0\r\n");
		if($post_data) {
			fwrite($this->fSock, "Content-Type: application/x-www-form-urlencoded\r\n");
			fwrite($this->fSock, "Content-Length: " . (strlen($post_data . "\r\n")) . "\r\n");
		}
        if ($this->nonce) {
            $h1 = md5($this->username . ':' . $this->realm . ':' . 
                $this->password);
            $h2 = md5($method . ':' . $uri);
            $response = md5($h1 . ':' . $this->nonce . ':' . $h2);
            $authResponse  = 'Authorization: Digest username="' . 
                $this->username . '", realm="' . $this->realm . '", nonce="' . 
                $this->nonce . '", uri="' . $uri . '", response="' . $response .
                '"';
            fwrite($this->fSock, "$authResponse\r\n");
        }
        fwrite( $this->fSock, "Connection: close\r\n\r\n");
        fwrite($this->fSock, $post_data . "\r\n\r\n");
        return true;
    }

    private function getResponse() {
        if ($this->fSock == -1) {
            $this->lastError = "Trying to read from closed socket.";
            return false;
        }

        $buffer = "";

        while (!feof($this->fSock)) {
            $buffer .= fread($this->fSock, 4096);
        }

        if (!$this->parseHeaders($buffer)) {
            return $buffer;
        }
        
        return $this->parseHeaders($buffer);
    }

    public function get($entity, $uid, $includes, $filters, $version="1") {
        $params = array();
        $params['type'] = "xml";

        if (is_array($includes)) {
            $inc_string = "";
            foreach ($includes as $inc) {
                if ($inc_string != "")
                    $inc_string .= " ";
                $inc_string .= $inc;
            }
            if ($inc_string != "") {
                $params['inc'] = $inc_string;
            }
        }

        if (is_array($filters)) {
            foreach ($filters as $filter => $value) {
                $params[$filter] = $value;
            }
        }

        $uri = $this->pathPrefix . "/" . $version . "/" . $entity . "/" . $uid . "?" . $this->build_query($params);


        return $this->runRequest("GET", $uri);
    }

    public function post($entity, $id, $data, $version = '1') {
        $uri = $this->pathPrefix . '/' . $version . '/' . $entity . '/' . $id;
        if ($this->fSock == -1 && !$this->connect()) {
            return false;
        }
		$data = $this->build_query($data);
		$data = ltrim($data, '?');
        return $this->runRequest("POST", $uri, $data);
    }

    private function build_query($array) {
        $first = true;
        $query_string = "";

        if (!is_array($array) || sizeof($array) == 0)
          return "";

        foreach ($array as $key => $value) {
            $query_string .= ($first ? "" : "&") . "$key=" . urlencode($value);
            if ($first) { $first = false; }
        }

        return $query_string;
    }
}
?>
