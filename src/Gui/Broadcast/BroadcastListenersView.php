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
 * The listener counter beside a running broadcast.
 *
 * It always renders zero: the running total arrives over the websocket and is written into the span
 * by `web_player_headers.phtml`. Rendered by the player template and by the player ajax handler, which
 * is why it is a view rather than markup built where it is used.
 */
final class BroadcastListenersView extends AbstractView
{
    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('broadcast_listeners.phtml');
    }
}
