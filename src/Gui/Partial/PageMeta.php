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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;

/**
 * What the page's <head> says about the object it shows: description, Open Graph and schema.org.
 * An action sets it before the header renders; pages that set nothing emit nothing.
 */
final class PageMeta
{
    private static ?self $current = null;

    /**
     * @param array<string, mixed> $jsonLd
     */
    private function __construct(
        private readonly string $description,
        private readonly string $ogType,
        private readonly string $title,
        private readonly string $url,
        private readonly string $image,
        private readonly array $jsonLd,
    ) {}

    /**
     * Seconds as a schema.org duration: 236 becomes PT3M56S.
     */
    public static function duration(int $seconds): string
    {
        if ($seconds < 1) {
            return '';
        }

        $hours   = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return 'PT' . (($hours > 0) ? $hours . 'H' : '') . (($minutes > 0) ? $minutes . 'M' : '') . ($seconds % 60) . 'S';
    }

    /**
     * The markup for the <head>, or nothing when no page set anything.
     */
    public static function render(): string
    {
        $meta          = self::$current;
        self::$current = null;
        if ($meta === null) {
            return '';
        }

        $e   = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES);
        $out = [];
        // An object with nothing of its own to say still gets a description, falling back to the site-wide one
        $description = ($meta->description !== '') ? $meta->description : trim((string) AmpConfig::get('site_description', ''));
        if ($description !== '') {
            $out[] = '<meta name="description" content="' . $e($description) . '">';
            $out[] = '<meta property="og:description" content="' . $e($description) . '">';
        }

        $out[] = '<meta property="og:type" content="' . $e($meta->ogType) . '">';
        $out[] = '<meta property="og:title" content="' . $e($meta->title) . '">';
        if ($meta->url !== '') {
            $out[] = '<meta property="og:url" content="' . $e($meta->url) . '">';
        }

        if ($meta->image !== '') {
            // Both the direct route and its beautified `stream_beautiful_url` rewrite reach the same art
            // handler, and only that handler can generate an svg placeholder a scraper cannot render.
            $imagePath   = (string) parse_url($meta->image, PHP_URL_PATH);
            $servedByArt = str_ends_with($imagePath, '/image.php') || str_contains($imagePath, '/play/art/');
            $image       = ($servedByArt) ? $meta->image . '&nosvg=1' : $meta->image;

            $out[] = '<meta property="og:image" content="' . $e($image) . '">';
            $out[] = '<meta name="twitter:card" content="summary">';
        }

        if ($meta->jsonLd !== []) {
            $out[] = '<script type="application/ld+json">'
                . json_encode($meta->jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
                . '</script>';
        }

        return implode("\n", $out) . "\n";
    }

    /**
     * @param list<string|int|null> $facts joined into the description, empties skipped
     * @param array<string, mixed> $jsonLd
     */
    public static function set(array $facts, string $ogType, string $title, string $url, string $image = '', array $jsonLd = []): void
    {
        $parts = [];
        foreach ($facts as $fact) {
            $fact = trim(html_entity_decode(strip_tags((string) $fact), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($fact !== '') {
                $parts[] = $fact;
            }
        }

        self::$current = new self(
            mb_substr(implode(' · ', $parts), 0, 300),
            $ogType,
            trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $url,
            $image,
            $jsonLd
        );
    }
}
