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

namespace Ampache\Module\LiveStream;

use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\System\AmpError;
use Ampache\Repository\LiveStreamRepositoryInterface;

final class LiveStreamCreator implements LiveStreamCreatorInterface
{
    private LiveStreamRepositoryInterface $liveStreamRepository;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        LiveStreamRepositoryInterface $liveStreamRepository,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->liveStreamRepository = $liveStreamRepository;
        $this->catalogLoader        = $catalogLoader;
    }

    /**
     * This is a function that takes a key'd array for input
     * and if everything is good creates the object.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): bool
    {
        // Make sure we've got a name and codec
        if (!strlen((string)$data['name'])) {
            AmpError::add('name', T_('Name is required'));
        }
        if (!strlen((string)$data['codec'])) {
            AmpError::add('codec', T_('Codec is required (e.g. MP3, OGG...)'));
        }

        $allowed_array = array('https', 'http', 'mms', 'mmsh', 'mmsu', 'mmst', 'rtsp', 'rtmp');

        $elements = explode(":", (string)$data['url']);

        if (!in_array($elements['0'], $allowed_array)) {
            AmpError::add('url', T_('URL is invalid, must be http:// or https://'));
        }

        if (!empty($data['site_url'])) {
            $elements = explode(":", (string)$data['site_url']);
            if (!in_array($elements['0'], $allowed_array)) {
                AmpError::add('site_url', T_('URL is invalid, must be http:// or https://'));
            }
        }

        if (AmpError::occurred()) {
            return false;
        }

        // Make sure it's a real catalog
        try {
            $catalog = $this->catalogLoader->byId($data['catalog']);
        } catch (CatalogNotFoundException $e) {
            AmpError::add('catalog', T_('Catalog is invalid'));

            return false;
        }

        return (bool) $this->liveStreamRepository->create(
            $data['name'],
            $data['site_url'],
            $data['url'],
            $catalog->getId(),
            strtolower((string)$data['codec'])
        );
    }
}
