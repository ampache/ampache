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

namespace Beets;

/**
 * Start commands in CLI and dispatch them
 *
 * @author raziel
 */
class CliHandler {

    /**
     *
     * @var \Catalog_beets
     */
    private $handler;

    /**
     * string handler command to do whatever we need
     * @var
     */
    private $handlerCommand;

    /**
     * Field seperator for beets field format
     * @var string
     */
    private $seperator = '###';

    /**
     * Custom limiter of beets song because we may have multi line output
     * @var string
     */
    private $itemEnd = '//EOS';

    /**
     * Format string for the '-f' argument from 'beet ls'
     * @var string
     */
    private $fieldFormat;

    /**
     * Choose whether the -f argument from beets is applied. May be needed to use other commands than 'beet ls'
     * @var boolean
     */
    private $useCustomFields = true;

    /**
     * All stored beets fields
     * @var array
     */
    private $fields = array();

    /**
     * Beets command
     * @var string
     */
    private $beetsCommand = '/usr/bin/beet';

    /**
     * Defines the differences between beets and ampache fields
     * @var array Defines the differences between beets and ampache fields
     */
    private $fieldMapping = array(
        'disc' => array('disk', '%d'),
        'path' => array('file', '%s'),
        'length' => array('time', '%d'),
        'comments' => array('comment', '%s'),
        'bitrate' => array('bitrate', '%d')
    );

    public function setHandler(\Catalog_beets $handler, $command) {
        $this->handler = $handler;
        $this->handlerCommand = $command;
    }

    public function start($command) {
        $handle = popen($this->assembleCommand($command), 'r');
        $item = '';
        while (!feof($handle)) {
            $item .= fgets($handle);
            if ($this->itemIsComlete($item)) {
                $song = $this->parse($item);
                $this->dispatch($song);
                $item = '';
            }
        }
    }

    /**
     * Assemble the command for CLI
     * @param string $command beets command (e.g. 'ls myArtist')
     * @param boolean $disableCostomFields disables the -f switch for this time
     * @return type
     */
    protected function assembleCommand($command, $disableCostomFields = false) {
        $commandParts = array(
            escapeshellcmd($this->beetsCommand),
            ' -l ' . escapeshellarg($this->handler->getBeetsDb()),
            escapeshellcmd($command)
        );
        if ($this->useCustomFields && !$disableCostomFields) {
            $commandParts[] = ' -f ' . escapeshellarg($this->getFieldFormat());
        }
        return implode(' ', $commandParts);
    }

    /**
     * 
     * @param string $item
     * @return boolean
     */
    protected function itemIsComlete($item) {
        return strrpos($item, $this->itemEnd, strlen($this->itemEnd)) !== false;
    }

    /**
     * Parse the output string from beets into a song
     * @param string $item
     * @return array
     */
    protected function parse($item) {
        $item = str_replace($this->itemEnd, '', $item);
        $values = explode($this->seperator, $item);
        $song = array_combine($this->fields, $values);
        return $this->mapFields($song);
    }

    /**
     * Call function from the dispatcher e.g. to store the new song
     * @param mixed $data
     * @return mixed
     */
    protected function dispatch($data) {
        return call_user_func(array($this->handler, $this->handlerCommand), $data);
    }

    /**
     * Create the format string for beet ls -f
     * @return string
     */
    protected function getFieldFormat() {
        if (!isset($this->fieldFormat)) {
            $this->fields = $this->getFields();
            $this->fieldFormat = '$' . implode($this->seperator . '$', $this->fields) . $this->itemEnd;
        }
        return $this->fieldFormat;
    }

    /**
     * 
     * @return array
     */
    protected function getFields() {
        $fields = null;
        $processedFields = array();
        exec($this->assembleCommand('fields', true), $fields);
        foreach ((array) $fields as $field) {
            $matches = array();
            if (preg_match('/^[\s]+([\w]+)$/', $field, $matches)) {
                $processedFields[] = $matches[1];
            }
        }

        return $processedFields;
    }

    /**
     * Resolves the differences between Beets and Ampache properties
     * @param type $song
     * @return type
     */
    protected function mapFields($song) {
        foreach ($this->fieldMapping as $from => $to) {
            list($key, $format) = $to;
            $song[$key] = sprintf($format, $song[$from]);
        }
        $song['genre'] = explode(',', $song['genre']);

        return $song;
    }

}
