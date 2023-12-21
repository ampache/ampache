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
declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

class AmpachePaypal implements AmpachePluginInterface
{
    public string $name        = 'Paypal';
    public string $categories  = 'user';
    public string $description = 'PayPal donation button on user page';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370034';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $user;
    private $business;
    private $currency_code;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('PayPal donation button on user page');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::exists('paypal_business') && !Preference::insert('paypal_business', T_('PayPal ID'), '', 25, 'string', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::exists('paypal_currency_code') && !Preference::insert('paypal_currency_code', T_('PayPal Currency Code'), 'USD', 25, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('paypal_business') &&
            Preference::delete('paypal_currency_code')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    }

    /**
     * display_user_field
     * This display the module in user page
     * @param library_item|null $libitem
     */
    public function display_user_field(library_item $libitem = null): void
    {
        $name = ($libitem != null) ? $libitem->get_fullname() : (T_('User') . " `" . $this->user->fullname . "` " . T_('on') . " " . AmpConfig::get('site_title'));
        $lang = substr(AmpConfig::get('lang', 'en_US'), 0, 2);
        if (empty($lang)) {
            $lang = 'US';
        }

        echo "<form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_top'>\n";
        echo "<input type='hidden' name='cmd' value='_donations'>\n";
        echo "<input type='hidden' name='business' value='" . scrub_out($this->business) . "'>\n";
        echo "<input type='hidden' name='lc' value='" . $lang . "'>\n";
        echo "<input type='hidden' name='item_name' value='" . $name . "'>\n";
        echo "<input type='hidden' name='no_note' value='0'>\n";
        echo "<input type='hidden' name='currency_code' value='" . scrub_out($this->currency_code) . "'>\n";
        echo "<input type='hidden' name='bn' value='PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest'>\n";
        echo "<input type='image' src='https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif' border='0' name='submit' alt='" . T_('PayPal - The safer, easier way to pay online!') . "'>\n";
        echo "<img alt= '' src='https://www.paypalobjects.com/fr_XC/i/scr/pixel.gif' width='1' height='1'>\n";
        echo "</form>\n";
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $this->user = $user;
        $user->set_preferences();
        $data = $user->prefs;

        $this->business = trim($data['paypal_business']);
        if (!strlen($this->business)) {
            debug_event('paypal.plugin', 'No PayPal ID, user field plugin skipped', 3);

            return false;
        }

        $this->currency_code = trim($data['paypal_currency_code']);
        if (!strlen($this->currency_code)) {
            $this->currency_code = 'USD';
        }

        return true;
    }
}
