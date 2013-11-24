<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
 
class PluginHelper {

    public static function wsGet($url) {
    
        $u = parse_url($url);
        if ($u['scheme'] != 'http') {
            debug_event('PluginHelper', 'Unsupported url scheme (' . $u['scheme'] . ').', '5');
            return false;
        }
        
        $errno;
        $lastError;
        
        $fp = fsockopen($u['host'], $u['port'] ?: 80, $errno, $lastError, 30);
        if ($fp == false) {
            debug_event('PluginHelper', 'Cannot access host: ' . $u['host'], '5');
            debug_event('PluginHelper', 'Errno ' . $errno . ': ' . $lastError, '5');
            return null;
        }
        
        fwrite($fp, "GET " . $u['path'] . (($u['query']) ? ('?' . $u['query']) : '') . " HTTP/1.1\r\n");
        fwrite($fp, "Host: " . $u['host'] . "\r\n");
        fwrite($fp, "Accept: */*\r\n");
        fwrite($fp, "User-Agent: Ampache/1.0\r\n");
        fwrite($fp, "Connection: close\r\n\r\n");
        
        $buffer = "";
        while (!feof($fp)) {
            $buffer .= fread($fp, 4096);
        }
        fclose($fp);
        
        return self::parseHeaders($buffer);
    }

    protected static function parseHeaders($string) {
        $lines = explode("\n", $string);
        $response = array();
        $response['headers'] = array();

        foreach ($lines as $key => $line) {
            if ($key == 0) { // Status line
                if (!preg_match("/^HTTP\/(\d+)\.(\d+) (\d+) .+$/", $line, $matches)) {
                    return false;
                }
                else {
                    $response['http'] = $matches[1] . '.' . $matches[2];
                    $response['status'] = $matches[3];
                }
            }
            else if ($line == "\r") { // Empty line
                $new_string = "";
                for ($i = $key+1; $i < sizeof($lines); $i++) {
                    $new_string .= $lines[$i] . "\n";
                }
                $response['body'] = $new_string;
                return $response;
            }
            else if (!preg_match("/^([^:]+): (.+)\r$/", $line, $matches)) {
                // Not a header
                return false;
            }
            else { // A header
               $response['headers'][$matches[1]] = $matches[2];
            }
        }

        return false;
    }
}
