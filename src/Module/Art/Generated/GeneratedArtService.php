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

namespace Ampache\Module\Art\Generated;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Generated\Template\TemplateInterface;
use Ampache\Module\Art\Generated\Template\TemplateRegistryInterface;

/**
 * Decides whether an item without a cover gets a drawn tile, and draws it.
 *
 * Nothing is stored. An SVG is a few kilobytes of string building, so caching it would cost more than
 * making it again: no thumbnails to keep, nothing to invalidate when an album is renamed, nothing an
 * administrator has to purge later to tell generated tiles from real covers.
 */
final readonly class GeneratedArtService implements GeneratedArtServiceInterface
{
    /**
     * Made-up items for the preview shown next to each template in the preferences page. Invented rather
     * than borrowed from the library, so the previews look the same on a fresh install as on a full one.
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: list<string>}>
     */
    private const array PREVIEWS = [
        'record' => ['ALBUM', 'Night Cartography', 'ORCHESTRE DU SOIR', 'Orchestre du Soir', []],
        'medallion' => ['ARTIST', 'Orchestre du Soir', '', 'Orchestre du Soir', []],
        'waveform' => ['SONG', 'Third Movement', 'Night Cartography', 'Orchestre du Soir', []],
        'tracklist' => ['PLAYLIST', 'Late Shift', '18 tracks', 'Late Shift', ['Kepler Trio', 'Vela', 'Norsk Lys', 'Aster', 'Hemlock']],
    ];

    public function __construct(
        private RecipeBuilderInterface $recipeBuilder,
        private TemplateRegistryInterface $templates,
    ) {}

    /**
     * @return list<TemplateInterface>
     */
    public function getTemplates(): array
    {
        return $this->templates->all();
    }

    public function isEnabled(): bool
    {
        return AmpConfig::get('generated_art_enabled', false) && AmpConfig::get('generated_art', false);
    }

    public function render(string $objectType, int $objectId, int $edge, ?string $forceTemplate = null, bool $force = false): ?array
    {
        // `generate=1` in the url draws the tile whatever the viewer's own preference says, so one link
        // shows the same picture to everyone. The instance switch still decides whether we draw at all.
        $allowed = $force
            ? (bool) AmpConfig::get('generated_art_enabled', false)
            : $this->isEnabled();
        if (!$allowed) {
            return null;
        }

        $recipe = $this->recipeBuilder->build($objectType, $objectId);
        if ($recipe === null) {
            return null;
        }

        $template = $this->templateById($forceTemplate) ?? $this->resolveTemplate();

        return [
            'svg' => $template->render($recipe, $edge),
            // the drawing identifies itself, so renaming an item changes the tag and the tile refreshes
            'etag' => 'gen-' . $template->getId() . '-' . $recipe->fingerprint() . '-' . $this->bucket($edge),
        ];
    }

    public function renderPreview(string $motif, ?string $templateId, int $edge): ?array
    {
        $recipe = self::PREVIEWS[$motif] ?? null;
        if ($recipe === null) {
            return null;
        }

        $template = $this->templateById($templateId) ?? $this->resolveTemplate();
        $built    = new Recipe(
            label: $recipe[0],
            name: $recipe[1],
            subtitle: $recipe[2],
            motif: $motif,
            seed: $recipe[3],
            palette: Palette::forSeed($recipe[3]),
            voices: $recipe[4],
        );

        return [
            'svg' => $template->render($built, $edge),
            'etag' => 'genprev-' . $template->getId() . '-' . $built->fingerprint() . '-' . $this->bucket($edge),
        ];
    }

    public function resolveTemplate(): TemplateInterface
    {
        $available = $this->getTemplates();
        $wanted    = (string) AmpConfig::get('generated_art_template_lock', '');
        if ($wanted === '') {
            $wanted = (string) AmpConfig::get('generated_art_template', 'auto');
        }

        if ($wanted === 'auto' || $wanted === '') {
            // follow the interface the listener is already looking at
            $wanted = (AmpConfig::get('theme_color', 'dark') === 'light') ? 'light' : 'dark';
        }

        foreach ($available as $template) {
            if ($template->getId() === $wanted) {
                return $template;
            }
        }

        return $available[0];
    }

    public function takesPrecedenceOverCustom(): bool
    {
        return (bool) AmpConfig::get('generated_art_over_custom', false);
    }

    /**
     * Only two layouts exist, so the tag does not need the exact pixel count: one entry per bucket is
     * enough for the browser cache and keeps a grid from fetching the same drawing twice.
     */
    private function bucket(int $edge): string
    {
        return ($edge < 96) ? 'small' : 'full';
    }

    private function templateById(?string $id): ?TemplateInterface
    {
        return ($id === null || $id === '') ? null : $this->templates->find($id);
    }
}
