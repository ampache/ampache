<?php

declare(strict_types=1);

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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song;

final class PrintDuplicatesCommand extends Command
{
    private ModelFactoryInterface $modelFactory;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        parent::__construct('print:duplicates', T_('Possible Duplicate'));

        $this->modelFactory = $modelFactory;

        $this
            ->option('-t|--type', T_('Object Type'), 'strval', 'album')
            ->usage('<bold>  print:duplicates</end> ## ' . T_('Possible Duplicate Albums') . '<eol/>');
    }

    public function execute(): void
    {
        $values     = $this->values();
        $interactor = $this->io();

        $type = $values['type'];
        if (!in_array(strtolower($type), ['album', 'album_disk', 'artist', 'album_artist', 'song', 'song_artist'])) {
            $interactor->error(
                "\n" . T_('Invalid Request') . ': ' . $type,
                true
            );

            return;
        }

        $user = $this->modelFactory->createUser(-1);
        $data = [
            'operator' => 'and',
            'rule_1' => 'possible_duplicate',
            'rule_1_operator' => 0,
            'rule_1_input' => '',
            'rule_2' => '',
            'rule_2_operator' => 0,
            'rule_2_input' => '',
            'type' => $type,
        ];

        $search_sql = Search::prepare($data, $user);
        $query      = Search::query($search_sql);

        // Use the properties of each type as column headers
        $printedHeader = false;

        foreach ($query['results'] as $duplicate) {
            $object = match ($type) {
                'album' => $this->modelFactory->createAlbum($duplicate),
                'album_disk' => $this->modelFactory->createAlbumDisk($duplicate),
                'artist', 'album_artist', 'song_artist' => $this->modelFactory->createArtist($duplicate),
                'song' => $this->modelFactory->createSong($duplicate),
                default => null,
            };

            if ($object === null) {
                continue;
            }

            $allowedKeys = match ($type) {
                'album', 'album_disk' => ['prefix', 'name', 'mbid', 'year', 'disk_count', 'mbid_group', 'release_type', 'album_artist', 'original_year', 'barcode', 'catalog_number', 'version', 'release_status'],
                'artist', ['prefix', 'name', 'mbid'],
                'song' => ['prefix', 'name', 'mbid', 'f_album_full', 'artist_full_name'],
                default => null,
            };

            if ($allowedKeys === null) {
                continue;
            }

            // songs are missing some data
            if ($object instanceof Song) {
                $object->get_album_fullname();
                $object->get_artist_fullname();
            }

            $props = get_object_vars($object);
            $props = array_intersect_key($props, array_flip($allowedKeys));

            if (!$printedHeader) {
                if ($type === 'song') {
                    print_r(implode("\t", ['prefix', 'name', 'mbid', 'album', 'song_artist']) . "\n");
                } else {
                    print_r(implode("\t", $allowedKeys) . "\n");
                }
                $printedHeader = true;
            }

            // Print each row in allowedKeys order
            $row = [];
            foreach ($allowedKeys as $key) {
                $value = $props[$key] ?? '';
                $row[] = is_scalar($value) ? $value : json_encode($value);
            }

            // print in a tsv format
            print_r(implode("\t", $row) . "\n");
        }

        print_r("\n" . T_('Done') . "\n");
    }
}
