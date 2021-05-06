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

namespace Ampache\Module\User\Activity;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\UseractivityInterface;
use Mockery\MockInterface;

class UserActivityRendererTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface */
    private MockInterface $configContainer;

    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    private UserActivityRenderer $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new UserActivityRenderer(
            $this->configContainer,
            $this->modelFactory
        );
    }

    public function testShowReturnsEmptyStringIfNotEnabled(): void
    {
        $activity = $this->mock(UseractivityInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            '',
            $this->subject->show($activity)
        );
    }

    /**
     * @dataProvider actionDataProvider
     */
    public function testShowReturnsValue(
        string $action,
        string $actionDescription
    ): void {
        $activity = $this->mock(UseractivityInterface::class);
        $user     = $this->mock(User::class);
        $libItem  = $this->mock(library_item::class);

        $userId     = 666;
        $date       = 123545;
        $link       = 'some-link';
        $objectId   = 42;
        $objectType = 33;
        $user_link  = 'some-user-link';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createUser')
            ->with($userId)
            ->once()
            ->andReturn($user);
        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($objectType, $objectId)
            ->once()
            ->andReturn($libItem);

        $libItem->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $libItem->f_link = $link;

        $user->shouldReceive('format')
            ->withNoArgs()
            ->once();

        $user->f_link = $user_link;

        $activity->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);
        $activity->shouldReceive('getObjectId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);
        $activity->shouldReceive('getObjectType')
            ->withNoArgs()
            ->once()
            ->andReturn($objectType);
        $activity->shouldReceive('getAction')
            ->withNoArgs()
            ->once()
            ->andReturn($action);
        $activity->shouldReceive('getActivityDate')
            ->withNoArgs()
            ->once()
            ->andReturn($date);

        $this->assertSame(
            sprintf(
                '<div>%s %s %s %s</div>',
                get_datetime($date),
                $user_link,
                $actionDescription,
                $link
            ),
            $this->subject->show($activity)
        );
    }

    public function actionDataProvider(): array
    {
        return [
            ['shout', 'commented on'],
            ['upload', 'uploaded'],
            ['play', 'played'],
            ['userflag', 'favorited'],
            ['follow', 'started to follow'],
            ['foobar', 'did something on'],
        ];
    }
}
