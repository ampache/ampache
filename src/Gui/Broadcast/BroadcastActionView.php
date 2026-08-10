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

namespace Ampache\Gui\Broadcast;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The web player's broadcast control, in either of its two states.
 *
 * A running broadcast offers the button that stops it; with none running the icon opens the dialog
 * that starts one. Both the player template and the player ajax handler render it, which is why it
 * is a view rather than markup built where it is used.
 */
final class BroadcastActionView extends AbstractView
{
    public function __construct(
        private readonly ?int $broadcastId = null,
    ) {}

    /**
     * The running broadcast, or null when nothing is being broadcast.
     */
    public function getBroadcastId(): ?int
    {
        return $this->broadcastId;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('broadcast_action.phtml');
    }
}
