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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\AutoUpdate;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Override;

final class ShowDebugCommand extends Command
{
    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly EnvironmentInterface $environment,
        private readonly UpdateInfoRepositoryInterface $updateInfoRepository,
    ) {
        parent::__construct('show:debug', T_('Show a system status and environment report'));

        $this
            ->option('-u|--check-updates', T_('Query the remote version (network access required)'), 'boolval', false)
            ->usage('<bold>  show:debug</end> <comment> ## ' . T_('Print a headless status report') . '</end><eol/>');
    }

    public function execute(): void
    {
        if ($this->app() === null) {
            return;
        }

        $interactor = $this->io();

        $interactor->info(T_('System'), true);
        $interactor->ok(sprintf('  Version:   %s', AutoUpdate::get_current_version()), true);
        $interactor->ok(sprintf('  Structure: %s', (string) $this->configContainer->get('structure')), true);
        $interactor->ok(sprintf('  PHP:       %s', PHP_VERSION), true);

        $lastCron = (int) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::CRON_DATE);
        $interactor->ok(
            sprintf('  Last cron: %s', ($lastCron > 0) ? date('Y-m-d H:i', $lastCron) : T_('Never')),
            true
        );

        if ($this->values()['check-updates'] === true) {
            $interactor->ok(sprintf('  Latest:    %s', AutoUpdate::get_latest_version(true)), true);
            $interactor->ok(sprintf('  Update:    %s', AutoUpdate::is_update_available(true) ? T_('Update available') : T_('up to date')), true);
        }

        $interactor->info(T_('Environment'), true);
        foreach ($this->environmentChecks() as $label => $passed) {
            $interactor->ok(sprintf('  [%s] %s', $passed ? 'OK' : '!!', $label), true);
        }
    }

    #[Override]
    protected function defaults(): self
    {
        $this->option('-h, --help', T_('Help'))->on($this->showHelp(...));

        $this->onExit(static fn($exitCode = 0) => exit($exitCode));

        return $this;
    }

    /**
     * The PHP/runtime prerequisites the web debug page verifies, collapsed to a headless pass/fail list
     *
     * @return array<string, bool>
     */
    private function environmentChecks(): array
    {
        return [
            'PHP version' => $this->environment->check_php_version(),
            'PDO' => $this->environment->check_php_pdo(),
            'PDO MySQL' => $this->environment->check_php_pdo_mysql(),
            'session' => $this->environment->check_php_session(),
            'JSON' => $this->environment->check_php_json(),
            'hash' => $this->environment->check_php_hash(),
            'intl' => $this->environment->check_php_intl(),
            'cURL' => $this->environment->check_php_curl(),
            'GD' => $this->environment->check_php_gd(),
            'memory limit' => $this->environment->check_php_memory(),
            'dependencies folder' => $this->environment->check_dependencies_folder(),
        ];
    }
}
