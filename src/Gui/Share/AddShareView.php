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

namespace Ampache\Gui\Share;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Override;

/**
 * The create-share form.
 *
 * It re-posts the values a failed submission carried, so the form does not reset what the user typed.
 */
final class AddShareView extends AbstractView
{
    private const array DOWNLOADABLE_TYPES = [
        LibraryItemEnum::SONG,
        LibraryItemEnum::VIDEO,
        LibraryItemEnum::PODCAST_EPISODE,
    ];

    public function __construct(
        private readonly library_item&displayable_item $object,
        private readonly LibraryItemEnum $objectType,
        private readonly string $token,
        private readonly bool $isZipable,
        private readonly bool $hasFailed,
        private readonly string $message,
        private readonly string $webPath,
        private readonly int $defaultExpireDays,
    ) {}

    public function getExpire(): string
    {
        return (string) ($_REQUEST['expire'] ?? $this->defaultExpireDays);
    }

    public function getFormAction(): string
    {
        return $this->webPath . '/share.php?action=create';
    }

    public function getMaxCounter(): string
    {
        return (string) ($_REQUEST['max_counter'] ?? '0');
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getObjectId(): int
    {
        return $this->object->getId();
    }

    public function getObjectLink(): string
    {
        return $this->object->get_f_link();
    }

    public function getObjectType(): string
    {
        return $this->objectType->value;
    }

    public function getSecret(): string
    {
        return (string) ($_REQUEST['secret'] ?? $this->token);
    }

    public function hasFailed(): bool
    {
        return $this->hasFailed;
    }

    /**
     * A first visit arrives by GET and both boxes start ticked; a re-render keeps what was submitted.
     */
    public function isDownloadChecked(): bool
    {
        return (bool) ($_REQUEST['allow_download'] ?? 0) || Core::get_server('REQUEST_METHOD') === 'GET';
    }

    public function isStreamChecked(): bool
    {
        return (bool) ($_REQUEST['allow_stream'] ?? 0) || Core::get_server('REQUEST_METHOD') === 'GET';
    }

    /**
     * A single media file may be downloaded outright; anything else has to come as a zip.
     */
    public function showDownload(): bool
    {
        return (in_array($this->objectType, self::DOWNLOADABLE_TYPES, true) && Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD))
            || ($this->isZipable && Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD));
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('share/add_share.phtml');
    }
}
