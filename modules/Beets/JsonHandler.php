<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Beets;

/**
 * Parse Json while loading and create the songs
 *
 * @author raziel
 */
class JsonHandler extends Handler
{
    protected $uri;

    /**
     *
     * @var Catalog
     */
    protected $handler;

    /**
     * string handler command to do whatever we need
     * @var
     */
    protected $handlerCommand;

    /**
     * Seperator between command and arguments
     * @var string
     */
    protected $commandSeperator = '/';

    /**
     * Defines the differences between beets and ampache fields
     * @var array Defines the differences between beets and ampache fields
     */
    protected $fieldMapping = array(
        'disc' => array('disk', '%d'),
        'length' => array('time', '%d'),
        'comments' => array('comment', '%s'),
        'bitrate' => array('bitrate', '%d')
    );

    /**
     * JsonHandler constructor.
     * @param $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Starts a command
     * @param string $command
     */
    public function start($command)
    {
        $handle = fopen($this->assembleUri($command), 'r');
        if ($handle) {
            $this->iterateItems($handle);
        }
    }

    /**
     * Iterate over the input and create a song if one is found
     * @param resource $handle
     */
    public function iterateItems($handle)
    {
        $item = '';
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

    /**
     * Assemble the URI from the different parts
     * @param string $command
     * @return string
     */
    protected function assembleUri($command)
    {
        $uriParts = array(
            $this->uri,
            $command
        );

        return implode('/', $uriParts);
    }

    /**
     * Check if the Json is complete to get a song
     * @param string $item
     * @return boolean
     */
    public function itemIsComlete($item)
    {
        $item = $this->removeUnwantedStrings($item);

        return $this->compareBraces($item);
    }

    /**
     * Remove the beginning and the end of the json string so we can access the object in it.
     * @param string $item
     * @return string
     */
    public function removeUnwantedStrings($item)
    {
        $toRemove = array(
            '{"items":[',
            '{"results":[',
            ']}'
        );

        return str_replace($toRemove, '', $item);
    }

    /**
     * Compare the braces to ensure that we have a complete song object
     * @param string $item
     * @return boolean
     */
    public function compareBraces($item)
    {
        $start = $this->countChar('{', $item);
        $end   = $this->countChar('}', $item);

        return $start !== 0 && $start === $end;
    }

    /**
     *
     * @param string $char
     * @param string $string
     * @return integer
     */
    public function countChar($char, $string)
    {
        return substr_count($string, $char);
    }

    /**
     * convert the json string into a song array
     * @param string $item
     * @return array
     */
    public function parse($item)
    {
        $item         = $this->removeUnwantedStrings($item);
        $song         = json_decode($item, true);
        $song['file'] = $this->createFileUrl($song);

        return $this->mapFields($song);
    }

    /**
     * Create the Url to access the file
     * Have to do some magic with the file ending so ampache can detect the type
     * @param array $song
     * @return string
     */
    public function createFileUrl($song)
    {
        $parts = array(
            $this->uri,
            'item',
            $song['id'],
            'file' . '#.' . strtolower($song['format'])
        );

        return implode('/', $parts);
    }
}
