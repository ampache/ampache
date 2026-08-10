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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Repository\Model\Live_Stream;
use Override;

/**
 * The live stream edit dialog.
 */
final class LiveStreamEditFormRenderer extends AbstractEditFormRenderer
{
    public function getCodec(): string
    {
        return (string) $this->getItem()->codec;
    }

    public function getLiveStreamId(): int
    {
        return $this->getItem()->getId();
    }

    public function getName(): string
    {
        return (string) $this->getItem()->name;
    }

    public function getSiteUrl(): string
    {
        return (string) $this->getItem()->site_url;
    }

    public function getUrl(): string
    {
        return (string) $this->getItem()->url;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/live_stream.phtml');
    }

    private function getItem(): Live_Stream
    {
        /** @var Live_Stream $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
