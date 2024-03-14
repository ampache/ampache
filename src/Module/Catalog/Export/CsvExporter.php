<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Catalog\Export;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class CsvExporter implements CatalogExporterInterface
{
    private SongRepositoryInterface $songRepository;

    private string $filePointer;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        SongRepositoryInterface $songRepository,
        ModelFactoryInterface $modelFactory,
        string $filePointer = 'php://output'
    ) {
        $this->songRepository = $songRepository;
        $this->modelFactory   = $modelFactory;
        $this->filePointer    = $filePointer;
    }

    /**
     * Exports all songs
     *
     * This echoes the build output directly to the browser
     */
    public function export(?Catalog $catalog): void
    {
        $result = $this->songRepository->getByCatalog($catalog);

        $stream = fopen($this->filePointer, 'w');

        if ($stream === false) {
            return;
        }

        fputcsv(
            $stream,
            [
                'ID',
                'Title',
                'Artist',
                'Album',
                'Length',
                'Track',
                'Year',
                'Date Added',
                'Bitrate',
                'Played',
                'File'
            ]
        );
        foreach ($result as $songId) {
            $song = $this->modelFactory->createSong((int)$songId);
            $song->format();

            fputcsv(
                $stream,
                [
                    $song->getId(),
                    $song->title,
                    $song->get_artist_fullname(),
                    $song->get_album_fullname(),
                    $song->f_time,
                    $song->f_track,
                    $song->year,
                    get_datetime($song->getAdditionTime()),
                    $song->f_bitrate,
                    $song->played,
                    $song->file
                ]
            );
        }
    }

    /**
     * Sends the necessary http headers
     */
    public function sendHeaders(): void
    {
        $date = get_datetime(time(), 'short', 'none', 'y-MM-dd');

        header('Content-Transfer-Encoding: binary');
        header('Cache-control: public');
        header('Content-Type: application/vnd.ms-excel');
        header(sprintf('Content-Disposition: filename="ampache-export-%s.csv"', $date));
    }
}
