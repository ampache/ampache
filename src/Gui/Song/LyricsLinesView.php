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

namespace Ampache\Gui\Song;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\OpenSubsonic_Fields;
use Override;

/**
 * A song's lyrics as lines: timed lines carry their start so the web player can follow them, plain text keeps its breaks.
 */
final class LyricsLinesView extends AbstractView
{
    public function __construct(
        private readonly int $songId,
        private readonly string $text,
    ) {}

    /**
     * Provider text may carry a literal <br> and CRLF line endings; each <br>, with the line ending after it, is one newline.
     */
    public static function toPlainText(string $text): string
    {
        return (string) preg_replace('/<br\s*\/?>\n?/i', "\n", str_replace(["\r\n", "\r"], "\n", $text));
    }

    /**
     * Lyrics text comes from an ID3 tag or a third-party provider, so it is never trusted markup; this builds the
     * only HTML it may contain itself (line breaks), which is why the template prints it with raw().
     */
    public function getLyrics(): string
    {
        if ($this->text === '') {
            return T_('No lyrics found.');
        }

        return nl2br($this->e(self::toPlainText($this->text)));
    }

    /**
     * The file's `[offset:]` in milliseconds; positive means the lyrics come sooner.
     */
    public function getOffset(): int
    {
        return ($this->text === '') ? 0 : OpenSubsonic_Fields::parseLyrics($this->text)['offset'];
    }

    public function getSongId(): int
    {
        return $this->songId;
    }

    /**
     * The timed lines in play order, or nothing when the lyrics carry no LRC timestamps; `end` only where the file states it.
     *
     * @return list<array{start: int, end: ?int, value: string}>
     */
    public function getSyncedLines(): array
    {
        if ($this->text === '') {
            return [];
        }

        $parsed = OpenSubsonic_Fields::parseLyrics($this->text, true);
        if (!$parsed['synced']) {
            return [];
        }

        $lines = [];
        foreach ($parsed['line'] as $line) {
            // an untimed line in an LRC file is an id tag like [ar:...] or [offset:...], not something to sing
            if (isset($line['start'])) {
                $lines[] = ['start' => $line['start'], 'end' => $line['end'] ?? null, 'value' => $line['value']];
            }
        }

        return $lines;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('lyrics_lines.phtml');
    }
}
