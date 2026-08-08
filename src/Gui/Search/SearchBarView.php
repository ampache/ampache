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
use Ampache\Module\Util\EnvironmentInterface;
use Override;

/**
 * The quick search box in the header.
 */
final class SearchBarView extends AbstractView
{
    public function __construct(
        private readonly EnvironmentInterface $environment,
        private readonly string $webPath,
    ) {}

    public function getFormAction(): string
    {
        return $this->webPath . '/search.php';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getOptions(): array
    {
        $options = [
            ['value' => 'anywhere', 'label' => T_('Anywhere')],
            ['value' => 'title', 'label' => T_('Songs')],
            ['value' => (AmpConfig::get('album_group')) ? 'album' : 'album_disk', 'label' => T_('Albums')],
            ['value' => 'artist', 'label' => T_('Artists')],
            ['value' => 'playlist', 'label' => T_('Playlists')],
        ];

        if (AmpConfig::get('label')) {
            $options[] = ['value' => 'label', 'label' => T_('Labels')];
        }

        if (AmpConfig::get('wanted')) {
            $options[] = ['value' => 'missing_artist', 'label' => T_('Missing Artists')];
        }

        return $options;
    }

    /**
     * The mobile layout hides the button and submits from the field itself.
     */
    public function isMobile(): bool
    {
        return $this->environment->isMobile();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('search/bar.phtml');
    }
}
