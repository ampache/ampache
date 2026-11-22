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

namespace Ampache\Plugin;

use Ampache\Repository\Model\User;
use Override;

class AmpacheTelegram extends AmpachePlugin implements PluginExternalShareInterface
{
    #[Override]
    public string $categories = 'share';

    #[Override]
    public string $description = 'Telegram share';

    #[Override]
    public string $max_ampache = '999999';

    #[Override]
    public string $min_ampache = '370027';

    #[Override]
    public string $name = 'Telegram';

    #[Override]
    public string $url = 'https://telegram.org/';

    #[Override]
    public string $version = '000001';

    public function __construct()
    {
        $this->description = T_('Telegram share');
    }

    #[Override]
    public function external_share(string $url, string $text): string
    {
        $share = "https://t.me/share/url?url=" . rawurlencode($url);
        if ($text !== '') {
            $share .= "&text=" . rawurlencode($text);
        }

        return $share;
    }

    #[Override]
    public function install(): bool
    {
        return true;
    }

    #[Override]
    public function load(User $user): bool
    {
        unset($user);

        return true;
    }

    #[Override]
    public function uninstall(): bool
    {
        return true;
    }

    #[Override]
    public function upgrade(): bool
    {
        return true;
    }
}
