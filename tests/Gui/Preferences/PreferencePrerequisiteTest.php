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

use PHPUnit\Framework\TestCase;

class PreferencePrerequisiteTest extends TestCase
{
    public function testAnUnknownOperatorNeverFiresRatherThanGuessing(): void
    {
        $this->assertFalse($this->rule([['a', 'sometimes', '1']])->holds(['a' => '1']));
    }

    public function testAPreferenceTheSubjectDoesNotHaveNeverTriggersAWarning(): void
    {
        $rule = $this->rule([['absent', PreferencePrerequisite::IS_OFF]]);

        $this->assertFalse(
            $rule->holds([]),
            'a preference that is not there cannot be making anything pointless'
        );
    }

    public function testARuleWithNoConditionNeverFires(): void
    {
        $this->assertFalse($this->rule([])->holds(['a' => '1']));
    }

    public function testAValueCanBeComparedExactly(): void
    {
        $is    = $this->rule([['transcode', PreferencePrerequisite::IS, 'never']]);
        $isNot = $this->rule([['transcode', PreferencePrerequisite::IS_NOT, 'never']]);

        $this->assertTrue($is->holds(['transcode' => 'never']));
        $this->assertFalse($is->holds(['transcode' => 'default']));
        $this->assertTrue($isNot->holds(['transcode' => 'default']));
    }

    public function testEmptyCoversTheSentinelsAmpacheUsesForUnset(): void
    {
        $rule = $this->rule([['a', PreferencePrerequisite::IS_EMPTY]]);

        $this->assertTrue($rule->holds(['a' => '']));
        $this->assertTrue($rule->holds(['a' => '0']));
        $this->assertTrue($rule->holds(['a' => '-1']), 'no catalogue chosen is stored as -1');
        $this->assertFalse($rule->holds(['a' => '7']));
    }

    public function testEveryConditionHasToHold(): void
    {
        $rule = $this->rule([['a', PreferencePrerequisite::IS_ON], ['b', PreferencePrerequisite::IS_OFF]]);

        $this->assertTrue($rule->holds(['a' => '1', 'b' => '0']));
        $this->assertFalse($rule->holds(['a' => '1', 'b' => '1']), 'one condition failing is enough');
        $this->assertFalse($rule->holds(['a' => '0', 'b' => '0']));
    }

    public function testOnAndOffReadAmpachesOwnSpelling(): void
    {
        $on  = $this->rule([['a', PreferencePrerequisite::IS_ON]]);
        $off = $this->rule([['a', PreferencePrerequisite::IS_OFF]]);

        $this->assertTrue($on->holds(['a' => '1']));
        $this->assertFalse($on->holds(['a' => '0']));
        $this->assertFalse($on->holds(['a' => '']));
        $this->assertTrue($off->holds(['a' => '0']));
        $this->assertTrue($off->holds(['a' => '']), 'an unset boolean is off');
    }

    private function rule(array $when): PreferencePrerequisite
    {
        return new PreferencePrerequisite('subject', $when, 'because');
    }
}
