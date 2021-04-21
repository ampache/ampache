<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Util;

interface UiInterface
{
    /**
     * Show the requested template file
     */
    public function show(string $template, array $context = []): void;

    /**
     * This displays the query stats
     */
    public function showQueryStats(): void;

    /**
     * This displays the footer
     */
    public function showFooter(): void;

    /**
     * This displays the header
     */
    public function showHeader(): void;

    public function showBoxTop(string $title = '', string $class = ''): void;

    public function showBoxBottom(): void;

    /**
     * Displays the default error page
     */
    public function accessDenied(string $error = 'Access Denied'): void;

    /**
     * shows a confirmation of an action
     *
     * @param string $title The Title of the message
     * @param string $text The details of the message
     * @param string $next_url Where to go next
     * @param integer $cancel T/F show a cancel button that uses return_referer()
     * @param string $form_name
     * @param boolean $visible
     */
    public function showConfirmation(
        $title,
        $text,
        $next_url,
        $cancel = 0,
        $form_name = 'confirmation',
        $visible = true
    ): void;

    public function scrubOut(?string $string): string;

    /**
     * Run on inputs, stuff that might get stuck in our db
     *
     * @param string|array $input
     * @return string|array
     */
    public function scrubIn($input);

    /**
     * Show a javascript notification to the user
     */
    public function displayNotification(string $message, int $timeout = 5000): string;

    /**
     * takes the key and then creates the correct type of input for updating it
     */
    public function createPreferenceInput(
        string $name,
        $value
    );

    /**
     * This shows the preference box for the preferences pages.
     *
     * @var array<string, mixed> $preferences
     */
    public function showPreferenceBox(array $preferences): void;

    /**
     * Yet another one of these buggers. this shows a drop down of all of your
     * catalogs.
     */
    public function showCatalogSelect(
        string $name,
        int $catalog_id,
        string $style = '',
        bool $allow_none = false,
        string $filter_type = ''
    ): string;
}
