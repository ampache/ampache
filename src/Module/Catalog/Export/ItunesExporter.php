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
use Ampache\Repository\Model\Song;
use Ampache\Repository\SongRepositoryInterface;

final class ItunesExporter implements CatalogExporterInterface
{
    private SongRepositoryInterface $songRepository;

    public function __construct(
        SongRepositoryInterface $songRepository
    ) {
        $this->songRepository = $songRepository;
    }

    /**
     * Exports all songs
     *
     * This echoes the build output directly to the browser
     */
    public function export(?Catalog $catalog): void
    {
        $result = $this->songRepository->getByCatalog($catalog);

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
            "<!DOCTYPE plist PUBLIC \"-//Apple Computer//DTD PLIST 1.0//EN\"\n" .
            "\"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n" .
            "<plist version=\"1.0\">\n" .
            "<dict>\n" .
            "       <key>Major Version</key><integer>1</integer>\n" .
            "       <key>Minor Version</key><integer>1</integer>\n" .
            "       <key>Application Version</key><string>7.0.2</string>\n" .
            "       <key>Features</key><integer>1</integer>\n" .
            "       <key>Show Content Ratings</key><true/>\n" .
            "       <key>Tracks</key>\n" .
            "       <dict>\n";

        foreach ($result as $songId) {
            $song = new Song($songId);
            $song->format();

            $xml                         = [];
            $xml['key']                  = $songId;
            $xml['dict']['Track ID']     = $songId;
            $xml['dict']['Name']         = $song->title;
            $xml['dict']['Artist']       = $song->get_artist_fullname();
            $xml['dict']['Album']        = $song->get_album_fullname();
            $xml['dict']['Total Time']   = $song->time * 1000; // iTunes uses milliseconds
            $xml['dict']['Track Number'] = (int) $song->track;
            $xml['dict']['Year']         = $song->year;
            $xml['dict']['Date Added']   = get_datetime(
                $song->addition_time,
                'short',
                'short',
                "Y-m-d\TH:i:s\Z"
            );
            $xml['dict']['Bit Rate']    = (int)($song->bitrate / 1024);
            $xml['dict']['Sample Rate'] = $song->rate;
            $xml['dict']['Play Count']  = (int)($song->played);
            $xml['dict']['Track Type']  = 'URL';
            $xml['dict']['Location']    = $song->play_url();

            echo xoutput_from_array($xml, true, 'itunes');
        }
        echo "      </dict>\n" .
            "</dict>\n" .
            "</plist>\n";
    }

    /**
     * Sends the necessary http headers
     */
    public function sendHeaders(): void
    {
        $date = get_datetime(time(), 'short', 'none', 'y-MM-dd');

        header('Content-Transfer-Encoding: binary');
        header('Cache-control: public');
        header('Content-Type: application/itunes+xml; charset=utf-8');
        header(sprintf('Content-Disposition: attachment; filename="ampache-itunes-%s.xml"', $date));
    }
}
