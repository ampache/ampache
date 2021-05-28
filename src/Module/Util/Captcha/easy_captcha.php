<?php
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

declare(strict_types=0);

namespace Ampache\Module\Util\Captcha;

/**
 * Class easy_captcha
 *
 * base logic and data storare
 */
class easy_captcha
{

    #-- init data
    /**
     * easy_captcha constructor.
     * @param $captcha_id
     * @param integer $ignore_expiration
     */
    public function __construct($captcha_id = null, $ignore_expiration = 0)
    {

        #-- load
        if (($this->id = $captcha_id) || ($this->id = preg_replace("/[^-,.\w]+/", "", $_REQUEST[CAPTCHA_PARAM_ID]))) {
            $this->load();
        }

        #-- create new
        if (empty($this->id) || !$ignore_expiration && !$this->is_valid() && $this->log("new()", "EXPIRED",
                "regenerating store")) {
            $this->generate();
        }
    }


    #-- create solutions
    public function generate()
    {

        #-- init
        srand(microtime() + time() / 2 - 21017);
        if ($this->id) {
            $this->prev[] = $this->id;
        }
        $this->id = $this->new_id();

        #-- meta information
        $this->created      = time();
        $this->{'created$'} = gmdate("r", $this->created);
        $this->expires      = $this->created + CAPTCHA_TIMEOUT;
        //$this->tries = 0;
        $this->passed = 0;

        #-- captcha processing info
        $this->sent       = 0;
        $this->tries      = CAPTCHA_TRIES; // 5
        $this->ajax_tries = CAPTCHA_AJAX_TRIES; // 25
        $this->passed     = 0;
        $this->maxpasses  = CAPTCHA_MAXPASSES; // 2
        $this->failures   = 0;
        $this->shortcut   = array();
        $this->grant      = 0; // unchecked access

        #-- mk IMAGE/GRAPHIC
        $this->image = (CAPTCHA_IMAGE_TYPE <= 1) ? new easy_captcha_graphic_image_waved() : new easy_captcha_graphic_image_disturbed();
        //$this->image = new easy_captcha_graphic_cute_ponys();

        #-- mk MATH/TEXT riddle
        $this->text = (CAPTCHA_NOTEXT >= 1) ? new easy_captcha_text_disable() : new easy_captcha_text_math_formula();
        //$this->text = new easy_captcha_text_riddle();

        #-- process granting cookie
        if (CAPTCHA_PERSISTENT) {
            $this->shortcut[] = new easy_captcha_persistent_grant();
        }

        #-- spam-check: no URLs submitted
        if (CAPTCHA_NEW_URLS) {
            $this->shortcut[] = new easy_captcha_spamfree_no_new_urls();
        }

        #-- store record
        $this->save();
    }

    #-- examine if captcha data is fresh

    /**
     * @return boolean
     */
    public function is_valid()
    {
        return isset($this->id) && ($this->created) && ($this->expires > time()) && ($this->tries > 0) && ($this->failures < 500) && ($this->passed < $this->maxpasses) || $this->delete() || $this->log("is_valid",
                "EXPIRED", "and deleted") && false;
    }


    #-- new captcha tracking/storage id

    /**
     * @return string
     */
    public function new_id()
    {
        return "ec." . time() . "." . md5($_SERVER["SERVER_NAME"] . CAPTCHA_SALT . rand(0, 1 << 30));
    }


    #-- check backends for correctness of solution

    /**
     * @param $input
     * @return boolean
     */
    public function solved($input = null)
    {
        $okay = false;

        #-- failure
        if ((0 >= $this->tries--) || !$this->is_valid()) {
            // log, this is either a frustrated user or a bot knocking
            $this->log("::solved", "INVALID", "tries exhausted ($this->tries) or expired(?) captcha");
        } elseif ($this->sent) {
            $input = $_REQUEST[CAPTCHA_PARAM_INPUT]; // might be empty string

            #-- check individual modules
            $okay = $this->grant;
            foreach ($this->shortcut as $test) {
                $okay = $okay || $test->solved($input); // cookie & nourls
            }
            $okay = $okay // either letters or math formula submitted
                || isset($this->image) && $this->image->solved($input) || isset($this->text) && $this->text->solved($input);

            #-- update state
            if ($okay) {
                $this->passed++;
                $this->log("::solved", "OKAY",
                    "captcha passed ($input) for image({$this->image->solution}) and text({$this->text->solution})");

                #-- set cookie on success
                if (CAPTCHA_PERSISTENT) {
                    $this->shortcut[0/*FIXME*/]->grant();
                    $this->log("::solved", "PERSISTENT", "cookie granted");
                }
            } else {
                $this->failures++;
                $this->log("::solved", "WRONG",
                    "solution failure ($input) for image({$this->image->solution}) and text({$this->text->solution})");
            }
        }

        #-- remove if done
        if (!$this->is_valid() /*&& !$this->delete()*/) {
            $this->generate(); // ensure object instance can be reused - for quirky form processing logic
        } #-- store state/result
        else {
            $this->save();
        }

        #-- return result
        return ($okay);
    }

    #-- combines ->image and ->text data into form fields

    /**
     * @param string $add_text
     * @return string
     */
    public function form($add_text = "&rarr;&nbsp;")
    {

        #-- store object data
        $this->sent++;
        $this->save();

        #-- check for errors
        $errors = array(
            "invalid object created" => !$this->is_valid(),
            "captcha_id storage could not be saved" => !$this->saved,
            "no ->id present" => empty($this->id),
            "no ->created timestamp" => empty($this->created),
        );
        if (array_sum($errors)) {
            return '<div id="captcha" class="error">*' . implode("<br>*", array_keys(array_filter($errors))) . '</div>';
        }

        #-- prepare output vars
        $p_id       = CAPTCHA_PARAM_ID;
        $p_input    = CAPTCHA_PARAM_INPUT;
        $base_url   = CAPTCHA_BASE_URL . '?' . CAPTCHA_PARAM_ID . '=';
        $id         = htmlentities($this->id);
        $img_url    = $base_url . $id;
        $alt_text   = htmlentities($this->text->question);
        $new_urls   = CAPTCHA_NEW_URLS ? 0 : 1;
        $onClick    = CAPTCHA_ONCLICK_HIRES ? 'onClick="this.src += this.src.match(/hires/) ? \'.\' : \'hires=1&\';"' : 'onClick="this.src += \'.\';"';
        $onKeyDown  = CAPTCHA_AJAX ? 'onKeyUp="captcha_check_solution()"' : '';
        $javascript = CAPTCHA_AJAX ? '<script src="' . $base_url . 'base.js&captcha_new_urls=' . $new_urls . '" id="captcha_ajax_1"></script>' : '';
        $error      = function_exists('imagecreatetruecolor') ? '' : '<div class="error">PHP setup lacks GD. No image drawing possible</div>';

        #-- assemble
        $HTML = //'<script>if (document.getElementById("captcha")) { document.getElementById("captcha").parentNode.removeChild(document.getElementById("captcha")); }</script>' .   // workaround for double instantiations
            '<div id="captcha" class="captcha">' . $error . '<input type="hidden" id="' . $p_id . '" name="' . $p_id . '" value="' . $id . '" />' . '<img src="' . $img_url . '&" width="' . $this->image->width . '" height="' . $this->image->height . '" alt="' . $alt_text . $onClick . ' title="' . CAPTCHA_REDRAW_TEXT . '" />' . '&nbsp;' . $add_text . '<input title="' . CAPTCHA_PROMPT_TEXT . '" type="text" ' . $onKeyDown . ' id="' . $p_input . '" name="' . $p_input . '" value="' . (isset($_REQUEST[$p_input]) ? htmlentities($_REQUEST[$p_input]) : "") . '" size="8" style="' . CAPTCHA_INPUT_STYLE . '" />' . $javascript . '</div>';

        return ($HTML);
    }

    #-- noteworthy stuff goes here

    /**
     * @param $error
     * @param $category
     * @param $message
     * @return boolean
     */
    public function log($error, $category, $message)
    {
        // append to text file
        if (CAPTCHA_LOG) {
            file_put_contents(CAPTCHA_TEMP_DIR . "/captcha.log",
                "[$error] -$category- \"$message\" $_SERVER[REMOTE_ADDR] id={$this->id} tries={$this->tries} failures={$this->failures} created/time/expires=$this->created/" . time() . "/$this->expires \n",
                FILE_APPEND | LOCK_EX);
        }

        return (true);   // for if-chaining
    }


    #-- load object from saved captcha tracking data
    public function load()
    {
        $filepath = $this->data_file();
        if (file_exists($filepath)) {
            $saved = (array)unserialize(fread(fopen($filepath, "r"), 1 << 20));
            foreach ($saved as $i => $v) {
                $this->{$i} = $v;
            }
        } else {
            $this->log("::load()", "MISSING", "captcha file does not exist $filepath");
        }
    }

    #-- save $this captcha state
    public function save()
    {
        $this->straighten_temp_dir();

        $filepath = $this->data_file();
        if ($filepath) {
            $this->saved = file_put_contents($filepath, serialize($this), LOCK_EX);
        }
    }

    #-- remove $this data file

    /**
     * @return boolean
     */
    public function delete()
    {
        // delete current and all previous data files
        $this->prev[] = $this->id;
        if (isset($this->prev)) {
            foreach ($this->prev as $file) {
                @unlink($this->data_file($file));
            }
        }
        // clean object
        foreach ((array)$this as $name => $val) {
            unset($this->{$name});
        }

        return (false);  // far if-chaining in ->is_valid()
    }

    #-- clean-up or init temporary directory
    public function straighten_temp_dir()
    {
        // create dir
        if (!file_exists($dir = CAPTCHA_TEMP_DIR)) {
            mkdir($dir);
        }
        // clean up old files
        if ((rand(0, 100) <= 5) && ($dir_handle = opendir($dir))) {
            $t_kill = time() - CAPTCHA_TIMEOUT * 1.2;
            while (false !== ($filepath = readdir($dir_handle))) {
                if ($filepath[0] != ".") {
                    if (filemtime("$dir/$filepath") < $t_kill) {
                        @unlink("$dir/$filepath");
                    }
                }
            }
        }
    }

    #-- where's the storage?

    /**
     * @param integer $object_id
     * @return string
     */
    public function data_file($object_id = null)
    {
        return CAPTCHA_TEMP_DIR . "/" . preg_replace("/[^-,.\w]/", "", ($object_id ? $object_id : $this->id)) . ".a()";
    }


    #-- unreversable hash from passphrase, with time() slice encoded

    /**
     * @param $text
     * @param integer $dtime
     * @param integer $length
     * @return string
     */
    public function hash($text, $dtime = 0, $length = 1)
    {
        $text = strtolower($text);
        $pfix = (int)(time() / $length * CAPTCHA_TIMEOUT) + $dtime;

        return md5("captcha::$pfix:$text::" . __FILE__ . ":$_SERVER[SERVER_NAME]:80");
    }
}
