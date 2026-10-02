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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JellyfinServerIdTest extends TestCase
{
    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;

    public function testAFreshIdIsGeneratedAndKept(): void
    {
        $this->updateInfoRepository->method('getValueByKey')->willReturn(null);
        $this->updateInfoRepository->expects(static::once())
            ->method('setValue')
            ->with(UpdateInfoEnum::JELLYFIN_SERVER_ID, static::isType('string'));

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $this->subject()->get()
        );
    }

    public function testAStoredIdIsKeptAndNotRewritten(): void
    {
        $this->updateInfoRepository->method('getValueByKey')->willReturn('stored-server-id');
        $this->updateInfoRepository->expects(static::never())->method('setValue');

        self::assertSame('stored-server-id', $this->subject()->get());
    }

    /**
     * A value derived from the install's configuration would be the same on two empty installs, which is
     * what let an anonymous caller confirm a guessed `secret_key`.
     */
    public function testTwoInstallsDoNotShareAnId(): void
    {
        $this->updateInfoRepository->method('getValueByKey')->willReturn(null);

        self::assertNotSame($this->subject()->get(), $this->subject()->get());
    }

    protected function setUp(): void
    {
        $this->updateInfoRepository = $this->createMock(UpdateInfoRepositoryInterface::class);
    }

    private function subject(): JellyfinServerId
    {
        return new JellyfinServerId($this->updateInfoRepository);
    }
}
