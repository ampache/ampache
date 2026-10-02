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
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreferenceExporterTest extends TestCase
{
    private PreferenceExporter $subject;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testAHostileUsernameCannotEscapeTheFileName(): void
    {
        $name = $this->subject->fileName(PreferenceSubject::ownPreferences($this->user(42, '../../etc/passwd" ;')));

        $this->assertStringNotContainsString('/', $name);
        $this->assertStringNotContainsString('"', $name);
        $this->assertMatchesRegularExpression('/^ampache-preferences_[A-Za-z0-9._-]+_42_/', $name);
    }

    public function testASecretIsNeverWrittenToTheFileButIsNamedAsMissing(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('download', '1'),
            $this->row('daap_pass', 'hunter2'),
        ]);

        $export = $this->subject->export(PreferenceSubject::ownPreferences($user), $user);

        $this->assertArrayNotHasKey('daap_pass', $export['preferences']);
        $this->assertSame(['daap_pass'], $export['omitted_secrets'], 'a reader must know the file is incomplete');
        $this->assertStringNotContainsString('hunter2', (string) json_encode($export));
    }

    public function testTheFileNameCarriesWhoWhenAndWhichVersion(): void
    {
        $name = $this->subject->fileName(PreferenceSubject::ownPreferences($this->user(42, 'UiDemo')));

        $this->assertStringContainsString('UiDemo', $name);
        $this->assertStringContainsString('_42_', $name);
        $this->assertStringContainsString('v8.2.0', $name);
        $this->assertMatchesRegularExpression('/_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.json$/', $name);
    }

    public function testTheFileSaysWhichInstallAndWhichSchemaProducedIt(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([]);

        $export = $this->subject->export(PreferenceSubject::ownPreferences($user), $user);

        $this->assertSame('ampache-preferences', $export['format']);
        $this->assertSame('8.2.0', $export['ampache_version']);
        $this->assertSame('810015', $export['schema_version'], 'a preference set only means something against its schema');
        $this->assertSame('https://play.example.net', $export['site']['url']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $export['exported_at']);
    }

    public function testTheFileSaysWhoseConfigurationItIsAndWhoTookIt(): void
    {
        $operator = $this->user(1, 'admin');
        $target   = $this->user(7, 'bituur');
        $this->userRepository->method('getPreferenceRows')->willReturn([]);

        $export = $this->subject->export(PreferenceSubject::otherUser($target, $operator), $operator);

        $this->assertSame('account', $export['subject']['kind']);
        $this->assertSame(7, $export['subject']['user_id']);
        $this->assertSame('bituur', $export['subject']['username']);
        $this->assertSame(1, $export['exported_by']['user_id']);
    }

    public function testThePreferencesAreSortedSoTwoExportsCompareCleanly(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('zebra', '1'),
            $this->row('alpha', '2'),
        ]);

        $export = $this->subject->export(PreferenceSubject::ownPreferences($user), $user);

        $this->assertSame(['alpha', 'zebra'], array_keys($export['preferences']));
    }

    public function testTheServerExportNamesNoUser(): void
    {
        $operator = $this->user(1, 'admin');
        $this->userRepository->method('getPreferenceRows')->willReturn([]);

        $export = $this->subject->export(PreferenceSubject::serverPreferences($operator), $operator);

        $this->assertSame('server', $export['subject']['kind']);
        $this->assertSame(-1, $export['subject']['user_id']);
        $this->assertNull($export['subject']['username']);
    }

    public function testTheServerFileIsNamedAfterTheServer(): void
    {
        $name = $this->subject->fileName(PreferenceSubject::serverPreferences($this->user(1, 'admin')));

        $this->assertStringContainsString('server', $name);
        $this->assertStringContainsString('_-1_', $name);
    }

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $config = $this->createMock(ConfigContainerInterface::class);
        $config->method('isFeatureEnabled')->willReturn(false);
        $config->method('getWebPath')->willReturn('https://play.example.net');
        $config->method('get')->willReturnCallback(static fn(string $key): string => match ($key) {
            'version' => '8.2.0',
            'site_title' => 'Example',
            default => '',
        });

        $updateInfo = $this->createMock(UpdateInfoRepositoryInterface::class);
        $updateInfo->method('getValueByKey')->willReturn('810015');

        $this->subject = new PreferenceExporter(
            new PreferenceCollector(
                $this->userRepository,
                new PreferenceHelpCatalog(),
                $this->createMock(PreferenceChoiceProviderInterface::class),
                new PreferencePrerequisiteCatalog(),
                $config,
            ),
            $config,
            $updateInfo,
        );
    }

    /** @return array<string, mixed> */
    private function row(string $name, string $value): array
    {
        return [
            'name' => $name,
            'description' => $name,
            'category' => 'options',
            'subcategory' => null,
            'type' => 'string',
            'level' => 25,
            'value' => $value,
        ];
    }

    private function user(int $id = 42, string $name = 'UiDemo'): User
    {
        $user           = $this->createMock(User::class);
        $user->fullname = $name;
        $user->username = $name;
        $user->access   = 100;
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
