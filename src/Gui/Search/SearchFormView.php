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

namespace Ampache\Gui\Search;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\System\Core;
use Ampache\Repository\VideoRepositoryInterface;
use Override;

/**
 * The advanced search form.
 *
 * Its result-limit options were eight hand-written lines and had drifted: the "10" option tested the
 * variable belonging to "50", so picking 10 never stuck and picking 50 selected both.
 */
final class SearchFormView extends AbstractView
{
    private const array LIMITS = [1, 5, 10, 25, 50, 100, 250, 500];

    public function __construct(
        private readonly ?Browse $browse,
        private readonly ?Search $playlist,
        private readonly ?string $searchType,
        private readonly VideoRepositoryInterface $videoRepository,
        private readonly string $webPath,
        private readonly bool $mayUse,
    ) {}

    public function getBrowseId(): int
    {
        return ($this->browse instanceof Browse) ? (int) $this->browse->id : 0;
    }

    /**
     * The category tabs, in the order they are offered.
     *
     * @return list<array{type: string, label: string, current: bool}>
     */
    public function getCategories(): array
    {
        $current    = $this->getCurrentType();
        $albumType  = (AmpConfig::get('album_group')) ? 'album' : 'album_disk';
        $categories = [
            ['type' => 'song', 'label' => T_('Songs'), 'current' => $current === 'song'],
            ['type' => $albumType, 'label' => T_('Albums'), 'current' => in_array($current, ['album', 'album_disk'], true)],
            ['type' => 'artist', 'label' => T_('Artists'), 'current' => $current === 'artist'],
        ];

        if (AmpConfig::get('label')) {
            $categories[] = ['type' => 'label', 'label' => T_('Labels'), 'current' => $current === 'label'];
        }

        $categories[] = ['type' => 'playlist', 'label' => T_('Playlists'), 'current' => $current === 'playlist'];

        if (AmpConfig::get('podcast')) {
            $categories[] = ['type' => 'podcast', 'label' => T_('Podcasts'), 'current' => $current === 'podcast'];
            $categories[] = ['type' => 'podcast_episode', 'label' => T_('Podcast Episodes'), 'current' => $current === 'podcast_episode'];
        }

        if (AmpConfig::get('allow_video') && $this->videoRepository->getItemCount()) {
            $categories[] = ['type' => 'video', 'label' => T_('Videos'), 'current' => $current === 'video'];
        }

        return $categories;
    }

    public function getCurrentType(): ?string
    {
        $type = $this->searchType ?? Core::get_request('type');

        return (in_array($type, Search::VALID_TYPES, true)) ? $type : null;
    }

    /**
     * A post is a submitted search; anything else arrived as a link.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = (empty($_POST)) ? $_REQUEST : $_POST;

        return array_merge(['type' => $this->getCurrentType()], $data);
    }

    /**
     * @return list<int>
     */
    public function getLimits(): array
    {
        return self::LIMITS;
    }

    public function getPermalink(): string
    {
        return $this->webPath . '/search.php?' . http_build_query($this->getData());
    }

    public function getPlaylist(): ?Search
    {
        return $this->playlist;
    }

    public function getSelectedLimit(): int
    {
        return (int) ($this->getData()['limit'] ?? 0);
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isRandom(): bool
    {
        return (int) ($this->getData()['random'] ?? 0) === 1;
    }

    public function showPermalink(): bool
    {
        return ($this->getData()['action'] ?? '') === 'search';
    }

    /**
     * Only a song search can be saved, because that is the only kind a smartlist can hold.
     */
    public function showSaveButtons(): bool
    {
        return $this->getCurrentType() === 'song' && $this->mayUse;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('search/form.phtml');
    }
}
