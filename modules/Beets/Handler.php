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
 * Description of handler
 *
 * @author raziel
 */
abstract class Handler
{
    /**
     * Seperator between command and arguments
     * @var string
     */
    protected $commandSeperator;

    /**
     * @param $command
     * @return mixed
     */
    abstract public function start($command);

    /**
     * @param Catalog $handler
     * @param $command
     */
    public function setHandler(Catalog $handler, $command)
    {
        $this->handler        = $handler;
        $this->handlerCommand = $command;
    }

    /**
     * Call function from the dispatcher e.g. to store the new song
     * @param mixed $data
     * @return mixed
     */
    protected function dispatch($data)
    {
        return call_user_func(array($this->handler, $this->handlerCommand), $data);
    }

    /**
     * Resolves the differences between Beets and Ampache properties
     * @param array $song
     * @return array
     */
    protected function mapFields($song)
    {
        foreach ($this->fieldMapping as $from => $to) {
            list($key, $format) = $to;
            $song[$key]         = sprintf($format, $song[$from]);
        }
        $song['genre'] = preg_split('/[\s]?[,|;][\s?]/', $song['genre']);

        return $song;
    }

    /**
     * Get a command to get songs with a timestamp in $tag newer than $time.
     * For example: 'ls added:2014-10-02..'
     * @param string $command
     * @param string $tag
     * @param integer $time
     * @return string
     */
    public function getTimedCommand($command, $tag, $time)
    {
        $commandParts = array(
            $command
        );
        if ($time) {
            $commandParts[] = $tag . ':' . date('Y-m-d', $time) . '..';
        } else {
            // Add an empty part so we get a trailing slash if needed
            $commandParts[] = '';
        }

        return implode($this->commandSeperator, $commandParts);
    }
}
