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
 * One preference as the screen needs it: the subject's value, the shipped default, and the server's value
 */
final readonly class PreferenceItem
{
    /**
     * @param ?array<array-key, string> $choices posted value => displayed label, when the set is closed
     * @param ?string $shippedDefault the value Ampache ships with, null when the name is unknown to `Preference::DEFAULTS`
     * @param ?string $systemValue the `user = -1` row, null when the subject *is* the system
     * @param ?string $warning why another preference is making this one pointless, when one is
     * @param ?string $numberFallback the subject's own value of the preference this one falls back to
     *        when left blank (e.g. `transcode_bitrate` for `transcode_bitrate_webplayer`), null when this
     *        preference has none
     */
    public function __construct(
        public string $name,
        public string $description,
        public PreferenceType $type,
        public ?string $subcategory,
        public int $level,
        public string $value,
        public ?string $shippedDefault,
        public ?string $systemValue,
        public ?array $choices,
        public bool $editable,
        public bool $isSecret,
        public bool $secretIsSet,
        public ?PreferenceHelp $help,
        public ?string $warning = null,
        public ?string $numberFallback = null,
    ) {}

    /**
     * Whether the row is worth showing under "differs from default", which a secret never is
     */
    public function differsFromShipped(): bool
    {
        return !$this->isSecret
            && $this->shippedDefault !== null
            && $this->value !== $this->shippedDefault;
    }

    public function inputId(): string
    {
        return 'pref-' . $this->name;
    }
}
