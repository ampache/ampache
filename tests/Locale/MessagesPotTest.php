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

namespace Ampache\Locale;

use PHPUnit\Framework\TestCase;

/**
 * Two msgids that differ only by case are two translation jobs for one piece of text: every language
 * catalogue ends up with duplicate work, and a translator has no way to know the strings are related.
 * This walks `locale/base/messages.pot` (the source string extraction every T_()/nT_() call feeds) and
 * fails on any such pair that isn't in `KNOWN_CASE_VARIANTS` below.
 *
 * A real duplicate should be fixed at the call site (reuse the existing string, `mb_strtoupper()` it in
 * code if the rendering wants shouting case - see `RecipeBuilder::fromAlbum()`) rather than added here.
 * `KNOWN_CASE_VARIANTS` is only for pairs that read the same to a case-insensitive diff but are not the
 * same word in use: a preposition next to a toggle-state label, a past-tense verb in a sentence next to
 * a noun column header, an inline qualifier next to a standalone status cell, a singular/plural pair
 * feeding `nT_()` next to an unrelated Title Case option label, or an established all-caps badge/log-level
 * convention. `testKnownCaseVariantsAreStillPresent()` fails if one of these pairs stops existing, so a
 * fix that removes one side leaves no stale entry behind.
 */
class MessagesPotTest extends TestCase
{
    /**
     * Pairs (or larger groups) of msgids that are case-insensitive duplicates of each other but are kept
     * as distinct strings on purpose. Each value is the reason, checked only by a human reading this file.
     *
     * @var array<int, array{0: list<string>, 1: string}>
     */
    private const array KNOWN_CASE_VARIANTS = [
        [['on', 'On'], 'preposition ("… on {site}") vs. the on/off toggle-state label'],
        [['private', 'Private'], 'lowercase smartlist-rule dropdown option vs. the Title Case checkbox label'],
        [['public', 'Public'], 'lowercase smartlist-rule dropdown option vs. the Title Case checkbox label'],
        [['played', 'Played'], 'past-tense verb in the activity feed sentence vs. the "play count" column header'],
        [['rated', 'Rated'], 'past-tense verb in the activity feed sentence vs. the "Rated" filter checkbox label'],
        [['skipped', 'Skipped'], 'lowercase podcast-episode-state dropdown option vs. the "# Skipped" stat column header'],
        [['required', 'Required'], 'inline "(required)" qualifier vs. the standalone table-cell status word'],
        [['day', 'Day'], 'nT_() singular of the day/days duration pair vs. the Title Case graph-granularity option'],
        [['hour', 'Hour'], 'nT_() singular of the hour/hours duration pair vs. the Title Case graph-granularity option'],
        [['Warning', 'WARNING'], 'section heading vs. the all-caps status-badge/log-level convention (OK/WARNING/UNKNOWN)'],
        [['UNKNOWN', 'Unknown'], 'the same all-caps status-badge convention, paired with OK/WARNING in the install test table'],
    ];
    private const string POT_FILE = __DIR__ . '/../../locale/base/messages.pot';

    public function testKnownCaseVariantsAreStillPresent(): void
    {
        $groups = $this->groupMsgidsByCaseInsensitiveText();

        $stale = [];
        foreach (self::KNOWN_CASE_VARIANTS as [$pair, $reason]) {
            $variants = $groups[$this->normalize($pair[0])] ?? [];
            if (count($variants) < 2) {
                $stale[] = implode(' / ', $pair) . ' (' . $reason . ')';
            }
        }

        self::assertSame(
            [],
            $stale,
            "Entries in MessagesPotTest::KNOWN_CASE_VARIANTS no longer have both variants in messages.pot:\n  "
            . implode("\n  ", $stale) . "\nRemove the stale entry."
        );
    }

    public function testNoUnexplainedCaseInsensitiveDuplicateMsgids(): void
    {
        $groups = $this->groupMsgidsByCaseInsensitiveText();

        $allowed = [];
        foreach (self::KNOWN_CASE_VARIANTS as [$pair, $reason]) {
            $allowed[$this->normalize($pair[0])] = true;
        }

        $unexplained = [];
        foreach ($groups as $normalized => $variants) {
            if (count($variants) < 2 || isset($allowed[$normalized])) {
                continue;
            }

            $unexplained[] = implode(' / ', array_map(static fn(string $s): string => '"' . $s . '"', $variants));
        }

        self::assertSame(
            [],
            $unexplained,
            "Found msgid(s) in messages.pot that differ only by case:\n  " . implode("\n  ", $unexplained)
            . "\nReuse one of them at the other call site (mb_strtoupper() it in code if the rendering needs "
            . 'shouting case), or add the pair to MessagesPotTest::KNOWN_CASE_VARIANTS with the reason they '
            . 'are genuinely different words in use.'
        );
    }

    /**
     * @return array<string, list<string>> normalized text => the distinct original msgids that produced it
     */
    private function groupMsgidsByCaseInsensitiveText(): array
    {
        $groups = [];
        foreach ($this->readMsgids() as $msgid) {
            if ($msgid === '') {
                // the empty msgid is the PO header, not a translatable string
                continue;
            }

            $normalized = $this->normalize($msgid);
            $groups[$normalized] ??= [];

            if (!in_array($msgid, $groups[$normalized], true)) {
                $groups[$normalized][] = $msgid;
            }
        }

        return $groups;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * @return list<string>
     */
    private function readMsgids(): array
    {
        $lines = file(self::POT_FILE, FILE_IGNORE_NEW_LINES);
        self::assertNotFalse($lines, 'Could not read ' . self::POT_FILE);

        $msgids = [];
        $count  = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $isMsgid = preg_match('/^msgid(?:_plural)? "(.*)"$/', $lines[$i], $matches) === 1;
            if (!$isMsgid) {
                continue;
            }

            $value = $this->unescape($matches[1]);
            $i++;
            while ($i < $count && preg_match('/^"(.*)"$/', $lines[$i], $continuation) === 1) {
                $value .= $this->unescape($continuation[1]);
                $i++;
            }

            $i--;

            $msgids[] = $value;
        }

        return $msgids;
    }

    private function unescape(string $value): string
    {
        return str_replace(['\\n', '\\t', '\\"', '\\\\'], ["\n", "\t", '"', '\\'], $value);
    }
}
