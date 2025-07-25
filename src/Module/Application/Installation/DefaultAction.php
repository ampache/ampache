<?php

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

namespace Ampache\Module\Application\Installation;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\InstallationHelperInterface;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Module\Util\Ui;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @todo This action has to be split up. Too complicated atm
 */
final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'default';

    public InstallationHelperInterface $installationHelper;

    private EnvironmentInterface $environment;

    public function __construct(
        InstallationHelperInterface $installationHelper,
        EnvironmentInterface $environment
    ) {
        $this->installationHelper = $installationHelper;
        $this->environment        = $environment;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        set_error_handler('ampache_error_handler');

        $configfile = __DIR__ . '/../../../../config/ampache.cfg.php';

        // Redirect if installation is already complete.
        if (!$this->installationHelper->install_check_status($configfile)) {
            $redirect_url = 'login.php';
            require_once Ui::find_template('error_page.inc.php');

            return null;
        }

        define('INSTALL', 1);

        $htaccess_play_file = __DIR__ . '/../../../../public/play/.htaccess';
        $htaccess_rest_file = __DIR__ . '/../../../../public/rest/.htaccess';

        // Clean up incoming variables
        $web_path   = scrub_in((string) ($_REQUEST['web_path'] ?? ''));
        $username   = scrub_in((string) ($_REQUEST['local_username'] ?? ''));
        $password   = $_REQUEST['local_pass'] ?? null;
        $hostname   = scrub_in((string) ($_REQUEST['local_host'] ?? ''));
        $database   = scrub_in((string) ($_REQUEST['local_db'] ?? ''));
        $port       = scrub_in((string) ($_REQUEST['local_port'] ?? ''));
        $skip_admin = isset($_REQUEST['skip_admin']);

        AmpConfig::set_by_array(
            [
                'web_path' => $web_path,
                'database_name' => $database,
                'database_hostname' => $hostname,
                'database_port' => $port
            ],
            true
        );
        if (!$skip_admin) {
            AmpConfig::set_by_array(
                [
                    'database_username' => $username,
                    'database_password' => $password
                ],
                true
            );
        }

        if (array_key_exists('transcode_template', $_REQUEST)) {
            $mode = (string)$_REQUEST['transcode_template'];
            $this->installationHelper->install_config_transcode_mode($mode);
        }

        if (array_key_exists('usecase', $_REQUEST)) {
            $case = (string)$_REQUEST['usecase'];
            if (Dba::check_database()) {
                $this->installationHelper->install_config_use_case($case);
            }
        }

        if (array_key_exists('backends', $_REQUEST)) {
            $backends = $_REQUEST['backends'];
            if (Dba::check_database()) {
                $this->installationHelper->install_config_backends($backends);
            }
        }

        // Charset and gettext setup
        $charset  = $_REQUEST['charset'] ?? 'UTF-8';
        $htmllang = $_REQUEST['htmllang'] ?? null;

        if (!$htmllang) {
            if ($_ENV['LANG']) {
                $lang = $_ENV['LANG'];
            } else {
                $lang = 'en_US';
            }
            if (strpos($lang, '.')) {
                $langtmp  = explode('.', $lang);
                $htmllang = $langtmp[0];
                $charset  = $langtmp[1];
            } else {
                $htmllang = $lang;
            }
        }
        AmpConfig::set('lang', $htmllang, true);
        AmpConfig::set('site_charset', $charset, true);
        if (!class_exists('Gettext\Translations')) {
            require_once __DIR__ . '/../../../../public/templates/test_error_page.inc.php';
            throw new Exception('load_gettext()');
        } else {
            load_gettext();
        }
        header('Content-Type: text/html; charset=' . AmpConfig::get('site_charset', 'UTF-8'));

        // Correct potential \ or / in the dirname
        $safe_dirname = get_web_path();

        $protocol = ($this->environment->isSsl()) ? 'https' : 'http';

        $web_path = sprintf(
            '%s://%s%s',
            $protocol,
            Core::get_server('HTTP_HOST'),
            $safe_dirname
        );

        unset($safe_dirname);

        // Switch on the actions
        $action = $_REQUEST['action'] ?? '';
        switch ($action) {
            case 'create_db':
                /** @noinspection PhpMissingBreakStatementInspection */
                $new_user = '';
                $new_pass = '';
                if (Core::get_post('db_user') == 'create_db_user') {
                    $new_user = Core::get_post('db_username');
                    $new_pass = Core::get_post('db_password');

                    if (!strlen($new_user) || !strlen($new_pass)) {
                        AmpError::add('general', T_('The Ampache database username or password is missing'));
                        require_once __DIR__ . '/../../../../public/templates/show_install.inc.php';
                        break;
                    }
                }

                if (!$skip_admin) {
                    if (!$this->installationHelper->install_insert_db($new_user, $new_pass, array_key_exists('create_db', $_REQUEST), array_key_exists('overwrite_db', $_REQUEST), array_key_exists('create_tables', $_REQUEST))) {
                        require_once __DIR__ . '/../../../../public/templates/show_install.inc.php';
                        break;
                    }
                }

                // Now that it's inserted save the lang preference
                Preference::update('lang', -1, AmpConfig::get('lang', 'en_US'));
                // Intentional break fall-through
            case 'show_create_config':
                require_once __DIR__ . '/../../../../public/templates/show_install_config.inc.php';
                break;
            case 'create_config':
                /** @noinspection PhpMissingBreakStatementInspection */
                $all  = (isset($_POST['create_all']));
                $skip = (isset($_POST['skip_config']));
                if (!$skip) {
                    $write                  = (isset($_POST['write']));
                    $download               = (isset($_POST['download']));
                    $download_htaccess_rest = (isset($_POST['download_htaccess_rest']));
                    $download_htaccess_play = (isset($_POST['download_htaccess_play']));
                    $write_htaccess_rest    = (isset($_POST['write_htaccess_rest']));
                    $write_htaccess_play    = (isset($_POST['write_htaccess_play']));

                    $created_config = true;
                    if ($write_htaccess_rest || $download_htaccess_rest || $all) {
                        $created_config = $this->installationHelper->install_rewrite_rules($htaccess_rest_file, Core::get_post('web_path'), $download_htaccess_rest);
                    }
                    if ($write_htaccess_play || $download_htaccess_play || $all) {
                        $created_config = $created_config && $this->installationHelper->install_rewrite_rules($htaccess_play_file, Core::get_post('web_path'), $download_htaccess_play);
                    }
                    if ($write || $download || $all) {
                        $created_config = $created_config && $this->installationHelper->install_create_config($download);
                        if ($download && !$created_config) {
                            return null;
                        }
                    }
                }
                // Intentional break fall-through
            case 'show_create_account':
                $results = parse_ini_file($configfile);
                if (!isset($created_config)) {
                    $created_config = true;
                }

                /* Make sure we've got a valid config file */
                if (
                    !$results ||
                    !check_config_values($results) ||
                    !$created_config
                ) {
                    AmpError::add('general', T_('Configuration files were either not found or unreadable'));
                    require_once Ui::find_template('show_install_config.inc.php');
                    break;
                }

                // Don't try to add administrator user on existing database
                if ($this->installationHelper->install_check_status($configfile)) {
                    require_once Ui::find_template('show_install_account.inc.php');
                } else {
                    header("Location: " . $web_path . '/login.php');
                }
                break;
            case 'create_account':
                $results = parse_ini_file($configfile) ?: [];
                AmpConfig::set_by_array($results, true);

                $password2 = $_REQUEST['local_pass2'];

                if (!$this->installationHelper->install_create_account($username, $password, $password2)) {
                    require_once Ui::find_template('show_install_account.inc.php');
                    break;
                }

                header("Location: " . $web_path . '/index.php');
                break;
            case 'init':
                require_once __DIR__ . '/../../../../public/templates/show_install.inc.php';
                break;
            case 'check':
                require_once __DIR__ . '/../../../../public/templates/show_install_check.inc.php';
                break;
            default:
                // Show the language options first
                require_once __DIR__ . '/../../../../public/templates/show_install_lang.inc.php';
                break;
        } // end action switch

        return null;
    }
}
