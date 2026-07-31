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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * podcast_update is an alias of update_podcast, so it only has to pass the call straight through
 */
class PodcastUpdateMethodTest extends MockeryTestCase
{
    private MockInterface|PodcastRepositoryInterface|null $podcastRepository;
    private MockInterface|PodcastSyncerInterface|null $podcastSyncer;
    private MockInterface|PrivilegeCheckerInterface|null $privilegeChecker;
    private ?PodcastUpdateMethod $subject;

    /**
     * The same method serves both api versions; the version is only handed to the output
     *
     * @return array<string, array{0: int}>
     */
    public static function apiVersionProvider(): array
    {
        return [
            'api6' => [6],
            'api8' => [8],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleDelegatesToUpdatePodcast(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        // the missing-filter guard lives in update_podcast, so reaching it proves the call was forwarded
        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'filter'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    public function testMethodExposesTheAliasAction(): void
    {
        $this->assertSame('podcast_update', PodcastUpdateMethod::ACTION);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);
        $this->podcastSyncer     = $this->mock(PodcastSyncerInterface::class);
        $this->privilegeChecker  = $this->mock(PrivilegeCheckerInterface::class);

        $this->subject = new PodcastUpdateMethod(
            new UpdatePodcastMethod(
                $this->podcastRepository,
                $this->podcastSyncer,
                $this->privilegeChecker
            )
        );
    }
}
