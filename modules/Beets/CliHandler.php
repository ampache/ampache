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
 * Start commands in CLI and dispatch them
 *
 * @author raziel
 */
class CliHandler extends Handler
{
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
     * Field seperator for beets field format
     * @var string
     */
    protected $seperator = '###';

    /**
     * Custom limiter of beets song because we may have multi line output
     * @var string
     */
    protected $itemEnd = '//EOS';

    /**
     * Format string for the '-f' argument from 'beet ls'
     * @var string
     */
    protected $fieldFormat;

    /**
     * Choose whether the -f argument from beets is applied. May be needed to use other commands than 'beet ls'
     * @var boolean
     */
    protected $useCustomFields = true;

    /**
     * All stored beets fields
     * @var array
     */
    protected $fields = array();

    /**
     * Beets command
     * @var string
     */
    protected $beetsCommand = 'beet';

    /**
     * Seperator between command and arguments
     * @var string
     */
    protected $commandSeperator = ' ';

    /**
     * Defines the differences between beets and ampache fields
     * @var array Defines the differences between beets and ampache fields
     */
    protected $fieldMapping = array(
        'disc' => array('disk', '%d'),
        'path' => array('file', '%s'),
        'length' => array('time', '%d'),
        'comments' => array('comment', '%s'),
        'bitrate' => array('bitrate', '%d')
    );

    /**
     * Starts a command
     * @param string $command
     */
    public function start($command)
    {
        $handle = popen($this->assembleCommand($command), 'r');
        if ($handle) {
            $this->iterateItems($handle);
        }
    }

    /**
     * @param $handle
     */
    public function iterateItems($handle)
    {
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
     * @return string
     */
    protected function assembleCommand($command, $disableCostomFields = false)
    {
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
    protected function itemIsComlete($item)
    {
        return strrpos($item, $this->itemEnd, strlen($this->itemEnd)) !== false;
    }

    /**
     * Parse the output string from beets into a song
     * @param string $item
     * @return array
     */
    protected function parse($item)
    {
        $item               = str_replace($this->itemEnd, '', $item);
        $values             = explode($this->seperator, $item);
        $song               = array_combine($this->fields, $values);
        $mappedSong         = $this->mapFields($song);
        $mappedSong['size'] = filesize($mappedSong['file']);

        return $mappedSong;
    }

    /**
     * Create the format string for beet ls -f
     * @return string
     */
    protected function getFieldFormat()
    {
        if (!isset($this->fieldFormat)) {
            $this->fields      = $this->getFields();
            $this->fieldFormat = '$' . implode($this->seperator . '$', $this->fields) . $this->itemEnd;
        }

        return $this->fieldFormat;
    }

    /**
     *
     * @return array
     */
    protected function getFields()
    {
        $fields          = null;
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
}
