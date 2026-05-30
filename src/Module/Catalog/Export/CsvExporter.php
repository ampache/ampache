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

namespace Ampache\Module\Catalog\Export;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final readonly class CsvExporter implements CatalogExporterInterface
{
    public function __construct(
        private SongRepositoryInterface $songRepository,
        private ModelFactoryInterface $modelFactory,
        private string $filePointer = 'php://output',
    ) {
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
            ],
            escape: '\\'
        );
        foreach ($result as $songId) {
            $song = $this->modelFactory->createSong((int)$songId);

            fputcsv(
                $stream,
                [
                    $song->getId(),
                    $song->title,
                    $song->get_artist_fullname(),
                    $song->get_album_fullname(),
                    $song->get_f_time(),
                    (string)$song->track,
                    $song->year,
                    get_datetime($song->getAdditionTime()),
                    (int)($song->bitrate / 1024) . "-" . strtoupper((string)$song->mode),
                    $song->played,
                    $song->file
                ],
                escape: '\\'
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
        header(sprintf('Content-Disposition: attachment; filename="ampache-export-%s.csv"', $date));
    }
}
