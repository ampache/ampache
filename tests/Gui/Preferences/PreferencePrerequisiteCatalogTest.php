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

use Ampache\Module\System\Preference;
use PHPUnit\Framework\TestCase;

class PreferencePrerequisiteCatalogTest extends TestCase
{
    /** Preferences a plugin installs, so `Preference::DEFAULTS` never lists them */
    private const array PLUGIN_PREFERENCES = [
        'personalfav_display',
        'personalfav_playlist',
        'personalfav_smartlist',
        'ratingmatch_stars',
        'ratingmatch_write_tags',
        'stream_control_bandwidth_days',
        'stream_control_bandwidth_max',
        'stream_control_hits_days',
        'stream_control_hits_max',
        'stream_control_time_days',
        'stream_control_time_max',
    ];

    private PreferencePrerequisiteCatalog $subject;

    public function testASettingFromTheConfigFileCanCancelAPreference(): void
    {
        $this->assertNotNull($this->subject->find('upload_script', [
            'upload_script' => '/usr/local/bin/tag.sh',
            'config:allow_upload_scripts' => '',
        ]));
        $this->assertNull($this->subject->find('upload_script', [
            'upload_script' => '/usr/local/bin/tag.sh',
            'config:allow_upload_scripts' => '1',
        ]));
    }

    public function testASubjectMissingThePreferenceEntirelyIsNeverWarned(): void
    {
        $this->assertNull(
            $this->subject->find('share_expire', []),
            'an account that does not hold `share` must not be told anything about it'
        );
    }

    public function testATranscodingSetupThatIsOffWarnsOnTheFormatAndTheBitrate(): void
    {
        $values = ['transcode' => 'never', 'encode_target' => 'opus', 'transcode_bitrate' => '128000'];

        $this->assertNotNull($this->subject->find('encode_target', $values));
        $this->assertNotNull($this->subject->find('transcode_bitrate', $values));
        $this->assertNull($this->subject->find('transcode', $values), 'the switch itself is not the problem');
    }

    public function testAWorkingTranscodingSetupWarnsAboutNothing(): void
    {
        $values = [
            'transcode' => 'default',
            'encode_target' => 'opus',
            'encode_player_webplayer_target' => '',
            'transcode_bitrate' => '128000',
            'transcode_bitrate_webplayer' => '0',
            'transcode_bitrate_api' => '0',
        ];

        $this->assertNull($this->subject->find('encode_target', $values));
        $this->assertNull($this->subject->find('transcode_bitrate', $values));
    }

    public function testEveryConditionNamesAPreferenceThatExistsOrAConfigSetting(): void
    {
        foreach ($this->subject->all() as $rule) {
            foreach ($rule->when as $condition) {
                if (PreferencePrerequisite::isConfigKey($condition[0])) {
                    continue;
                }

                $this->assertTrue(
                    $this->isKnown($condition[0]),
                    sprintf('the rule on `%s` waits for `%s`, which does not exist', $rule->preference, $condition[0])
                );
            }
        }
    }

    public function testEveryRuleSaysSomething(): void
    {
        foreach ($this->subject->all() as $rule) {
            $this->assertNotSame('', $rule->text, sprintf('the rule on `%s` has no text', $rule->preference));
            $this->assertNotSame([], $rule->when, sprintf('the rule on `%s` would never fire', $rule->preference));
        }
    }

    public function testEveryRuleWarnsOnAPreferenceThatExists(): void
    {
        foreach ($this->subject->all() as $rule) {
            $this->assertTrue(
                $this->isKnown($rule->preference),
                sprintf('the rule warns on `%s`, which no install would ever have', $rule->preference)
            );
        }
    }

    public function testTheConfigSettingsAreCollectedForTheCaller(): void
    {
        $keys = $this->subject->configKeys();

        $this->assertContains('statistical_graphs', $keys);
        $this->assertContains('delete_from_disk', $keys);
        $this->assertContains('allow_upload_scripts', $keys);
        $this->assertSame($keys, array_unique($keys), 'the caller reads each setting once');
        foreach ($keys as $key) {
            $this->assertStringNotContainsString(PreferencePrerequisite::CONFIG_PREFIX, $key, 'the prefix is stripped');
        }
    }

    public function testTheDefaultRatingMatchSetupIsCaught(): void
    {
        $this->assertNotNull(
            $this->subject->find('ratingmatch_write_tags', ['ratingmatch_write_tags' => '1', 'ratingmatch_stars' => '0']),
            'the shipped minimum of 0 turns the whole sync off'
        );
        $this->assertNull(
            $this->subject->find('ratingmatch_write_tags', ['ratingmatch_write_tags' => '1', 'ratingmatch_stars' => '3'])
        );
    }

    public function testUploadsAllowedWithNoCatalogIsCaught(): void
    {
        $this->assertNotNull(
            $this->subject->find('upload_catalog', ['allow_upload' => '1', 'upload_catalog' => '-1']),
            'this is the state of a fresh install the moment uploads are switched on'
        );
        $this->assertNull($this->subject->find('upload_catalog', ['allow_upload' => '1', 'upload_catalog' => '3']));
        $this->assertNull($this->subject->find('upload_catalog', ['allow_upload' => '0', 'upload_catalog' => '-1']));
    }

    protected function setUp(): void
    {
        $this->subject = new PreferencePrerequisiteCatalog();
    }

    private function isKnown(string $name): bool
    {
        return array_key_exists($name, Preference::DEFAULTS) || in_array($name, self::PLUGIN_PREFERENCES, true);
    }
}
