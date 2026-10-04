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
use Ampache\Module\Art\Generated\Template\TemplateInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\MetadataFieldRepositoryInterface;
use Ampache\Repository\Model\User;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreferenceChoiceProviderTest extends TestCase
{
    /** @var list<string> */
    private const array PLAYBACK_SWITCHES = [
        'allow_stream_playback',
        'allow_democratic_playback',
        'allow_localplay_playback',
    ];
    /** @var list<string> every preference whose list is fixed and needs neither the database nor the disk */
    private const array SELF_CONTAINED_NAMES = [
        'album_sort',
        'api_force_version',
        'jp_volume',
        'localplay_level',
        'play_type',
        'playlist_method',
        'playlist_type',
        'ratingmatch_stars',
        'transcode',
        'upload_access_level',
        'upload_catalog',
        'webplayer_removeplayed',
    ];

    private CatalogRepositoryInterface&MockObject $catalogRepository;
    private GeneratedArtServiceInterface&MockObject $generatedArtService;
    private MetadataFieldRepositoryInterface&MockObject $metadataFieldRepository;

    /** @var array<string, mixed> */
    private array $playbackConfig = [];

    private PreferenceChoiceProvider $subject;
    private PreferenceSubject $subjectOf;
    private User&MockObject $user;

    public function testAlbumSortOffersEveryOrderTheBrowseSupports(): void
    {
        $this->assertSame(
            ['default', 'year_asc', 'year_desc', 'name_asc', 'name_desc'],
            array_keys($this->choicesOf('album_sort'))
        );
    }

    public function testAnUnknownPreferenceIsLeftAsAFreeField(): void
    {
        $this->assertNull($this->subject->find('lastfm_challenge', $this->subjectOf));
    }

    public function testAPreferenceTheSubjectDoesNotHoldFallsBackToTheConfiguration(): void
    {
        AmpConfig::set('allow_stream_playback', true, true);

        $choices = (array) $this->subject->find('play_type', $this->subjectOf, []);

        $this->assertArrayHasKey('stream', $choices);
    }

    public function testDisabledCustomMetadataFieldsAreTheFieldsTheRepositoryKnows(): void
    {
        $this->metadataFieldRepository->method('getPropertyList')
            ->willReturn($this->fields());

        $this->assertSame([4 => 'Mood', 9 => 'BPM'], $this->choicesOf('disabled_custom_metadata_fields'));
    }

    public function testEveryApiVersionTheServerKnowsIsOffered(): void
    {
        $choices = $this->choicesOf('api_force_version');

        foreach (Api::API_VERSIONS as $version) {
            $this->assertArrayHasKey(
                $version,
                $choices,
                sprintf('API version %d has no option: an install set to it would be saved back as 0', $version)
            );
        }
    }

    public function testEveryFixedListIsFilledWithNonEmptyLabels(): void
    {
        foreach (self::SELF_CONTAINED_NAMES as $name) {
            $choices = $this->choicesOf($name);

            $this->assertNotSame([], $choices, sprintf('`%s` offers no choice at all', $name));
            foreach ($choices as $value => $label) {
                $this->assertIsString($label, sprintf('`%s` labels the option `%s` with something that is not a string', $name, $value));
                $this->assertNotSame('', trim($label), sprintf('`%s` labels the option `%s` with nothing', $name, $value));
            }
        }
    }

    public function testGeneratedArtTemplatesAreLedByTheAutomaticOne(): void
    {
        $this->generatedArtService->method('getTemplates')
            ->willReturn([$this->template('mosaic', 'Mosaic'), $this->template('stripes', 'Stripes')]);

        $this->assertSame(
            ['auto', 'mosaic', 'stripes'],
            array_keys($this->choicesOf('generated_art_template')),
            'the theme-matching choice has to lead the templates'
        );
    }

    public function testGuestIsOfferedAsALocalplayLevel(): void
    {
        $choices = $this->choicesOf('localplay_level');

        $this->assertArrayHasKey(5, $choices, 'the Guest level is storable, so it has to be offered');
        $this->assertSame([0, 5, 25, 50, 75, 100], array_keys($choices));
    }

    public function testGuestIsOfferedAsAnUploadAccessLevel(): void
    {
        $choices = $this->choicesOf('upload_access_level');

        $this->assertArrayHasKey(5, $choices, 'the Guest level is storable, so it has to be offered');
        $this->assertSame([0, 5, 25, 50, 75, 100], array_keys($choices));
    }

    public function testPlaylistMethodOffersEveryWayOfAddingToTheQueue(): void
    {
        $this->assertSame(
            ['send', 'send_clear', 'clear', 'default'],
            array_keys($this->choicesOf('playlist_method'))
        );
    }

    public function testPlaylistTypeOffersEveryExportFormat(): void
    {
        $this->assertSame(
            ['m3u', 'simple_m3u', 'pls', 'asx', 'ram', 'xspf'],
            array_keys($this->choicesOf('playlist_type'))
        );
    }

    public function testPlayTypeHidesThePlaybackModesThatAreTurnedOff(): void
    {
        $this->setPlaybackSwitches(false);

        $this->assertSame(['', 'web_player'], array_keys($this->choicesOf('play_type')));
    }

    public function testPlayTypeOffersEveryPlaybackModeThatIsTurnedOn(): void
    {
        $this->setPlaybackSwitches(true);

        $this->assertSame(
            ['', 'stream', 'democratic', 'localplay', 'web_player'],
            array_keys($this->choicesOf('play_type'))
        );
    }

    public function testRatingMatchStarsOffersEveryStarCountAndNoMatchingAtAll(): void
    {
        $this->assertSame([0, 1, 2, 3, 4, 5], array_keys($this->choicesOf('ratingmatch_stars')));
    }

    public function testTheApiVersionListIsLedByTurningItOff(): void
    {
        $expected = array_merge([0], Api::API_VERSIONS);

        $this->assertSame(
            $expected,
            array_keys($this->choicesOf('api_force_version')),
            'the list has to be `Off` plus exactly the versions the server answers'
        );
    }

    /**
     * An admin editing someone else must see what that account is allowed, not what they are.
     */
    public function testThePlaybackTypesFollowTheSubjectAndNotTheOperator(): void
    {
        AmpConfig::set('allow_democratic_playback', true, true);

        $subjectAllows = $this->subject->find('play_type', $this->subjectOf, [
            'allow_stream_playback' => '1',
            'allow_democratic_playback' => '0',
            'allow_localplay_playback' => '0',
        ]);

        $this->assertArrayHasKey('stream', (array) $subjectAllows);
        $this->assertArrayNotHasKey('democratic', (array) $subjectAllows, 'the operator allows it, the subject does not');
        $this->assertArrayHasKey('web_player', (array) $subjectAllows, 'the web player is always offered');
    }

    public function testTheUploadCatalogListIsLedByAMinusOneMeaningNone(): void
    {
        $this->catalogRepository->method('getIds')->willReturn([3, 7]);
        $this->catalogRepository->method('findType')->willReturnMap([[3, 'local'], [7, 'beets']]);
        $this->catalogRepository->method('getNamesByIds')->willReturn([3 => 'Music']);

        $choices = $this->choicesOf('upload_catalog');

        $this->assertSame([-1, 3], array_keys($choices), 'the `none` option has to lead the catalogs');
        $this->assertArrayNotHasKey('', $choices, 'an empty `none` would be read back as catalog 0, which holds orphans');
    }

    public function testTranscodeOffersNeverDefaultAndAlways(): void
    {
        $this->assertSame(['never', 'default', 'always'], array_keys($this->choicesOf('transcode')));
    }

    public function testVolumesRunFromNoneToFullInTenthsKeyedWithTwoDecimals(): void
    {
        $choices = $this->choicesOf('jp_volume');

        $this->assertSame(
            ['0.00', '0.10', '0.20', '0.30', '0.40', '0.50', '0.60', '0.70', '0.80', '0.90', '1.00'],
            array_keys($choices),
            'the rendered control matches the stored value on this exact shape'
        );
        $this->assertSame('0%', $choices['0.00']);
        $this->assertSame('100%', $choices['1.00']);
    }

    public function testWebplayerRemovePlayedOffersEveryHistoryLengthAndKeepingNone(): void
    {
        $this->assertSame(
            [0, 1, 2, 3, 5, 10, 999],
            array_keys($this->choicesOf('webplayer_removeplayed'))
        );
    }

    protected function setUp(): void
    {
        $this->catalogRepository       = $this->createMock(CatalogRepositoryInterface::class);
        $this->generatedArtService     = $this->createMock(GeneratedArtServiceInterface::class);
        $this->metadataFieldRepository = $this->createMock(MetadataFieldRepositoryInterface::class);
        $this->user                    = $this->createMock(User::class);
        $this->user->fullname          = 'u';
        $this->subjectOf               = PreferenceSubject::ownPreferences($this->user);

        $this->subject = new PreferenceChoiceProvider(
            $this->catalogRepository,
            $this->generatedArtService,
            $this->metadataFieldRepository,
        );

        foreach (self::PLAYBACK_SWITCHES as $switch) {
            $this->playbackConfig[$switch] = AmpConfig::get($switch);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->playbackConfig as $switch => $value) {
            AmpConfig::set($switch, $value, true);
        }
    }

    /** @return array<array-key, string> */
    private function choicesOf(string $name): array
    {
        $choices = $this->subject->find($name, $this->subjectOf);

        $this->assertNotNull($choices, sprintf('`%s` is expected to have a fixed list of choices', $name));

        return $choices;
    }

    /** @return Generator<int, string> */
    private function fields(): Generator
    {
        yield 4 => 'Mood';
        yield 9 => 'BPM';
    }

    private function setPlaybackSwitches(bool $enabled): void
    {
        foreach (self::PLAYBACK_SWITCHES as $switch) {
            AmpConfig::set($switch, $enabled, true);
        }
    }

    private function template(string $id, string $label): TemplateInterface
    {
        $template = $this->createMock(TemplateInterface::class);
        $template->method('getId')->willReturn($id);
        $template->method('getLabel')->willReturn($label);

        return $template;
    }
}
