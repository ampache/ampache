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

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Repository\UserRepositoryInterface;

final class AdminUpdateUserCommand extends Command
{
    private UserRepositoryInterface $userRepository;

    private UserKeyGeneratorInterface $userKeyGenerator;

    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on([$this, 'showHelp']);

        $this->onExit(static fn ($exitCode = 0) => exit($exitCode));

        return $this;
    }

    public function __construct(
        UserRepositoryInterface $userRepository,
        UserKeyGeneratorInterface $userKeyGenerator
    ) {
        parent::__construct('admin:updateUser', T_('Update User'));

        $this->userRepository   = $userRepository;
        $this->userKeyGenerator = $userKeyGenerator;

        $this
            ->option('-a|--apikey', T_('Generate new API key'), 'boolval', false)
            ->option('-s|--streamtoken', T_('Generate new Stream key'), 'boolval', false)
            ->option('-r|--rsstoken', T_('Generate new RSS key'), 'boolval', false)
            ->argument('[username]', T_('Username'))
            ->usage('<bold>  admin:updateUser some-user --apikey</end> <comment> ## ' . T_('Update API key for user with the name `some-user`') . '</end><eol/>');
    }

    public function execute(
        ?string $username
    ): void {
        if ($this->app() === null) {
            return;
        }

        $interactor  = $this->io();
        $apiKey      = $this->values()['apikey'] === true;
        $streamToken = $this->values()['streamtoken'] === true;
        $rssToken    = $this->values()['rsstoken'] === true;
        $user        = ($username)
            ? $this->userRepository->findByUsername($username)
            : null;

        if ($user === null) {
            /* HINT: filename (File path) OR table name (podcast, video, etc) */
            $interactor->error(
                sprintf(T_('Missing: %s'), $username ?? 'username'),
                true
            );

            return;
        }
        if ($user->access == 100) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            $interactor->error(
                sprintf(T_('Bad Request: %s'), $user->getUsername()),
                true
            );

            return;
        }

        if ($apiKey) {
            $this->userKeyGenerator->generateApikey($user);

            $interactor->ok(
                sprintf(
                    '%s (%d)',
                    T_('API Key'),
                    $user->apikey ?? ''
                ),
                true
            );
        }

        if ($streamToken) {
            $this->userKeyGenerator->generateStreamToken($user);

            $interactor->ok(
                sprintf(
                    '%s (%d)',
                    T_('Stream Token'),
                    $user->streamtoken ?? ''
                ),
                true
            );
        }

        if ($rssToken) {
            $this->userKeyGenerator->generateRssToken($user);

            $interactor->ok(
                sprintf(
                    '%s (%d)',
                    T_('RSS Token'),
                    $user->rsstoken ?? ''
                ),
            );
        }
    }
}
