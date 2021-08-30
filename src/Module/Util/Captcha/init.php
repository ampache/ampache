<?php

declare(strict_types=0);

/**
 * @deprecated Replace by a captcha library or at least move to global constants into a config
 * Requiring this file shouldn't be necessary
 */

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

/**
 * #####################################################################
 * #                               Warning                             #
 * #                               #######                             #
 * # This external file is Ampache-adapted and probably unsynced with  #
 * # origin because abandoned by its original authors.                #
 * #                                                                   #
 * #####################################################################
 *
 * api: php
 * title: Easy_CAPTCHA
 * description: highly configurable, user-friendly and accessible CAPTCHA
 * version: 2.3
 * author: milki
 * url: http://freshmeat.net/projects/captchaphp
 * config:
 * <const name="CAPTCHA_PERSISTENT" value="1"  type="boolean" title="persistent cookie" description="sets a cookie after user successfully solved it, spares further captchas for a few days" />
 * <const name="CAPTCHA_NEW_URLS" value="0"  type="boolean" title="new URLs only Javascript" description="uses Javascript detection to engage CAPTCHA only if a new URL was entered into any input box" />
 * <const name="CAPTCHA_AJAX" value="1" type="boolean" title="AJAX quickcheck" description="verifies the solution (visually) while user enters it" />
 * <const name="CAPTCHA_IMAGE_SIZE" value="200x60" type="string" regex="\d+x\d+" title="image size" description="height x width of CAPTCHA image" />
 * <const name="CAPTCHA_INVERSE" value="1"  type="boolean" title="inverse color" description="make captcha white on black" />
 * <const name="CAPTCHA_PIXEL" value="1" type="multi" multi="1=single pixel|2=greyscale 2x2|3=smooth color" title="smooth drawing" description="image pixel assembly method and speed" />
 * <const name="CAPTCHA_ONCLICK_HIRES" value="1" type="boolean" title="onClick-HiRes" description="reloads a finer resolution version of the CAPTCHA if user clicks on it" />
 * <const name="CAPTCHA_TIMEOUT" value="5000" type="string" regex="\d+" title="verification timeout" description="in seconds, maximum time to elapse from CAPTCHA display to verification" />
 * type: intercept
 * category: antispam
 * priority: optional
 *
 *
 * This library provides a CAPTCHA for safeguarding form submissions from
 * spam bots and alike. It is easy to hook into existing web sites and
 * scripts. And it comes with "smart" defaults, and some user-friendliness
 * built in.
 *
 * While the operation logic and identifier processing are extremley safe,
 * this is a "weak" implementation. Specifically targetted and tweaked OCR
 * software could overcome the visual riddle. And if enabled, the textual
 * or mathematical riddles are rather simple to overcome, if attacked.
 * Generic spambots are however blocked already with the default settings.
 *
 * PRINT captcha::form()
 * emits the img and input fields for inclusion into your submit <form>
 *
 * IF (captcha::solved())
 * tests for a correctly entered solution on submit, returns true if ok
 *
 * Temporary files are created for tracking, verification and basic data
 * storage, but will get automatically removed once a CAPTCHA was solved
 * to prevent replay attacks. Additionally this library has "AJAX" super
 * powers to enhance user interaction. And a short-lasting session cookie
 * is also added site-wide, so users may only have to solve the captcha
 * once (can be disabled, because that's also just security by obscurity).
 *
 * This code is Public Domain.
 */

namespace Ampache\Module\Util\Captcha;

// @define("CAPTCHA_BASE_URL", "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]" . dirname($_SERVER['SCRIPT_NAME']) . '/captcha.php');
// @define("C_TRIGGER_URL", (strtok('http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'], '?')));
/*
Mike O'Connell <wb:gm.c>
  The definition of CAPTCHA_BASE_URL was incorrect, as well as the test to
  see if the script was being called directly.  Here is what worked for
  me.  The Request URI and script name is the URL path rather than the
  actual server side directory you were using before
  ($_SERVER['DOCUMENT_ROOT']).
*/

define("CAPTCHA_PERSISTENT", 1);     // cookie-pass after it's solved once (does not work if headers were already sent on innovocation of captcha::solved() check)
define("CAPTCHA_NEW_URLS", 0);       // force captcha only when URLs submitted
define("CAPTCHA_AJAX", 1);           // visual feedback while entering letters
define("CAPTCHA_LOG", 0);            // create /tmp/captcha/log file
define("CAPTCHA_NOTEXT", 0);         // disables the accessible text/math riddle

#-- look
define("CAPTCHA_IMAGE_TYPE", 1);     // 1=wave, 2=whirly
#define("CAPTCHA_INVERSE", 0);        // white(=0) or black(=1)
define("CAPTCHA_IMAGE_SIZE", "200x60");  // randomly adapted a little
define("CAPTCHA_INPUT_STYLE", "height:46px; font-size:34px; font-weight:500;");
define("CAPTCHA_PIXEL", 1);          // set to 2 for smoother 2x2 grayscale pixel transform
define("CAPTCHA_ONCLICK_HIRES", 1);  // use better/slower drawing mode on reloading

#-- solving
define("CAPTCHA_FUZZY", 0.65);       // easier solving: accept 1 or 2 misguessed letters
define("CAPTCHA_TRIES", 5);          // maximum failures for solving the captcha
define("CAPTCHA_AJAX_TRIES", 25);    // AJAX testing limit (prevents brute-force cracking via check API)
define("CAPTCHA_MAXPASSES", 2);      // 2 passes prevent user annoyment with caching/reload failures
define("CAPTCHA_TIMEOUT", 5000);     // (in seconds/2) = 3:00 hours to solve a displayed captcha
define("CAPTCHA_MIN_CHARS", 5);      // how many letters to use
define("CAPTCHA_MAX_CHARS", 7);

#-- operation
define("CAPTCHA_TEMP_DIR", easy_captcha_utility::tmp() . "/captcha/");    // storage directory for captcha handles
define("CAPTCHA_PARAM_ID", "__ec_i");
define("CAPTCHA_PARAM_INPUT", "__ec_s");
define("CAPTCHA_BGCOLOR", 0xFFFFFF);   // initial background color (non-inverse, white)
define("CAPTCHA_SALT", ",e?c:7<");
#define("CAPTCHA_DATA_URLS", 0);     // RFC2397-URLs exclude MSIE users
define("CAPTCHA_FONT_DIR", __DIR__ . '/../../resources/fonts');
#define("CAPTCHA_BASE_URL",
#    (empty($_SERVER['HTTPS']) ? "http" : "https") . "://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/" . substr(realpath(__FILE__),
#        strlen(realpath($_SERVER["DOCUMENT_ROOT"]))));

#-- texts
define("CAPTCHA_PROMPT_TEXT", 'please enter the letters you recognize in the CAPTCHA image to the left');
define("CAPTCHA_WHATIS_TEXT", 'What is %s = ');
define("CAPTCHA_REDRAW_TEXT", 'click on image to redraw');

#-- init (triggered if *this* script is called directly)
//if ((basename($_SERVER["SCRIPT_FILENAME"]) == basename(__FILE__)) || (easy_captcha_utility::canonical_path("http://ignored.xxx/$_SERVER[REQUEST_URI]") == easy_captcha_utility::canonical_path(CAPTCHA_BASE_URL))) {
//    //easy_captcha_utility::API();
//}
