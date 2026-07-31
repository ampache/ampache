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

namespace Ampache\Module\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;

/**
 * Recognises the placeholder text that tagging tools write into the publisher field
 *
 * Discogs-sourced tags carry `[no label]` and `Not On Label (Artist Self-released)` for releases that
 * never had a publisher, and rippers leave behind fragments like `/v/`. None of these name a real
 * label, so they are kept out of the `label` table rather than cleaned up by hand afterwards.
 */
final readonly class LabelNameFilter implements LabelNameFilterInterface
{
    /**
     * Matched case-insensitively against the whole name.
     *
     * The trailing branch covers names holding fewer than two letters or digits (`/<`, `/v/`, `−N`),
     * which are always junk while genuinely short labels (`XL`, `4AD`) survive.
     */
    public const string DEFAULT_PATTERN = '^\[no label\]|^not on label\b|^\[fwd:|^self[\s-]*released?\b|\(self[\s-]*released?\)|^[^\p{L}\p{N}]*[\p{L}\p{N}]?[^\p{L}\p{N}]*$';

    public function __construct(private ConfigContainerInterface $configContainer) {}

    public function filter(array $labelNames): array
    {
        return array_values(
            array_filter(
                $labelNames,
                fn(string $labelName): bool => !$this->isIgnored($labelName)
            )
        );
    }

    public function isIgnored(string $labelName): bool
    {
        $pattern = (string) ($this->configContainer->get(ConfigurationKeyEnum::LABEL_IGNORE_PATTERN) ?? '');
        if ($pattern === '') {
            $pattern = self::DEFAULT_PATTERN;
        }

        // `u` is needed for \p{L} and for the non-ascii punctuation these names arrive with. A broken
        // pattern (or invalid utf-8) makes preg_match return false, and dropping a label on an error
        // would be silent data loss, so anything other than a definite match keeps the name.
        return preg_match('/(' . $pattern . ')/iu', $labelName) === 1;
    }
}
