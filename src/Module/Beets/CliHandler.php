<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\System\Core;

/**
 * Start commands in CLI and dispatch them
 *
 * @author raziel
 */
class CliHandler extends Handler
{
    protected Catalog $handler;

    /**
     * Field separator for beets field format
     */
    protected string $seperator = '###';

    /**
     * Custom limiter of beets song because we may have multi line output
     */
    protected string $itemEnd = '//EOS';

    /**
     * Format string for the '-f' argument from 'beet ls'
     */
    protected string $fieldFormat = '$';

    /**
     * Choose whether the -f argument from beets is applied. May be needed to use other commands than 'beet ls'
     */
    protected bool $useCustomFields = true;

    /**
     * All stored beets fields
     */
    protected array $fields = [];

    /**
     * Beets command
     */
    protected string $beetsCommand = 'beet';

    /**
     * Seperator between command and arguments
     */
    protected string $commandSeperator = ' ';

    /**
     * Defines the differences between beets and ampache fields
     */
    protected array $fieldMapping = [
        'disc' => ['disk', '%d'],
        'path' => ['file', '%s'],
        'length' => ['time', '%d'],
        'comments' => ['comment', '%s'],
        'bitrate' => ['bitrate', '%d']
    ];

    /**
     * CliHandler constructor.
     */
    public function __construct(Catalog $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Starts a command
     */
    public function start(string $command): void
    {
        $handle = popen($this->assembleCommand($command), 'r');
        if ($handle) {
            $this->iterateItems($handle);
        }
    }

    /**
     * @param resource $handle
     */
    public function iterateItems($handle): void
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
     * @param bool $disableCostomFields disables the -f switch for this time
     */
    protected function assembleCommand(string $command, bool $disableCostomFields = false): string
    {
        $commandParts = [
            escapeshellcmd($this->beetsCommand),
            ' -l ' . escapeshellarg($this->handler->get_path()),
            escapeshellcmd($command),
        ];
        if ($this->useCustomFields && !$disableCostomFields) {
            $commandParts[] = ' -f ' . escapeshellarg($this->getFieldFormat());
        }

        return implode(' ', $commandParts);
    }

    /**
     * itemIsComlete
     */
    protected function itemIsComlete(string $item): bool
    {
        $offset   = strlen($this->itemEnd);
        $position = (strlen($item) > $offset)
            ? strpos($item, $this->itemEnd, $offset)
            : false;

        return ($position !== false);
    }

    /**
     * Parse the output string from beets into a song
     * @param string $item
     * @return array
     */
    protected function parse(string $item): array
    {
        $item               = str_replace($this->itemEnd, '', $item);
        $values             = explode($this->seperator, $item);
        $song               = array_combine($this->fields, $values);
        $mappedSong         = $this->mapFields($song);
        $mappedSong['size'] = Core::get_filesize($mappedSong['file']);

        return $mappedSong;
    }

    /**
     * Create the format string for beet ls -f
     */
    protected function getFieldFormat(): string
    {
        if (!empty($this->fieldFormat)) {
            $this->fields      = $this->getFields();
            $this->fieldFormat = '$' . implode($this->seperator . '$', $this->fields) . $this->itemEnd;
        }

        return $this->fieldFormat;
    }

    /**
     * getFields
     * @return string[]
     */
    protected function getFields(): array
    {
        $fields          = null;
        $processedFields = [];
        exec($this->assembleCommand('fields', true), $fields);
        foreach ((array) $fields as $field) {
            $matches = [];
            if (preg_match('/^[\s]+([\w]+)$/', $field, $matches)) {
                $processedFields[] = $matches[1];
            }
        }

        return $processedFields;
    }
}
