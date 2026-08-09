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

use Ampache\Config\AmpConfig;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * Builds the category link-bars, so the actions that draw one do not each repeat the config reads.
 */
final readonly class StatsFormViewFactory implements StatsFormViewFactoryInterface
{
    public function __construct(
        private VideoRepositoryInterface $videoRepository,
        private FolderRepositoryInterface $folderRepository,
    ) {}

    #[Override]
    public function createBrowse(): BrowseFormView
    {
        return new BrowseFormView(
            AmpConfig::get_web_path(),
            $this->getFilter(),
            (bool) AmpConfig::get('show_artist'),
            (bool) AmpConfig::get('show_album_artist'),
            (bool) AmpConfig::get('album_group'),
            (bool) AmpConfig::get('podcast'),
            $this->isVideoEnabled(),
            $this->isFolderEnabled(),
            (bool) AmpConfig::get('label'),
            (bool) AmpConfig::get('broadcast'),
            (bool) AmpConfig::get('live_stream')
        );
    }

    #[Override]
    public function createHighest(bool $byUser = false): HighestFormView
    {
        return new HighestFormView(...$this->commonArguments($byUser));
    }

    #[Override]
    public function createNewest(bool $byUser = false): NewestFormView
    {
        return new NewestFormView(...$this->commonArguments($byUser));
    }

    #[Override]
    public function createPopular(bool $byUser = false): PopularFormView
    {
        return new PopularFormView(...$this->commonArguments($byUser));
    }

    #[Override]
    public function createRecent(bool $byUser = false): RecentFormView
    {
        return new RecentFormView(...$this->commonArguments($byUser));
    }

    #[Override]
    public function createUserflag(bool $byUser = false): UserflagFormView
    {
        return new UserflagFormView(...$this->commonArguments($byUser));
    }

    /**
     * @return array{string, string, bool, bool, bool, bool, bool, bool}
     */
    private function commonArguments(bool $byUser): array
    {
        return [
            AmpConfig::get_web_path(),
            $this->getFilter(),
            $byUser,
            (bool) AmpConfig::get('show_artist'),
            (bool) AmpConfig::get('show_album_artist'),
            (bool) AmpConfig::get('album_group'),
            (bool) AmpConfig::get('podcast'),
            $this->isVideoEnabled(),
        ];
    }

    private function getFilter(): string
    {
        return (string) filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    }

    private function isFolderEnabled(): bool
    {
        return (bool) AmpConfig::get('show_folder') && $this->folderRepository->getItemCount() > 0;
    }

    private function isVideoEnabled(): bool
    {
        return (bool) AmpConfig::get('allow_video') && $this->videoRepository->getItemCount() > 0;
    }
}
