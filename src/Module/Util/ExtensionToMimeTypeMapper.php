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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Util;

/**
 * FIXME: This should really be done the other way around.
 * Store the mime type in the database, and provide a function
 * to make it a human-friendly type.
 */
final class ExtensionToMimeTypeMapper implements ExtensionToMimeTypeMapperInterface
{
    /**
     * Returns the mime type for the specified audio file extension/type
     */
    public function mapAudio(string $extension): string
    {
        switch ($extension) {
            case 'spx':
            case 'ogg':
                return 'application/ogg';
            case 'opus':
                return 'audio/ogg; codecs=opus';
            case 'wma':
            case 'asf':
                return 'audio/x-ms-wma';
            case 'rm':
            case 'ra':
                return 'audio/x-realaudio';
            case 'flac':
                return 'audio/x-flac';
            case 'wv':
                return 'audio/x-wavpack';
            case 'aac':
            case 'mp4':
            case 'm4a':
                return 'audio/mp4';
            case 'aacp':
                return 'audio/aacp';
            case 'mpc':
                return 'audio/x-musepack';
            case 'mkv':
                return 'audio/x-matroska';
            case 'mpeg3':
            case 'mp3':
            default:
                return 'audio/mpeg';
        }
    }

    /**
     * Returns the mime type for the specified video file extension/type
     */
    public function mapVideo(string $extension): string
    {
        switch ($extension) {
            case 'avi':
                return 'video/avi';
            case 'ogg':
            case 'ogv':
                return 'application/ogg';
            case 'wmv':
                return 'audio/x-ms-wmv';
            case 'mp4':
            case 'm4v':
                return 'video/mp4';
            case 'mkv':
                return 'video/x-matroska';
            case 'mov':
                return 'video/quicktime';
            case 'divx':
                return 'video/x-divx';
            case 'webm':
                return 'video/webm';
            case 'flv':
                return 'video/x-flv';
            case 'ts':
                return 'video/mp2t';
            case 'mpg':
            case 'mpeg':
            case 'm2ts':
            default:
                return 'video/mpeg';
        }
    }
}
