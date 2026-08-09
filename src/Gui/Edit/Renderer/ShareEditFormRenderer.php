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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Repository\Model\Share;
use Override;

/**
 * The share edit dialog.
 *
 * A share is editable but is not a library item, so it reaches the dialog through `ShareRepository`.
 */
final class ShareEditFormRenderer extends AbstractEditFormRenderer
{
    private const array STREAMABLE_TYPES = ['song', 'video', 'podcast_episode'];

    public function allowDownload(): bool
    {
        return (bool) $this->getItem()->allow_download;
    }

    public function allowStream(): bool
    {
        return (bool) $this->getItem()->allow_stream;
    }

    public function getExpireDays(): string
    {
        return (string) $this->getItem()->expire_days;
    }

    public function getMaxCounter(): string
    {
        return (string) $this->getItem()->max_counter;
    }

    public function getObjectUrl(): string
    {
        return $this->getItem()->getObjectUrl();
    }

    public function getShareId(): int
    {
        return $this->getItem()->getId();
    }

    /**
     * A single media file may be downloaded outright; anything else has to come as a zip.
     */
    public function showDownload(): bool
    {
        $objectType = $this->getItem()->object_type ?? '';

        return (in_array($objectType, self::STREAMABLE_TYPES, true) && Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD))
            || (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $this->getContext()->zipHandler->isZipable($objectType));
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/share.phtml');
    }

    private function getItem(): Share
    {
        /** @var Share $item */
        $item = $this->getContext()->item;

        return $item;
    }
}
