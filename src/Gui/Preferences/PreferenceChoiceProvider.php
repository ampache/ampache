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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Art\Generated\GeneratedArtServiceInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\CatalogTypeEnum;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Localplay\LocalPlayTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Preference;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\Model\Playlist;
use Override;

/**
 * The options a preference can be set to, for every preference that has a fixed list of them.
 */
final readonly class PreferenceChoiceProvider implements PreferenceChoiceProviderInterface
{
    public function __construct(
        private CatalogRepositoryInterface $catalogRepository,
        private GeneratedArtServiceInterface $generatedArtService,
        private MetadataFieldRepositoryInterface $metadataFieldRepository,
    ) {}

    #[Override]
    public function find(string $name, PreferenceSubject $subject, array $held = []): ?array
    {
        // the shared row is inherited by every new account, so one person's playlists do not belong in it
        $owner = ($subject->isServer) ? null : $subject->user;

        $choices = match ($name) {
            'album_sort' => [
                'default' => T_('Default'),
                'year_asc' => T_('Year ascending'),
                'year_desc' => T_('Year descending'),
                'name_asc' => T_('Name ascending'),
                'name_desc' => T_('Name descending'),
            ],
            'api_force_version' => $this->getApiVersions(),
            'disabled_custom_metadata_fields' => iterator_to_array($this->metadataFieldRepository->getPropertyList()),
            'encode_player_api_target', 'encode_player_webplayer_target', 'encode_target' => $this->getEncodeFormats('audio'),
            'encode_video_target' => $this->getEncodeFormats('video'),
            'generated_art_template' => $this->getGeneratedArtTemplates(),
            'jp_volume' => $this->getVolumes(),
            'lang' => get_languages(),
            'localplay_controller' => $this->getLocalplayControllers(),
            'localplay_level', 'upload_access_level' => $this->getAccessLevels(),
            'personalfav_playlist' => ($owner === null) ? [] : Playlist::get_playlist_array($owner->getId()),
            'personalfav_smartlist' => ($owner === null) ? [] : Search::get_search_array($owner->getId()),
            'play_type' => $this->getPlayTypes($held),
            'playlist_method' => [
                'send' => T_('Send on Add'),
                'send_clear' => T_('Send and Clear on Add'),
                'clear' => T_('Clear on Send'),
                'default' => T_('Default'),
            ],
            'playlist_type' => [
                'm3u' => T_('M3U'),
                'simple_m3u' => T_('Simple M3U'),
                'pls' => T_('PLS'),
                'asx' => T_('Asx'),
                'ram' => T_('RAM'),
                'xspf' => T_('XSPF'),
            ],
            'popularvariety_days' => $this->getStatisticsWindows(),
            'ratingmatch_stars' => [
                0 => T_('Disabled'),
                1 => T_('1 Star'),
                2 => T_('2 Stars'),
                3 => T_('3 Stars'),
                4 => T_('4 Stars'),
                5 => T_('5 Stars'),
            ],
            'theme_color' => $this->getThemeColors($held),
            'theme_name' => $this->getThemeNames(),
            'transcode' => [
                'never' => T_('Never'),
                'default' => T_('Default'),
                'always' => T_('Always'),
            ],
            'upload_catalog' => $this->getUploadCatalogs(),
            'webplayer_removeplayed' => $this->getRemovePlayedCounts(),
            default => null,
        };

        return $choices;
    }

    /**
     * The levels a preference can be gated on, with the zero that turns the feature off entirely
     *
     * @return array<int|string, string>
     */
    private function getAccessLevels(): array
    {
        return [AccessLevelEnum::DEFAULT->value => T_('Disabled')] + AccessLevelEnum::selectableDescriptions();
    }

    /**
     * @return array<int|string, string>
     */
    private function getApiVersions(): array
    {
        $choices = [0 => T_('Off')];
        foreach (Api::API_VERSIONS as $version) {
            /* HINT: API version number */
            $choices[$version] = sprintf(T_('Allow API%s Only'), $version);
        }

        return $choices;
    }

    /**
     * @return array<array-key, string>
     */
    private function getEncodeFormats(string $kind): array
    {
        $choices = ['' => T_('None')];
        foreach (Stream::get_available_encode_formats($kind) as $format) {
            $choices[$format] = $format;
        }

        return $choices;
    }

    /**
     * @return array<array-key, string>
     */
    private function getGeneratedArtTemplates(): array
    {
        $choices = ['auto' => T_('Match my theme')];
        foreach ($this->generatedArtService->getTemplates() as $template) {
            $choices[$template->getId()] = $template->getLabel();
        }

        return $choices;
    }

    /**
     * @return array<array-key, string>
     */
    private function getLocalplayControllers(): array
    {
        $choices = ['' => T_('None')];
        foreach (array_keys(LocalPlayTypeEnum::TYPE_MAPPING) as $controller) {
            if (LocalPlay::is_enabled($controller)) {
                $choices[$controller] = ucfirst($controller);
            }
        }

        return $choices;
    }

    /**
     * @param array<string, string> $held
     * @return array<array-key, string>
     */
    private function getPlayTypes(array $held): array
    {
        $choices = ['' => T_('None')];
        // these three are per-account preferences: an admin editing someone else must see theirs
        if ($this->isOn($held, 'allow_stream_playback')) {
            $choices['stream'] = T_('Stream');
        }

        if ($this->isOn($held, 'allow_democratic_playback')) {
            $choices['democratic'] = T_('Democratic');
        }

        if ($this->isOn($held, 'allow_localplay_playback')) {
            $choices['localplay'] = T_('Localplay');
        }

        $choices['web_player'] = T_('Web Player');

        return $choices;
    }

    /**
     * @return array<int|string, string>
     */
    private function getRemovePlayedCounts(): array
    {
        $choices = [
            0 => T_('Disabled'),
            1 => T_('Keep last played track'),
        ];
        foreach ([2, 3, 5, 10] as $count) {
            /* HINT: Keep (2|3|4|5|10) previous tracks */
            $choices[$count] = sprintf(T_('Keep %s previous tracks'), $count);
        }

        $choices[999] = T_('Remove all previous tracks');

        return $choices;
    }

    /**
     * Only the windows the statistics cache already holds: asking for any other one answers an empty ranking.
     *
     * @return array<array-key, string>
     */
    private function getStatisticsWindows(): array
    {
        $choices = [];
        foreach (Preference::cachedThresholds() as $days) {
            $choices[$days] = ($days === 0)
                ? T_('All time')
                : sprintf(nT_('%d day', '%d days', $days), $days);
        }

        return $choices;
    }

    /**
     * @param array<string, string> $held
     * @return array<array-key, string>
     */
    private function getThemeColors(array $held): array
    {
        // the colours on offer are those of the theme the subject uses, not the one the operator sees
        $theme = get_theme($held['theme_name'] ?? (string) AmpConfig::get('theme_name', 'reborn'));

        $choices = [];
        foreach ((array) ($theme['colors'] ?? []) as $color) {
            $label                       = (string) $color;
            $choices[strtolower($label)] = $label;
        }

        return $choices;
    }

    /**
     * @return array<array-key, string>
     */
    private function getThemeNames(): array
    {
        $choices = [];
        foreach (get_themes() as $theme) {
            $choices[(string) $theme['path']] = (string) $theme['name'];
        }

        return $choices;
    }

    /**
     * @return array<int|string, string>
     */
    private function getUploadCatalogs(): array
    {
        $catalogIds = [];
        foreach ($this->catalogRepository->getIds('music') as $catalogId) {
            if ($this->catalogRepository->findType($catalogId) === CatalogTypeEnum::LOCAL->value) {
                $catalogIds[] = $catalogId;
            }
        }

        // the stored `none` is -1: an empty value would be read back as catalog 0, which holds orphans
        return [-1 => T_('None')] + $this->catalogRepository->getNamesByIds($catalogIds);
    }

    /**
     * @return array<int|string, string>
     */
    private function getVolumes(): array
    {
        $choices = [];
        for ($step = 0; $step <= 10; $step++) {
            $choices[sprintf('%.2f', $step / 10)] = sprintf('%d%%', $step * 10);
        }

        return $choices;
    }

    /**
     * Falls back to the resolved configuration when the subject does not hold the preference at all.
     *
     * @param array<string, string> $held
     */
    private function isOn(array $held, string $name): bool
    {
        return array_key_exists($name, $held) ? $held[$name] === '1' : AmpConfig::get_bool($name);
    }
}
