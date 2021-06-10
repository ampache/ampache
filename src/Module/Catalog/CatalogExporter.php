<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
 */

declare(strict_types=0);

namespace Ampache\Module\Catalog;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class CatalogExporter implements CatalogExporterInterface
{
    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->modelFactory  = $modelFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * it exports all songs in the database to the given export type.
     */
    public function export(string $type, ?int $catalog_id = null): StreamInterface
    {
        // Select all songs in catalog
        $params = array();
        if ($catalog_id) {
            $sql      = 'SELECT `id` FROM `song` ' . "WHERE `catalog`= ? " . 'ORDER BY `album`, `track`';
            $params[] = $catalog_id;
        } else {
            $sql = 'SELECT `id` FROM `song` ORDER BY `album`, `track`';
        }
        $db_results = Dba::read($sql, $params);

        $result = '';

        switch ($type) {
            case 'itunes':
                $result .= $this->xml_get_header('itunes');
                while ($results = Dba::fetch_assoc($db_results)) {
                    $song = $this->modelFactory->createSong((int) $results['id']);
                    $song->format();

                    $xml                         = array();
                    $xml['key']                  = $results['id'];
                    $xml['dict']['Track ID']     = (int)($results['id']);
                    $xml['dict']['Name']         = $song->title;
                    $xml['dict']['Artist']       = $song->getFullArtistNameFormatted();
                    $xml['dict']['Album']        = $song->f_album_full;
                    $xml['dict']['Total Time']   = (int) ($song->time) * 1000; // iTunes uses milliseconds
                    $xml['dict']['Track Number'] = (int) ($song->track);
                    $xml['dict']['Year']         = (int) ($song->year);
                    $xml['dict']['Date Added']   = get_datetime((int) $song->addition_time, 'short', 'short', "Y-m-d\TH:i:s\Z");
                    $xml['dict']['Bit Rate']     = (int) ($song->bitrate / 1000);
                    $xml['dict']['Sample Rate']  = (int) ($song->rate);
                    $xml['dict']['Play Count']   = (int) ($song->played);
                    $xml['dict']['Track Type']   = "URL";
                    $xml['dict']['Location']     = $song->play_url();
                    $result .= xoutput_from_array($xml, true, 'itunes');
                    // flush output buffer
                } // while result
                $result .= $this->xml_get_footer('itunes');
                break;
            case 'csv':
                $result .= "ID,Title,Artist,Album,Length,Track,Year,Date Added,Bitrate,Played,File\n";
                while ($results = Dba::fetch_assoc($db_results)) {
                    $song = new Song($results['id']);
                    $song->format();
                    $result .= '"' . $song->id . '","' . $song->title . '","' . $song->getFullArtistNameFormatted() . '","' . $song->f_album_full . '","' . $song->getDurationFormatted() . '","' . $song->f_track . '","' . $song->year . '","' . get_datetime((int)$song->addition_time) . '","' . $song->f_bitrate . '","' . $song->played . '","' . $song->getFile() . '"' . "\n";
                }
                break;
        } // end switch

        return $this->streamFactory->createStream($result);
    }

    /**
     * This takes the type and returns the correct xml header
     */
    private function xml_get_header(string $type): string
    {
        switch ($type) {
            case 'itunes':
                return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
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
            case 'xspf':
                return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" .
                    "<!-- XML Generated by Ampache v." . AmpConfig::get('version') . " -->";
            default:
                return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        }
    }

    /**
     * This takes the type and returns the correct xml footer
     */
    private function xml_get_footer(string $type): string
    {
        switch ($type) {
            case 'itunes':
                return "      </dict>\n" .
                    "</dict>\n" .
                    "</plist>\n";
            case 'xspf':
                return "      </trackList>\n" .
                    "</playlist>\n";
            default:
                return '';
        }
    }
}
