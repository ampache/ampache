<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=1);

namespace Ampache\Gui;

use Ampache\MockeryTestCase;

class TalTranslationServiceTest extends MockeryTestCase
{
    /** @var TalTranslationService|null */
    private TalTranslationService $subject;

    public function setUp(): void
    {
        $this->subject = new TalTranslationService();
    }

    public function testSetLanguageReturnsFirstEntry(): void
    {
        $lang1 = 'some-lang1';
        $lang2 = 'some-lang1';

        $this->assertSame(
            $lang1,
            $this->subject->setLanguage($lang1, $lang2)
        );
    }

    public function testUseDomainReturnsNull(): void
    {
        $this->assertNull(
            $this->subject->useDomain('som-domain')
        );
    }

    public function testTranslateReturnsValue(): void
    {
        $key = 'some-key';

        $this->assertSame(
            $key,
            $this->subject->translate(
                $key,
                false
            )
        );
    }
}
