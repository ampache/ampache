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
 * Warns that a preference is being set while another one makes it pointless
 */
final readonly class PreferencePrerequisite
{
    /**
     * Marks a condition that reads `config/ampache.cfg.php`, whose half of the decision is not on the page
     */
    public const string CONFIG_PREFIX = 'config:';

    public const string IS       = 'is';
    public const string IS_EMPTY = 'is-empty';
    public const string IS_NOT   = 'is-not';
    public const string IS_OFF   = 'is-off';
    public const string IS_ON    = 'is-on';

    /**
     * @param string $preference the preference the warning is shown under
     * @param list<array{0: string, 1: string, 2?: string}> $when every condition must hold, as
     *        [other preference, operator, value]
     */
    public function __construct(
        public string $preference,
        public array $when,
        public string $text,
    ) {}

    /**
     * True when this condition reads the config file instead of the subject's preferences.
     */
    public static function isConfigKey(string $name): bool
    {
        return str_starts_with($name, self::CONFIG_PREFIX);
    }

    /**
     * @return list<string> the config settings this rule needs, without their prefix
     */
    public function configKeys(): array
    {
        $keys = [];
        foreach ($this->when as $condition) {
            if (self::isConfigKey($condition[0])) {
                $keys[] = substr($condition[0], strlen(self::CONFIG_PREFIX));
            }
        }

        return $keys;
    }

    /**
     * @param array<string, string> $values every preference of the subject, by name
     */
    public function holds(array $values): bool
    {
        foreach ($this->when as $condition) {
            if (!$this->matches($values[$condition[0]] ?? null, $condition)) {
                return false;
            }
        }

        return $this->when !== [];
    }

    /**
     * A preference the subject does not have cannot make anything pointless, so it never matches.
     *
     * @param array{0: string, 1: string, 2?: string} $condition
     */
    private function matches(?string $value, array $condition): bool
    {
        if ($value === null) {
            return false;
        }

        return match ($condition[1]) {
            self::IS => $value === ($condition[2] ?? ''),
            self::IS_NOT => $value !== ($condition[2] ?? ''),
            // Ampache stores booleans as '1' and '0', and an unset one reads as empty
            self::IS_ON => $value === '1',
            self::IS_OFF => $value !== '1',
            self::IS_EMPTY => $value === '' || $value === '0' || $value === '-1',
            default => false,
        };
    }
}
