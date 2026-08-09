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

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The transport buttons shown in the rightbar when localplay is the play type.
 */
final class LocalplayControlView extends AbstractView
{
    /**
     * Each entry is a localplay command with the symbol, label and element id its button uses.
     *
     * @return list<array{command: string, symbol: string, label: string, id: string}>
     */
    public function getTransportButtons(): array
    {
        return [
            ['command' => 'prev', 'symbol' => 'skip_previous', 'label' => T_('Previous'), 'id' => 'localplay_control_previous'],
            ['command' => 'stop', 'symbol' => 'stop', 'label' => T_('Stop'), 'id' => 'localplay_control_stop'],
            ['command' => 'pause', 'symbol' => 'pause', 'label' => T_('Pause'), 'id' => 'localplay_control_pause'],
            ['command' => 'play', 'symbol' => 'play_arrow', 'label' => T_('Play'), 'id' => 'localplay_control_play'],
            ['command' => 'next', 'symbol' => 'skip_next', 'label' => T_('Next'), 'id' => 'localplay_control_next'],
        ];
    }

    /**
     * @return list<array{command: string, symbol: string, label: string, id: string}>
     */
    public function getVolumeButtons(): array
    {
        return [
            ['command' => 'volume_mute', 'symbol' => 'no_sound', 'label' => T_('Mute'), 'id' => 'localplay_mute'],
            ['command' => 'volume_down', 'symbol' => 'volume_down', 'label' => T_('Decrease Volume'), 'id' => 'localplay_volume_dn'],
            ['command' => 'volume_up', 'symbol' => 'volume_up', 'label' => T_('Increase Volume'), 'id' => 'localplay_volume_up'],
        ];
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('localplay_control.phtml');
    }
}
