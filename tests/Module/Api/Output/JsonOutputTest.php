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
use Ampache\Model\Tag;
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

    public function testUserReturnsFullInfo(): void
    {
        $user = $this->mock(User::class);

        $id              = 666;
        $username        = 'some-username';
        $fullname        = 'some-fullname';
        $apikey          = 'some-auth';
        $email           = 'some-email';
        $access          = 42;
        $fullname_public = 21;
        $validation      = 'some-validation';
        $disabled        = 33;
        $create_date     = 123;
        $last_seen       = 456;
        $website         = 'some-website';
        $state           = 'some-state';
        $city            = 'some-city';

        $user->id              = (string) $id;
        $user->username        = $username;
        $user->fullname        = $fullname;
        $user->apikey          = $apikey;
        $user->email           = $email;
        $user->access          = (string) $access;
        $user->fullname_public = (string) $fullname_public;
        $user->validation      = (string) $validation;
        $user->disabled        = (string) $disabled;
        $user->create_date     = (string) $create_date;
        $user->last_seen       = (string) $last_seen;
        $user->website         = $website;
        $user->state           = $state;
        $user->city            = $city;

        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            json_encode(['user' => [
                'id' => (string) $id,
                'username' => $username,
                'auth' => $apikey,
                'email' => $email,
                'access' => $access,
                'fullname_public' => $fullname_public,
                'validation' => $validation,
                'disabled' => $disabled,
                'create_date' => $create_date,
                'last_seen' => $last_seen,
                'website' => $website,
                'state' => $state,
                'city' => $city,
                'fullname' => $fullname,
            ]], JSON_PRETTY_PRINT),
            $this->subject->user($user, true)
        );
    }

    public function testUserReturnsRestrictedInfo(): void
    {
        $user = $this->mock(User::class);

        $id              = 666;
        $username        = 'some-username';
        $create_date     = 123;
        $last_seen       = 456;
        $website         = 'some-website';
        $state           = 'some-state';
        $city            = 'some-city';

        $user->id              = (string) $id;
        $user->username        = $username;
        $user->create_date     = (string) $create_date;
        $user->last_seen       = (string) $last_seen;
        $user->website         = $website;
        $user->state           = $state;
        $user->city            = $city;

        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $this->assertSame(
            json_encode(['user' => [
                'id' => (string) $id,
                'username' => $username,
                'create_date' => $create_date,
                'last_seen' => $last_seen,
                'website' => $website,
                'state' => $state,
                'city' => $city,
            ]], JSON_PRETTY_PRINT),
            $this->subject->user($user, false)
        );
    }

    public function testGenresReturnsWrappedList(): void
    {
        $tagId           = 666;
        $albumCount      = 111;
        $artistCount     = 222;
        $songCount       = 333;
        $videoCount      = 444;
        $playlistCount   = 555;
        $liveStreamCount = 666;
        $name            = 'some-name';

        $tag = $this->mock(Tag::class);

        $this->modelFactory->shouldReceive('createTag')
            ->with($tagId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'album' => (string) $albumCount,
                'artist' => (string) $artistCount,
                'song' => (string) $songCount,
                'video' => (string) $videoCount,
                'playlist' => (string) $playlistCount,
                'live_stream' => (string) $liveStreamCount,
            ]);

        $tag->name = $name;

        $this->assertSame(
            json_encode([
                'genre' => [[
                    'id' => (string) $tagId,
                    'name' => $name,
                    'albums' => $albumCount,
                    'artists' => $artistCount,
                    'songs' => $songCount,
                    'videos' => $videoCount,
                    'playlists' => $playlistCount,
                    'live_streams' => $liveStreamCount
                ]]
            ], JSON_PRETTY_PRINT),
            $this->subject->genres([$tagId])
        );
    }

    public function testGenresReturnsUnWrappedSingleItem(): void
    {
        $tagId           = 666;
        $albumCount      = 111;
        $artistCount     = 222;
        $songCount       = 333;
        $videoCount      = 444;
        $playlistCount   = 555;
        $liveStreamCount = 666;
        $name            = 'some-name';

        $tag = $this->mock(Tag::class);

        $this->modelFactory->shouldReceive('createTag')
            ->with($tagId)
            ->once()
            ->andReturn($tag);

        $tag->shouldReceive('count')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'album' => (string) $albumCount,
                'artist' => (string) $artistCount,
                'song' => (string) $songCount,
                'video' => (string) $videoCount,
                'playlist' => (string) $playlistCount,
                'live_stream' => (string) $liveStreamCount,
            ]);

        $tag->name = $name;

        $this->assertSame(
            json_encode([
                'id' => (string) $tagId,
                'name' => $name,
                'albums' => $albumCount,
                'artists' => $artistCount,
                'songs' => $songCount,
                'videos' => $videoCount,
                'playlists' => $playlistCount,
                'live_streams' => $liveStreamCount

            ], JSON_PRETTY_PRINT),
            $this->subject->genres([$tagId, 999], false, 1)
        );
    }
}
