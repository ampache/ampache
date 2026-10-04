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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreferenceCollectorTest extends TestCase
{
    private PreferenceChoiceProviderInterface&MockObject $choiceProvider;
    private ConfigContainerInterface&MockObject $configContainer;
    private PreferenceCollector $subject;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testAnEmptySubcategoryBecomesNull(): void
    {
        $row                = $this->row('popular_threshold', 'interface', type: 'integer', value: '25');
        $row['subcategory'] = '';

        $this->assertNull($this->itemOf($row)->subcategory);
    }

    public function testAnInheritedBitrateThatIsBlankCountsAsUnset(): void
    {
        // `Stream::get_player_bitrate()` answers 128000 for a blank rate, so the hint must not read empty
        $operator = $this->user(42);
        $override = $this->row('transcode_bitrate_webplayer', 'streaming', type: 'integer', value: '0');

        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? []
                : [$this->row('transcode_bitrate', 'streaming', type: 'integer', value: ''), $override]
        );

        $items = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['streaming'];

        $this->assertNull($items[1]->numberFallback, 'a blank base rate is no rate at all');
    }

    public function testANullValueBecomesAnEmptyString(): void
    {
        $this->assertSame('', $this->itemOf($this->row('popular_threshold', 'interface', value: null))->value);
    }

    public function testAnUnsetSecretIsReportedAsUnset(): void
    {
        $item = $this->itemOf($this->row('daap_pass', 'system', type: 'string', value: ''));

        $this->assertTrue($item->isSecret);
        $this->assertFalse($item->secretIsSet);
    }

    public function testAPreferenceAboveTheOperatorsLevelIsNotEditable(): void
    {
        $operator = $this->user(42, 25);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('show_lyrics', 'interface', 25),
            $this->row('download', 'options', 100),
        ]);

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertTrue($collected['interface'][0]->editable);
        $this->assertFalse($collected['options'][0]->editable);
    }

    public function testARuleReadingAServerOnlyPreferenceStillWarnsOnAnAccountPage(): void
    {
        // `subsonic_always_download` is warned about when `subsonic_backend` is off, and that one is a
        // `system` preference the account's own rows never carry
        $operator = $this->user(42);
        $own      = $this->row('subsonic_always_download', 'options', type: 'boolean', value: '1');

        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? [$this->row('subsonic_backend', 'system', type: 'boolean', value: '0'), $own]
                : [$own]
        );

        $item = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['options'][0];

        $this->assertNotNull($item->warning);
    }

    public function testASecretNeverCarriesItsValue(): void
    {
        $item = $this->itemOf($this->row('daap_pass', 'system', type: 'string', value: 'hunter2'), systemValue: 'hunter2');

        $this->assertTrue($item->isSecret);
        $this->assertSame('', $item->value);
        $this->assertNull($item->systemValue);
        $this->assertTrue($item->secretIsSet);
    }

    public function testAUserSubjectExcludesTheSystemCategory(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository
            ->method('getPreferenceRows')
            ->willReturnCallback(function (int $userId, ?string $category, bool $excludeSystem): array {
                if ($userId !== User::INTERNAL_SYSTEM_USER_ID) {
                    $this->assertTrue($excludeSystem, 'an account never edits the system category');
                }

                return [];
            });

        $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);
    }

    public function testDemoModeLocksEveryField(): void
    {
        $operator = $this->user(42, 100);
        $this->configContainer
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);
        $this->userRepository->method('getPreferenceRows')->willReturn([$this->row('show_lyrics', 'interface', 5)]);

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertFalse($collected['interface'][0]->editable);
    }

    public function testItAttachesHelpWhenTheCatalogueHasSome(): void
    {
        $item = $this->itemOf($this->row('popular_threshold', 'interface', type: 'integer', value: '25'));

        $this->assertNotNull($item->help);
        $this->assertNotSame('', $item->help->text);
    }

    public function testItAttachesNoHelpWhenTheCatalogueHasNone(): void
    {
        $this->assertNull($this->itemOf($this->row('lastfm_challenge', 'plugins', type: 'string', value: ''))->help);
    }

    public function testItAttachesTheSystemValueToEachItem(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? [$this->row('download', 'options', value: '0')]
                : [$this->row('download', 'options', value: '1')]
        );

        $item = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['options'][0];

        $this->assertSame('1', $item->value);
        $this->assertSame('0', $item->systemValue);
    }

    public function testItCarriesTheRowThrough(): void
    {
        $row  = $this->row('popular_threshold', 'interface', type: 'integer', value: '25');
        $item = $this->itemOf($row, systemValue: '10');

        $this->assertSame('popular_threshold', $item->name);
        $this->assertSame('Popular_threshold', $item->description);
        $this->assertSame(25, $item->level);
        $this->assertSame('25', $item->value);
        $this->assertSame('10', $item->systemValue);
        $this->assertTrue($item->editable);
        $this->assertSame(PreferenceType::INTEGER, $item->type);
    }

    public function testItGroupsByCategoryAndKeepsTheRepositoryOrder(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? [$this->row('download', 'options'), $this->row('show_lyrics', 'interface')]
                : [$this->row('download', 'options'), $this->row('show_lyrics', 'interface')]
        );

        $collected = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $this->assertSame(['options', 'interface'], array_keys($collected));
        $this->assertSame('download', $collected['options'][0]->name);
        $this->assertSame('show_lyrics', $collected['interface'][0]->name);
    }

    public function testItLeavesTheShippedDefaultUnsetForAnUnknownPreference(): void
    {
        $item = $this->itemOf($this->row('some_plugin_option', 'plugins', type: 'string', value: 'x'));

        $this->assertNull($item->shippedDefault);
        $this->assertFalse($item->differsFromShipped());
    }

    public function testItReadsTheShippedDefaultFromTheDefaultsCatalogue(): void
    {
        $this->assertSame('10', $this->itemOf($this->row('popular_threshold', 'interface', type: 'integer', value: '25'))->shippedDefault);
    }

    public function testItResolvesChoiceListsForTheSubject(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('transcode', 'streaming', 25, 'default', 'string'),
        ]);

        $this->choiceProvider
            ->method('find')
            ->with('transcode')
            ->willReturn(['never' => 'Never', 'default' => 'Default', 'always' => 'Always']);

        $item = $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)['streaming'][0];

        $this->assertSame(['never' => 'Never', 'default' => 'Default', 'always' => 'Always'], $item->choices);
    }

    public function testTheServerSubjectReadsTheSharedRowAndKeepsTheSystemCategory(): void
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository
            ->expects($this->once())
            ->method('getPreferenceRows')
            ->with(User::INTERNAL_SYSTEM_USER_ID, null, false)
            ->willReturn([$this->row('site_title', 'system', 100, 'Ampache', 'string')]);

        $collected = $this->subject->collect(PreferenceSubject::serverPreferences($operator), $operator);

        $this->assertArrayHasKey('system', $collected);
        $this->assertNull($collected['system'][0]->systemValue, 'the server is not compared against itself');
    }

    protected function setUp(): void
    {
        $this->userRepository  = $this->createMock(UserRepositoryInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->choiceProvider  = $this->createMock(PreferenceChoiceProviderInterface::class);
        $this->subject         = new PreferenceCollector(
            $this->userRepository,
            new PreferenceHelpCatalog(),
            $this->choiceProvider,
            new PreferencePrerequisiteCatalog(),
            $this->configContainer,
        );
    }

    /**
     * The single item the collector builds from one row, with the server row it is compared against.
     *
     * @param array<string, mixed> $row
     */
    private function itemOf(array $row, ?string $systemValue = null): PreferenceItem
    {
        $operator = $this->user(42);
        $this->configContainer->method('isFeatureEnabled')->willReturn(false);
        $this->userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === User::INTERNAL_SYSTEM_USER_ID)
                ? (($systemValue === null) ? [] : [['name' => $row['name'], 'value' => $systemValue] + $row])
                : [$row]
        );

        return $this->subject->collect(PreferenceSubject::ownPreferences($operator), $operator)[$row['category']][0];
    }

    /** @return array<string, mixed> */
    private function row(string $name, string $category, int $level = 25, ?string $value = '1', string $type = 'boolean'): array
    {
        return [
            'name' => $name,
            'description' => ucfirst($name),
            'category' => $category,
            'subcategory' => null,
            'type' => $type,
            'level' => $level,
            'value' => $value,
        ];
    }

    private function user(int $id, int $access = 100): User
    {
        $user           = $this->createMock(User::class);
        $user->access   = $access;
        $user->fullname = 'u' . $id;
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
