<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

interface UpdateViewAdapterInterface
{
    public function getHtmlLanguage(): string;

    public function getCharset(): string;

    public function getTitle(): string;

    public function getLogoUrl(): string;

    public function getInstallationTitle(): string;

    public function getUpdateInfoText(): string;

    public function getErrorText(): string;

    public function hasUpdate(): bool;

    public function getUpdateActionUrl(): string;

    public function getUpdateInfo(): array;

    public function getWebPath(): string;
}
