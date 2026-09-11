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

namespace Ampache\Module\Art\Generated\Template;

use Ampache\Module\Art\Generated\Palette;
use Ampache\Module\Art\Generated\Recipe;

/**
 * Draws the illustrated tiles. The two shipped templates differ only by their ground.
 *
 * Everything is drawn in a 0..100 viewBox, so one document serves every requested size. The only thing
 * the requested size changes is whether the name is drawn at all: below TEXT_FLOOR it would be an
 * unreadable smear, and the colour alone identifies the item better than grey mush.
 */
abstract class AbstractSvgTemplate implements TemplateInterface
{
    /** Names are broken on these before falling back to cutting mid-word. */
    private const string BREAK_CHARS = "/\\-_·|";

    private const string FONT_STACK = "'DejaVu Sans Condensed','Helvetica Neue',Helvetica,Arial,sans-serif";
    /** Below this many pixels the name is dropped and only the motif remains. */
    private const int TEXT_FLOOR = 96;

    public function render(Recipe $recipe, int $edge): string
    {
        $ground = $this->ground($recipe);
        $accent = $this->accent($recipe);
        $face   = $this->face($recipe);
        $body   = $this->motif($recipe, $accent, $ground);

        if ($edge >= self::TEXT_FLOOR) {
            $body .= $this->chrome($recipe, $accent, $face);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100%" height="100%"'
            . ' preserveAspectRatio="xMidYMid slice" role="img" aria-label="' . $this->esc($recipe->name) . '">'
            . '<rect width="100" height="100" fill="' . $ground . '"/>'
            . $body
            . '</svg>';
    }

    abstract protected function isDark(): bool;

    private function accent(Recipe $recipe): string
    {
        return $this->isDark()
            ? $recipe->palette['accent']
            : $this->blend($recipe->palette['accent'], '#000000', 0.28);
    }

    private function blend(string $from, string $to, float $amount): string
    {
        $a = sscanf($from, '#%2x%2x%2x') ?? [0, 0, 0];
        $b = sscanf($to, '#%2x%2x%2x') ?? [0, 0, 0];

        return sprintf(
            '#%02x%02x%02x',
            (int) ($a[0] + ($b[0] - $a[0]) * $amount),
            (int) ($a[1] + ($b[1] - $a[1]) * $amount),
            (int) ($a[2] + ($b[2] - $a[2]) * $amount)
        );
    }

    /* ----- type, shared by every motif ----- */

    private function chrome(Recipe $recipe, string $accent, string $face): string
    {
        $veil = $this->isDark() ? '#000' : '#fff';
        $out  = '<linearGradient id="g" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0" stop-color="' . $veil . '" stop-opacity="0"/>'
            . '<stop offset="0.42" stop-color="' . $veil . '" stop-opacity="0.74"/>'
            . '<stop offset="1" stop-color="' . $veil . '" stop-opacity="0.92"/></linearGradient>'
            . '<rect x="0" y="44" width="100" height="56" fill="url(#g)"/>'
            . '<rect x="0" y="0" width="5" height="100" fill="' . $accent . '"/>'
            . '<text x="12" y="13" font-family="' . self::FONT_STACK . '" font-size="3.4" letter-spacing="1.5"'
            . ' fill="' . $accent . '">' . $this->esc($recipe->label) . '</text>';

        $lines = $this->lines($recipe->name, 5.2, 82, 3);
        $y     = 84.5 - 11.6 * (count($lines) - 1);
        foreach ($lines as $index => $line) {
            $out .= '<text x="12" y="' . round($y + $index * 11.6, 2) . '" font-family="' . self::FONT_STACK
                . '" font-size="10" fill="' . $face . '" textLength="' . round(min(82, mb_strlen($line) * 5.2), 2)
                . '" lengthAdjust="spacingAndGlyphs">' . $this->esc($line) . '</text>';
        }

        if ($recipe->subtitle !== '') {
            $out .= '<text x="12" y="93.5" font-family="' . self::FONT_STACK . '" font-size="4.4" fill="' . $accent
                . '" textLength="' . round(min(82, mb_strlen($recipe->subtitle) * 2.3), 2)
                . '" lengthAdjust="spacingAndGlyphs">' . $this->esc($recipe->subtitle) . '</text>';
        }

        return $out;
    }

    /**
     * Object names are typed by users and land inside XML, so every one of them goes through here.
     */
    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function face(Recipe $recipe): string
    {
        return $this->isDark()
            ? $recipe->palette['face']
            : $this->blend('#1a1d22', $recipe->palette['accent'], 0.10);
    }

    private function ground(Recipe $recipe): string
    {
        return $this->isDark()
            ? $recipe->palette['ground']
            : $this->blend('#ffffff', $recipe->palette['accent'], 0.07);
    }

    private function initials(string $name): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [$name];
        $out   = mb_strtoupper(mb_substr($words[0], 0, 1));
        if (count($words) > 1) {
            $out .= mb_strtoupper(mb_substr($words[1], 0, 1));
        }

        return $out;
    }

    /**
     * Breaks a name into lines. Widths are an estimate: textLength corrects whatever the client font
     * does, so the estimate only has to be close enough to pick sensible break points.
     *
     * @return list<string>
     */
    private function lines(string $text, float $perChar, float $maxWidth, int $maxLines): array
    {
        // each piece remembers whether a space belongs in front of it: the halves of one long word are
        // rejoined as they were written, while separate words keep the space between them
        $pieces = [];
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $split = $this->split($word, (int) ($maxWidth / $perChar));
            foreach ($split as $index => $piece) {
                $pieces[] = [$piece, $index === 0];
            }
        }

        $lines   = [];
        $current = '';
        foreach ($pieces as [$piece, $spaced]) {
            $glue      = ($current === '' || !$spaced) ? '' : ' ';
            $candidate = $current . $glue . $piece;
            if (mb_strlen($candidate) * $perChar > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $piece;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        if (count($lines) > $maxLines) {
            $lines                = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = mb_substr(rtrim($lines[$maxLines - 1]), 0, -1) . '…';
        }

        return ($lines === []) ? [''] : array_values($lines);
    }

    /** An artist gets a medallion: initials ringed by rays whose lengths come from the name. */
    private function medallion(Recipe $recipe, string $accent): string
    {
        $next = $this->sequence($recipe->seed);
        $out  = '';
        for ($i = 0; $i < 44; $i++) {
            $angle = deg2rad($i * 360 / 44);
            $from  = 23.8;
            $len   = 19.5 * (0.28 + $next(0, 62) / 100) * 0.55;
            $out .= '<line x1="' . round(50 + cos($angle) * $from, 2) . '" y1="' . round(50 + sin($angle) * $from, 2)
                . '" x2="' . round(50 + cos($angle) * ($from + $len), 2) . '" y2="' . round(50 + sin($angle) * ($from + $len), 2)
                . '" stroke="' . $accent . '" stroke-opacity="' . $this->weight(0.45 + $next(0, 45) / 100)
                . '" stroke-width="1.3" stroke-linecap="round"/>';
        }

        return $out
            . '<circle cx="50" cy="50" r="19.5" fill="none" stroke="' . $accent . '" stroke-width="1.4"/>'
            . '<text x="50" y="56.5" text-anchor="middle" font-family="' . self::FONT_STACK
            . '" font-size="17" fill="' . $this->face($recipe) . '">' . $this->esc($this->initials($recipe->name)) . '</text>';
    }

    private function motif(Recipe $recipe, string $accent, string $ground): string
    {
        return match ($recipe->motif) {
            'record' => $this->record($recipe, $accent, $ground),
            'medallion' => $this->medallion($recipe, $accent),
            'waveform' => $this->waveform($recipe, $accent),
            'tracklist' => $this->tracklist($recipe),
            default => '',
        };
    }

    /* ----- motifs ----- */

    /** An album is a record: grooves, a label, a spindle hole, cropped by the frame. */
    private function record(Recipe $recipe, string $accent, string $ground): string
    {
        $next = $this->sequence($recipe->seed);
        $out  = '<circle cx="74" cy="34" r="46" fill="#000" fill-opacity="' . ($this->isDark() ? '0.35' : '0.06') . '"/>';
        for ($radius = 14.0; $radius < 46.0; $radius += 1.1) {
            $fade = round(0.34 - ($radius / 46) * 0.20, 3);
            $lit  = ($next(0, 9) === 0);
            $out .= '<circle cx="74" cy="34" r="' . round($radius, 2) . '" fill="none" stroke="' . $accent
                . '" stroke-opacity="' . $this->weight(max(0.06, $lit ? $fade + 0.22 : $fade))
                . '" stroke-width="' . ($this->isDark() ? '0.4' : '0.5') . '"/>';
        }

        return $out
            . '<circle cx="74" cy="34" r="12.9" fill="' . $accent . '"/>'
            . '<circle cx="74" cy="34" r="1.8" fill="' . $ground . '"/>';
    }

    /* ----- helpers ----- */

    /**
     * A deterministic stream, so the same item always draws the same tile. mt_rand() would work but it
     * is global state, and seeding it here would disturb whatever else the request is doing.
     *
     * @return callable(int, int): int
     */
    private function sequence(string $seed): callable
    {
        $state = crc32($seed);

        return function (int $low, int $high) use (&$state): int {
            $state = ($state * 1103515245 + 12345) & 0x7fffffff;

            return $low + ($state % max(1, $high - $low + 1));
        };
    }

    /**
     * Splits an over-long word, preferring the separators real names are built from: without this a
     * playlist called Lounge_Soiree_Firefox2 is one unbreakable word and runs off the tile.
     *
     * @return list<string>
     */
    private function split(string $word, int $limit): array
    {
        if ($limit < 1 || mb_strlen($word) <= $limit) {
            return [$word];
        }

        $parts = preg_split('/(?<=[' . preg_quote(self::BREAK_CHARS, '/') . '])/u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [$word];
        $out   = [];
        foreach ($parts as $part) {
            while (mb_strlen($part) > $limit) {
                $out[] = mb_substr($part, 0, $limit);
                $part  = mb_substr($part, $limit);
            }

            if ($part !== '') {
                $out[] = $part;
            }
        }

        return $out;
    }

    /** A playlist is a list, and every line of it is one of those waveforms, borrowed from its artists. */
    private function tracklist(Recipe $recipe): string
    {
        $rows = min(5, count($recipe->voices));
        if ($rows === 0) {
            return '';
        }

        $step = 9.3;
        $top  = 21.5 + $step * 0.35;
        $out  = '';
        for ($row = 0; $row < $rows; $row++) {
            $voice = $recipe->voices[$row];
            $axis  = $top + $row * $step;
            $next  = $this->sequence($recipe->seed . $voice . $row);
            $tint  = Palette::accentForSeed($voice);
            $len   = 40 + $next(0, 42);
            $count = max(6, (int) ($len / 1.4));
            $gap   = 0.4;
            $width = max(0.3, ($len - ($count + 1) * $gap) / $count);
            $slow  = $next(20, 55) / 10;
            $phase = $next(0, 314) / 100;

            for ($i = 0; $i < $count; $i++) {
                $u      = $i / max(1, $count - 1);
                $shape  = sin(M_PI * $u) ** 2 * 0.66 + 0.34;
                $value  = sin($u * $slow * M_PI * 2 + $phase) * 0.6 + $next(-20, 20) / 100;
                $height = abs($value) * $shape * $step * 0.42 + 0.25;
                $out .= '<rect x="' . round(12.5 + $gap + $i * ($width + $gap), 2) . '" y="' . round($axis - $height, 2)
                    . '" width="' . round($width, 2) . '" height="' . round($height * 2, 2)
                    . '" fill="' . $tint . '" fill-opacity="' . $this->weight(0.30 + abs($value) * 0.26) . '"/>';
            }

            $out .= '<circle cx="8.2" cy="' . round($axis, 2) . '" r="0.85" fill="' . $tint
                . '" fill-opacity="' . $this->weight(0.6) . '"/>';
        }

        return $out;
    }

    /** A song is a waveform: bars mirrored about an axis, shaped by an envelope so it reads as audio. */
    private function waveform(Recipe $recipe, string $accent): string
    {
        $next  = $this->sequence($recipe->seed);
        $count = 46;
        $gap   = 0.4;
        $width = (100 - ($count + 1) * $gap) / $count;
        $slow  = $next(20, 60) / 10;
        $fast  = $next(60, 140) / 10;
        $phase = $next(0, 314) / 100;

        $out = '';
        for ($i = 0; $i < $count; $i++) {
            $u      = $i / ($count - 1);
            $shape  = sin(M_PI * $u) ** 2 * 0.75 + 0.25;
            $value  = sin($u * $slow * M_PI * 2 + $phase) * 0.5 + sin($u * $fast * M_PI * 2) * 0.28 + $next(-18, 18) / 100;
            $height = abs($value) * $shape * 30 + 0.8;
            $out .= '<rect x="' . round($gap + $i * ($width + $gap), 2) . '" y="' . round(40 - $height, 2)
                . '" width="' . round($width, 2) . '" height="' . round($height * 2, 2)
                . '" fill="' . $accent . '" fill-opacity="' . $this->weight(0.55 + abs($value) * 0.45) . '"/>';
        }

        return $out;
    }

    /**
     * The same opacity that carries a motif on a dark ground nearly vanishes on a pale one, so every
     * motif is drawn firmer in the light template.
     */
    private function weight(float $opacity): float
    {
        return round(min(1.0, $this->isDark() ? $opacity : $opacity * 1.85), 3);
    }
}
