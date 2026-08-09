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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\System\Preference;
use Override;

/**
 * The play-type picker in the header.
 *
 * It reached its selected option through a variable-variable pair of locals.
 */
final class PlayTypeSwitchView extends AbstractView
{
    public function getCurrent(): string
    {
        return (string) AmpConfig::get('play_type');
    }

    public function getCurrentLabel(): string
    {
        return T_(ucwords($this->getCurrent()));
    }

    /**
     * The web player is always offered; the rest depend on what the install allows.
     *
     * @return list<array{value: string, label: string}>
     */
    public function getOptions(): array
    {
        $options = [];
        if (AmpConfig::get('allow_stream_playback')) {
            $options[] = ['value' => 'stream', 'label' => T_('Stream')];
        }

        if (AmpConfig::get('allow_localplay_playback')) {
            $options[] = ['value' => 'localplay', 'label' => T_('Localplay')];
        }

        if (AmpConfig::get('allow_democratic_playback')) {
            $options[] = ['value' => 'democratic', 'label' => T_('Democratic')];
        }

        $options[] = ['value' => 'web_player', 'label' => T_('Web Player')];

        return $options;
    }

    public function mayChange(): bool
    {
        return Preference::has_access('play_type');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/play_type_switch.phtml');
    }
}
