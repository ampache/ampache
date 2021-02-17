<?php

declare(strict_types=1);

namespace Ampache\Gui\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Update\UpdateAction;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Update;
use Ampache\Module\Util\Ui;

final class UpdateViewAdapter implements UpdateViewAdapterInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function getHtmlLanguage(): string
    {
        return str_replace(
            '_',
            '-',
            $this->configContainer->get(ConfigurationKeyEnum::LANG)
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
            '<strong>' . Update::get_version() . '</strong>'
        );
    }

    public function getErrorText(): string
    {
        return AmpError::getErrorsFormatted('general');
    }

    public function hasUpdate(): bool
    {
        return Update::need_update();
    }

    public function getUpdateActionUrl(): string
    {
        return sprintf(
            '%s/update.php?action=%s',
            $this->configContainer->getWebPath(),
            UpdateAction::REQUEST_KEY
        );
    }

    public function getUpdateInfo(): array
    {
        $updates = Update::display_update();
        $result  = [];

        foreach ($updates as $update) {
            $result[] = [
                'title' => sprintf(
                    T_('Version: %s'),
                    Update::format_version($update['version'])
                ),
                'description' => $update['description'],
            ];
        }

        return $result;
    }

    public function getWebPath(): string
    {
        return $this->configContainer->getWebPath();
    }
}
