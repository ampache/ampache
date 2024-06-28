<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Gui\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Update\UpdateAction;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Update;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\UpdateInfoEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Generator;

final class UpdateViewAdapter implements UpdateViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    private UpdateInfoRepositoryInterface $updateInfoRepository;

    private Update\UpdateHelperInterface $updateHelper;

    private Update\UpdaterInterface $updater;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UpdateInfoRepositoryInterface $updateInfoRepository,
        Update\UpdateHelperInterface $updateHelper,
        Update\UpdaterInterface $updater
    ) {
        $this->configContainer      = $configContainer;
        $this->updateInfoRepository = $updateInfoRepository;
        $this->updateHelper         = $updateHelper;
        $this->updater              = $updater;
    }

    public function getHtmlLanguage(): string
    {
        return str_replace(
            '_',
            '-',
            $this->configContainer->get(ConfigurationKeyEnum::LANG) ?? 'en-US'
        );
    }

    public function getCharset(): string
    {
        return $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET);
    }

    public function getTitle(): string
    {
        return sprintf(
            T_('%s - Update'),
            $this->configContainer->get(ConfigurationKeyEnum::SITE_TITLE)
        );
    }

    public function getLogoUrl(): string
    {
        return Ui::get_logo_url('dark');
    }

    public function getInstallationTitle(): string
    {
        return T_('Ampache :: For the Love of Music - Installation');
    }

    public function getUpdateInfoText(): string
    {
        /* HINT: %1 Displays 3.3.3.5, %2 shows current Ampache version, %3 shows current database version */
        return sprintf(
            T_('This page handles all database updates to Ampache starting with %1$s. Your current version is %2$s with database version %3$s'),
            '<strong>3.3.3.5</strong>',
            '<strong>' . $this->configContainer->get(ConfigurationKeyEnum::VERSION) . '</strong>',
            '<strong>' . $this->updateHelper->formatVersion((string) $this->updateInfoRepository->getValueByKey(UpdateInfoEnum::DB_VERSION)) . '</strong>'
        );
    }

    public function getErrorText(): string
    {
        return AmpError::getErrorsFormatted('general');
    }

    public function hasUpdate(): bool
    {
        return $this->updater->hasPendingUpdates();
    }

    public function getUpdateActionUrl(): string
    {
        return sprintf(
            '%s/update.php?action=%s',
            $this->configContainer->getWebPath(),
            UpdateAction::REQUEST_KEY
        );
    }

    /**
     * @return Generator<array{
     *  title: string,
     *  changelog: list<string>,
     *  warning: bool
     * }>
     */
    public function getUpdateInfo(): Generator
    {
        foreach ($this->updater->getPendingUpdates() as $update) {
            $migration = $update['migration'];

            yield [
                'title' => sprintf('%s: %s', T_('Version'), $update['versionFormatted']),
                'changelog' => $migration->getChangelog(),
                'warning' => $migration->hasWarning()
            ];
        }
    }

    public function getWebPath(): string
    {
        return $this->configContainer->getWebPath();
    }
}
