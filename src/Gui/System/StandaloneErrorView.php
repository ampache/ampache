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

namespace Ampache\Gui\System;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The full-page error shown when the request cannot produce its own page.
 *
 * It carries its own chrome because it replaces a page that may have failed before the header was written,
 * so it can rely on nothing the normal layout sets up.
 */
final class StandaloneErrorView extends AbstractView
{
    public function __construct(
        private readonly StandaloneErrorTypeEnum $type,
        private readonly string $webPath,
        private readonly string $logoUrl,
        private readonly string $siteTitle,
        private readonly bool $demoMode,
        private readonly string $detail = '',
    ) {}

    /**
     * @return list<string>
     */
    public function getBodyParagraphs(): array
    {
        if ($this->demoMode && $this->type !== StandaloneErrorTypeEnum::ERROR) {
            return [T_('You have been redirected to this page because you attempted to access a function that is disabled in the demo.')];
        }

        return match ($this->type) {
            StandaloneErrorTypeEnum::ACCESS_DENIED => [
                T_('You have been redirected to this page because you do not have access to this function'),
                T_('If you believe this is an error please contact an Ampache administrator'),
                T_('This event has been logged') . ': ' . T_('You will be automatically redirected in 10 seconds.'),
            ],
            StandaloneErrorTypeEnum::ERROR => [
                T_('Something went wrong. Please check the logs for further information.'),
                T_('You will be automatically redirected in 10 seconds.'),
            ],
            StandaloneErrorTypeEnum::PERMISSION_DENIED => [
                T_('You do not have permission to write to this file or folder'),
            ],
        };
    }

    public function getDebugPageUrl(): string
    {
        return $this->webPath . '/test.php';
    }

    public function getHeading(): string
    {
        return match ($this->type) {
            StandaloneErrorTypeEnum::ACCESS_DENIED => T_('Access Denied'),
            StandaloneErrorTypeEnum::ERROR => T_('Error'),
            StandaloneErrorTypeEnum::PERMISSION_DENIED => T_('Permission Denied'),
        };
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    /**
     * A write permission failure is not fixed by waiting, so that page alone does not bounce anywhere.
     */
    public function getRedirectUrl(): ?string
    {
        return match ($this->type) {
            StandaloneErrorTypeEnum::ACCESS_DENIED => $this->webPath,
            StandaloneErrorTypeEnum::ERROR => $this->getDebugPageUrl(),
            StandaloneErrorTypeEnum::PERMISSION_DENIED => null,
        };
    }

    public function getSiteTitle(): string
    {
        return $this->siteTitle;
    }

    /**
     * The permission failure names the file it could not write; the other two only say the event was logged.
     */
    public function getSubHeading(): string
    {
        return ($this->type === StandaloneErrorTypeEnum::PERMISSION_DENIED)
            ? $this->detail
            : T_('This event has been logged');
    }

    public function getTitle(): string
    {
        return ($this->type === StandaloneErrorTypeEnum::ERROR)
            ? T_('Ampache Error Page')
            : T_('Ampache') . ' -- ' . T_('Debug Page');
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isDebugPageLinked(): bool
    {
        return $this->type === StandaloneErrorTypeEnum::ERROR;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('standalone_error.phtml');
    }
}
