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

namespace Ampache\Gui\Preferences;

/**
 * Supplies the closed set of values a preference accepts, when it has one.
 */
interface PreferenceChoiceProviderInterface
{
    /**
     * @param array<string, string> $held every preference of the subject, because a few lists depend on
     *        other preferences and those belong to the account being edited, not to whoever is editing
     * @return ?array<array-key, string> posted value => displayed label, or null when the field is free text
     */
    public function find(string $name, PreferenceSubject $subject, array $held = []): ?array;
}
