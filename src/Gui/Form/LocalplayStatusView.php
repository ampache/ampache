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

namespace Ampache\Gui\Form;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Override;

/**
 * The localplay control panel and its queue.
 */
final class LocalplayStatusView extends AbstractView
{
    /**
     * @param array<int, array{
     *     id: int,
     *     raw: string,
     *     vlid?: int,
     *     oid?: int,
     *     name?: string|null,
     *     link?: string|null,
     *     track: int,
     * }> $objects
     */
    public function __construct(
        private readonly LocalPlay $localplay,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly array $objects,
    ) {}

    public function createBrowse(): \Ampache\Module\Database\Query\Browse
    {
        $browse = $this->browseFactory->create();
        $browse->set_type('playlist_localplay');
        $browse->set_use_filters(false);
        $browse->set_static_content(true);

        return $browse;
    }

    public function getLocalplay(): LocalPlay
    {
        return $this->localplay;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     raw: string,
     *     vlid?: int,
     *     oid?: int,
     *     name?: string|null,
     *     link?: string|null,
     *     track: int,
     * }>
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('form/localplay_status.phtml');
    }
}
