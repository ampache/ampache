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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Playback\Democratic;
use Override;

/**
 * The democratic playlist page header.
 *
 * It is rendered twice: once for a playlist that does not exist yet, with no browse to clear, and once
 * for the real one. The browse is optional here rather than assumed absent.
 */
final class DemocraticPageView extends AbstractView
{
    public function __construct(
        private readonly Democratic $democratic,
        private readonly ?Browse $browse,
        private readonly string $webPath,
        private readonly int $refreshLimit,
        private readonly bool $reloadPage,
        private readonly int $timestamp,
    ) {}

    public function getBoxTitle(): string
    {
        /* HINT: Democratic Playlist Name */
        return ($this->isEnabled())
            ? sprintf(T_('%s Playlist'), scrub_out($this->democratic->name))
            : T_('Democratic Playlist');
    }

    public function getClearButton(): string
    {
        return Ajax::button_with_text(
            '?page=democratic&action=clear_playlist&democratic_id=' . $this->democratic->getId() . '&browse_id=' . (($this->browse instanceof Browse) ? $this->browse->getId() : 0),
            'close',
            T_('Clear Playlist'),
            'clear_democratic'
        );
    }

    public function getConfigureUrl(): string
    {
        return $this->webPath . '/democratic.php?action=manage';
    }

    public function getCooldown(): string
    {
        return $this->democratic->cooldown . ' ' . T_('minutes');
    }

    public function getPlayButton(): string
    {
        return Ajax::button_with_text('?page=democratic&action=send_playlist&democratic_id=' . $this->democratic->getId(), 'play_circle', T_('Play'), 'play_democratic');
    }

    public function getRefreshMilliseconds(): int
    {
        return $this->refreshLimit * 1000;
    }

    /**
     * The reload appends a changing dummy value so the browser cannot serve the page from cache.
     */
    public function getReloadSuffix(): string
    {
        return ($this->reloadPage) ? "'&dummy=" . $this->timestamp . "'" : "'&dummy=" . $this->timestamp . "' + '&reloadpage=1'";
    }

    /**
     * The play and clear controls only exist once the playlist is running, and clearing needs a browse.
     */
    public function isEnabled(): bool
    {
        return $this->democratic->is_enabled();
    }

    public function isReloadPage(): bool
    {
        return $this->reloadPage;
    }

    public function mayManage(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER);
    }

    public function showClear(): bool
    {
        return $this->isEnabled() && $this->browse instanceof Browse;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('democratic_page.phtml');
    }
}
