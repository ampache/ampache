<?php

/**
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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class UtilityFactoryTest extends MockeryTestCase
{
    /** @var UserRepositoryInterface|MockInterface */
    private MockInterface $userRepository;

    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    private ?UtilityFactory $subject;

    public function setUp(): void
    {
        $this->userRepository  = $this->mock(UserRepositoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new UtilityFactory(
            $this->userRepository,
            $this->configContainer,
            $this->logger
        );
    }

    public function testCreateMailerReturnsInstance(): void
    {
        $this->assertInstanceOf(
            Mailer::class,
            $this->subject->createMailer()
        );
    }
}
