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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AdvancedSearchMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ?AdvancedSearchMethod $subject;

    /**
     * @return array<string, array{0: int}>
     */
    public static function olderApiVersionProvider(): array
    {
        return [
            'api4' => [4],
            'api5' => [5],
            'api6' => [6],
        ];
    }

    public function testCheckRulesThrowsExceptionIfRuleIsMissing(): void
    {
        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'rule_1'));

        $this->subject->checkRules([]);
    }

    /**
     * album_disk is searchable but only api8 can render it. Older versions used to fall through to the
     * song formatter, so a client read album_disk ids as song ids.
     */
    #[DataProvider(methodName: 'olderApiVersionProvider')]
    public function testHandleReturnsEmptyResultForAlbumDiskBelowApi8(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'empty-result';

        $output->shouldReceive('writeEmpty')
            ->with($apiVersion, 'album_disk')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => 'album_disk',
                    'rule_1' => 'title',
                    'rule_1_operator' => '0',
                    'rule_1_input' => 'some-title',
                    'api_format' => 'json',
                    'auth' => 'some-auth',
                ],
                $user,
                $apiVersion
            )
        );
    }

    public function testIsSearchableTypeAllowsAlbumDisk(): void
    {
        $this->assertTrue($this->subject->isSearchableType('album_disk'));
    }

    public function testIsSearchableTypeReturnsFalseForUnknownType(): void
    {
        $this->assertFalse($this->subject->isSearchableType('not-a-type'));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new AdvancedSearchMethod(
            $this->configContainer
        );
    }
}
