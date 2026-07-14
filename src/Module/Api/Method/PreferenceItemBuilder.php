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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

/**
 * Builds the single-preference payload shared by user_preference and system_preference
 */
final class PreferenceItemBuilder
{
    /**
     * @param array<int, array<string, mixed>> $preference the row returned by Preference::get()
     *
     * @return array<string, mixed>
     */
    public function build(array $preference, User $user): array
    {
        $item = [
            'id' => (string) $preference[0]['id'],
            'name' => $preference[0]['name'],
            'level' => $preference[0]['level'],
            'description' => $preference[0]['description'],
            'value' => $preference[0]['value'],
            'type' => $preference[0]['type'],
            'category' => $preference[0]['category'],
            'subcategory' => $preference[0]['subcategory'],
            'has_access' => (((int) $preference[0]['level']) <= $user->access),
        ];

        // only the special types carry a value list
        if ($preference[0]['type'] === 'special') {
            $values = Preference::get_special_values((string) $preference[0]['name'], $user);

            $item['values'] = ($values) ? $values : [];
        }

        return $item;
    }
}
