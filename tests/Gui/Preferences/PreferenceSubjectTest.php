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

use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;

class PreferenceSubjectTest extends TestCase
{
    public function testAnAdminOpeningTheirOwnAccountIsNotTreatedAsSomeoneElse(): void
    {
        $subject = PreferenceSubject::otherUser($this->user(42, 'Zoé'), $this->user(42, 'Zoé'));

        $this->assertTrue($subject->isSelf);
    }

    public function testAnotherUserIsNeitherSelfNorTheServer(): void
    {
        $subject = PreferenceSubject::otherUser($this->user(7, 'bituur'), $this->user(42, 'Zoé'));

        $this->assertSame(7, $subject->userId);
        $this->assertSame('bituur', $subject->label);
        $this->assertFalse($subject->isSelf);
        $this->assertFalse($subject->isServer, 'level and apply-to-all belong to the server only');
    }

    public function testOwnPreferencesTargetTheUserAndNeedNoAdmin(): void
    {
        $subject = PreferenceSubject::ownPreferences($this->user(42, 'Zoé'));

        $this->assertSame(42, $subject->userId);
        $this->assertSame('Zoé', $subject->label);
        $this->assertTrue($subject->isSelf);
        $this->assertFalse($subject->isServer);
    }

    public function testServerPreferencesTargetTheSharedRow(): void
    {
        $operator = $this->user(42, 'Zoé');
        $subject  = PreferenceSubject::serverPreferences($operator);

        $this->assertSame(-1, $subject->userId);
        $this->assertSame(User::INTERNAL_SYSTEM_USER_ID, $subject->userId);
        $this->assertTrue($subject->isServer);
        $this->assertFalse($subject->isSelf);
        $this->assertSame($operator, $subject->user, 'choice lists are resolved against the operator');
    }

    private function user(int $id, string $fullname): User
    {
        $user           = $this->createMock(User::class);
        $user->fullname = $fullname;
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
