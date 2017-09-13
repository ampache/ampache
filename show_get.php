<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once 'lib/init.php';

 if (isset($_REQUEST['param_name'])) {
     $name = scrub_in($_REQUEST['param_name']);
     if (isset($_REQUEST[$name])) {
         echo $name . ": " . scrub_in($_REQUEST[$name]);
     }
 }

 if (isset($_REQUEST['error'])) {
     $error             = scrub_in($_REQUEST['error']);
     $error_description = scrub_in($_REQUEST['error_description']);
     echo $error . " error: " . $error_description;
 }
