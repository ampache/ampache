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

class BeetsClientLocal extends BeetsClient {
    protected $_commands = array(
        'item' => 'ls id:%d',
        'itemQuery' => 'ls %s',
        'album' => 'ls albumid:%d',
        'albumQuery' => 'ls album:%s'
    );
    protected $beetsCommand;
    protected $beetsFieldFormat;
    protected $beetsFields;


    public function __construct($beetsCommand) {
        $this->beetsCommand = $beetsCommand;
        $handle = popen($this->beetsCommand . ' fields', 'r');
        $fields = array();
        while(!feof($handle)) { 
            if(preg_match('/  (.*)$/', fgets($handle), $matches)) {
                $fields[] = $matches[1];
            }
        }
        $this->beetsFieldFormat = implode('###', $fields);
        $this->beetsFields = $fields;
        pclose($handle);
    }
    
    
    public function queryBeets($action, $argument = '', $rawAnswer = false) {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            $lines = array();
            $handle = popen($this->beetsCommand . ' ' . $action . ' ' . $argument, 'r');
            while(!feof($handle)) { 
                $lines[] = $this->parseResponse(fgets($handle));
            }
            pclose($handle);
            return $lines;
        }
    }
    
    public function parseResponse($response) {
        $values = explode('###', $response);
        return array_combine($this->beetsFields, $values);
    }
}