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
 * Description of JsonHandler
 *
 * @author raziel
 */
class JsonHandler {

    private $uri;

    /**
     *
     * @var \Catalog_beetsremote
     */
    private $handler;

    /**
     * string handler command to do whatever we need
     * @var
     */
    private $handlerCommand;

    /**
     * Defines the differences between beets and ampache fields
     * @var array Defines the differences between beets and ampache fields
     */
    private $fieldMapping = array(
        'disc' => array('disk', '%d'),
        'length' => array('time', '%d'),
        'comments' => array('comment', '%s'),
        'bitrate' => array('bitrate', '%d')
    );

    public function __construct($uri) {
        $this->uri = $uri;
    }

    public function setHandler(\Catalog_beetsremote $handler, $command) {
        $this->handler = $handler;
        $this->handlerCommand = $command;
    }

    public function start($command) {
        $handle = fopen($this->assembleUri($command), 'r');
        if ($handle) {
            $this->iterateItems($handle);
        }
    }

    public function iterateItems($handle) {
        while (!feof($handle)) {
            $item .= $char = fgetc($handle);
            // Check for the brace prevents unneded call of itemIsComlete() which saves a whole lot of time
            if ($char === '}' && $this->itemIsComlete($item)) {
                $song = $this->parse($item);
                $this->dispatch($song);
                $item = '';
                fgetc($handle); // Skip comma between two objects
            }
        }
    }

    protected function assembleUri($command) {
        $uriParts = array(
            $this->uri,
            $command
        );

        return implode('/', $uriParts) . '/query/nightwish';
    }

    public function itemIsComlete($item) {
        $item = $this->removeUnwantedStrings($item);
        return $this->compareBraces($item);
    }

    public function removeUnwantedStrings($item) {
        $toRemove = array(
            '{"results":[',
            ']}'
        );
        return str_replace($toRemove, '', $item);
    }

    public function compareBraces($item) {
        $start = $this->countChar('{', $item);
        $end = $this->countChar('}', $item);
        return $start !== 0 && $start === $end;
    }

    public function countChar($char, $string) {
        return preg_match_all('/' . $char . '/', $string);
    }

    public function parse($item) {
        $item = $this->removeUnwantedStrings($item);
        $song = json_decode($item, true);
        $song['file'] = $this->createFileUrl($song);
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

    public function createFileUrl($song) {
        $parts = array(
            $this->uri,
            'item',
            $song['id'],
            'file',
            '#.' . strtolower($song['format'])
        );
        return implode('/', $parts);
    }

}
