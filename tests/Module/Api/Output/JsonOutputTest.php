<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Api\Output;

use Ampache\MockeryTestCase;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Shoutbox;
use Ampache\Model\User;
use Mockery\MockInterface;

class JsonOutputTest extends MockeryTestCase
{
    /** @var MockInterface|null|ModelFactoryInterface */
    private ?MockInterface $modelFactory;

    private ?JsonOutput $subject;

    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new JsonOutput(
            $this->modelFactory
        );
    }

    public function testShoutsReturnsResult(): void
    {
        $shoutId  = 666;
        $userId   = '42';
        $date     = 'some-date';
        $text     = 'some-text';
        $username = 'some-username';
        $result   = [
            'shout' => [[
                'id' => (string) $shoutId,
                'date' => $date,
                'text' => $text,
                'user' => [
                    'id' => $userId,
                    'username' => $username
                ]
            ]]
        ];

        $shout = $this->mock(Shoutbox::class);
        $user  = $this->mock(User::class);

        $shout->date    = $date;
        $shout->text    = $text;
        $shout->user    = $userId;
        $user->username = $username;

        $this->modelFactory->shouldReceive('createShoutbox')
            ->with($shoutId)
            ->once()
            ->andReturn($shout);
        $this->modelFactory->shouldReceive('createUser')
            ->with((int) $userId)
            ->once()
            ->andReturn($user);

        $this->assertSame(
            json_encode($result, JSON_PRETTY_PRINT),
            $this->subject->shouts([$shoutId])
        );
    }
}
