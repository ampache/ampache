<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Beets;

use Override;

/**
 * Parse Json while loading and create the songs
 *
 * @author raziel
 */
class JsonHandler extends Handler
{
    /**
     * Seperator between command and arguments
     */
    #[Override]
    protected string $commandSeperator = '/';

    /**
     * Defines the differences between beets and ampache fields
     */
    #[Override]
    protected array $fieldMapping = [
        'disc' => ['disk', '%d'],
        'length' => ['time', '%d'],
        'comments' => ['comment', '%s'],
        'bitrate' => ['bitrate', '%d']
    ];

    protected Catalog $handler;

    /**
     * JsonHandler constructor.
     */
    public function __construct(protected string $uri) {}

    /**
     * Compare the braces to ensure that we have a complete song object
     */
    public function compareBraces(string $item): bool
    {
        $start = $this->countChar('{', $item);
        $end   = $this->countChar('}', $item);

        return $start !== 0 && $start === $end;
    }

    /**
     * countChar
     */
    public function countChar(string $char, string $string): int
    {
        return substr_count($string, $char);
    }

    /**
     * Create the Url to access the file
     * Have to do some magic with the file ending so ampache can detect the type
     */
    public function createFileUrl(array $song): string
    {
        $parts = [
            $this->uri,
            'item',
            $song['id'],
            'file#.' . strtolower((string) $song['format']),
        ];

        return implode('/', $parts);
    }

    /**
     * Check if the Json is complete to get a song
     */
    public function itemIsComlete(string $item): bool
    {
        $item = $this->removeUnwantedStrings($item);

        return $this->compareBraces($item);
    }

    /**
     * Iterate over the input and create a song if one is found
     * @param resource $handle
     */
    public function iterateItems($handle): void
    {
        $item = '';
        while (!feof($handle)) {
            $item .= $char = fgetc($handle);
            // Check for the brace prevents unneeded call of itemIsComplete() which saves a whole lot of time
            if ($char === '}' && $this->itemIsComlete($item)) {
                $song = $this->parse($item);
                $this->dispatch($song);
                $item = '';
                fgetc($handle); // Skip comma between two objects
            }
        }
    }

    /**
     * convert the json string into a song array
     */
    public function parse(string $item): array
    {
        $item         = $this->removeUnwantedStrings($item);
        $song         = json_decode($item, true);
        $song['file'] = $this->createFileUrl($song);

        return $this->mapFields($song);
    }

    /**
     * Remove the beginning and the end of the json string so we can access the object in it.
     */
    public function removeUnwantedStrings(string $item): string
    {
        $toRemove = [
            '{"items":[',
            '{"results":[',
            ']}',
        ];

        return str_replace($toRemove, '', $item);
    }

    /**
     * Starts a command
     */
    public function start(string $command): void
    {
        $handle = fopen($this->assembleUri($command), 'r');
        if ($handle) {
            $this->iterateItems($handle);
        }
    }

    /**
     * Assemble the URI from the different parts
     */
    protected function assembleUri(string $command): string
    {
        $uriParts = [
            $this->uri,
            $command,
        ];

        return implode('/', $uriParts);
    }
}
