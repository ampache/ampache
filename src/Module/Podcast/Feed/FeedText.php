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

namespace Ampache\Module\Podcast\Feed;

/**
 * Turns the markup feeds put into their text fields back into plain text
 *
 * Some send plain text, some html, and some html escaped a second time on the way into the xml. All three end up as text.
 */
final class FeedText
{
    /** An escaped tag such as "&lt;p&gt;", meaning the markup was encoded a second time */
    private const string ESCAPED_TAG = '#&lt;\s*/?\s*[a-z][a-z0-9]*(?:\s[^&]*)?/?\s*&gt;#i';

    /** Tags that only start a new line; the openers cover feeds that never close their paragraphs */
    private const string LINE_TAGS = '#<\s*(?:br\b[^>]*|p\b[^>]*|div\b[^>]*|/\s*li|/\s*tr)\s*/?\s*>#i';

    /** Tags that end a block, so the next text starts a new paragraph */
    private const string PARAGRAPH_TAGS = '#<\s*/\s*(?:p|div|ul|ol|h[1-6]|blockquote|section)\s*>#i';

    /**
     * Convert a feed value to plain text, keeping paragraphs and breaks as newlines
     */
    public static function clean(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // only when there really is an encoded tag, so a description writing "&lt;" as text keeps what it wrote
        if (preg_match(self::ESCAPED_TAG, $value) === 1) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // record the layout as line breaks before the markup goes away
        $value = (string) preg_replace(self::PARAGRAPH_TAGS, "\n\n", $value);
        $value = (string) preg_replace(self::LINE_TAGS, "\n", $value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // feeds are full of non-breaking spaces, and \s does not match those
        $value = str_replace("\xc2\xa0", ' ', $value);

        $value = (string) preg_replace('#[ \t]*\R[ \t]*#', "\n", $value);
        $value = (string) preg_replace('#\n{3,}#', "\n\n", $value);
        $value = (string) preg_replace('#[ \t]{2,}#', ' ', $value);

        return trim($value);
    }

    /**
     * Same as clean(), for the values that have to stay on one line (titles, author, category)
     */
    public static function cleanLine(string $value): string
    {
        return trim(
            (string) preg_replace('#\s+#u', ' ', self::clean($value))
        );
    }
}
