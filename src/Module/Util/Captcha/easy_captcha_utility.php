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

use Ampache\Module\System\Core;

/**
 * Class easy_captcha_utility
 *
 * "AJAX" and utility code
 */
class easy_captcha_utility
{


    #-- determine usable temp directory
    /**
     * @return mixed
     */
    public function tmp()
    {
        return current(array_filter(// filter by writability
            array_filter(// filter empty entries
                array(
                    $_SERVER['TMPDIR'],
                    $_SERVER['REDIRECT_TMPDIR'],
                    $_SERVER['TEMP'],
                    ini_get('upload_tmp_dir'),
                    $_SERVER['TMP'],
                    $_SERVER['TEMPDIR'],
                    function_exists("sys_get_temp_dir") ? sys_get_temp_dir() : "",
                    '/tmp'
                )), "is_writable"));
    }


    #-- script was called directly

    /**
     * @return boolean
     */
    public static function API()
    {

        #-- load data
        if ($id = Core::get_get(CAPTCHA_PARAM_ID)) {
            #-- special case
            if ($id == 'base.js') {
                easy_captcha_utility::js_base();
            } else {
                $c       = new easy_captcha($id = null, $ignore_expiration = 1);
                $expired = !$c->is_valid();

                #-- JS-RPC request, check entered solution on the fly
                if ($test = $_REQUEST[CAPTCHA_PARAM_INPUT]) {
                    #-- check
                    if ($expired || empty($c->image)) {
                        die(easy_captcha_utility::js_header('alert("captcha error: request invalid (wrong storage id) / or expired");'));
                    }
                    if (0 >= $c->ajax_tries--) {
                        $c->log("::API", "JS-RPC", "ajax_tries exhausted ($c->ajax_tries)");
                    }
                    $okay = $c->image->solved($test) || $c->text->solved($test);

                    #-- sendresult
                    easy_captcha_utility::js_rpc($okay);
                } #-- generate and send image file
                else {
                    if ($expired) {
                        $type = "image/png";
                        $bin  = easy_captcha_utility::expired_png();
                    } else {
                        $type = "image/jpeg";
                        $bin  = $c->image->jpeg();
                    }
                    header("Pragma: no-cache");
                    header("Cache-Control: no-cache, no-store, must-revalidate, private");
                    header("Expires: " . gmdate("r", time()));
                    header("Content-Length: " . strlen($bin));
                    header("Content-Type: $type");
                    print $bin;
                }
            }

            return false;
        }

        return false;
    }

    #-- hardwired error img

    /**
     * @return false|string
     */
    public function expired_png()
    {
        return base64_decode("iVBORw0KGgoAAAANSUhEUgAAADwAAAAUAgMAAACsbba6AAAADFBMVEUeEhFcMjGgWFf9jIrTTikpAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA3UlEQVQY01XPzwoBcRAH8F9RjpSTm9xR9qQwtnX/latX0DrsA3gC8QDK0QO4bv7UOtmM+x4oZ4X5FQc1hlb41dR8mm/9ZhT/P7X/dDcpZPU3FYft9kWbLuWp4Bgt9v1oGG07Ja8ojfjxQFym02DVmoixkV/m2JI/TUtefR7nD9rkrhkC+6D77/8mUhDvw0ymLPwxf8esghEFRq8hqKcu2iG16Vlun1zYTO7RwCeFyoJqAgC3LQwzYiCokDj0MWRxb+Z6R8mPJb8Q77zlPbuCoJE8a/t7P773uv36tdcTmsXfRycoRJ8AAAAASUVORK5CYII=");
    }

    #-- send base javascript
    public function js_base()
    {
        $captcha_new_urls = $_GET["captcha_new_urls"] ? 0 : 1;
        $BASE_URL         = CAPTCHA_BASE_URL;
        $PARAM_ID         = CAPTCHA_PARAM_ID;
        $PARAM_INPUT      = CAPTCHA_PARAM_INPUT;
        $COLOR_CALC       = CAPTCHA_INVERSE ? "32 +" : "224 -";
        easy_captcha_utility::js_header();
        print<<<END_____BASE__BASE__BASE__BASE__BASE__BASE__BASE__BASE_____END


/* easy_captcha utility code */

// global vars
captcha_url_rx = /(https?:\/\/\w[^\/,\]\[=#]+)/ig;
captcha_form_urls = new Array();
captcha_sol_cb = "";
captcha_rpc = 0;

// set up watchers
if ($captcha_new_urls) {
  window.setTimeout("captcha_form_urls = captcha_find_urls_in_form()", 500);
  window.setInterval("captcha_spamfree_no_new_urls()", 3000);
}

// scans for URLs in any of the form fields
function captcha_find_urls_in_form() {
   var nf, ne, nv;
   for (nf=0; nf<document.forms.length; nf++) {
      for (ne=0; ne<document.forms[nf].elements.length; ne++) {
         nv += "\\n" + document.forms[nf].elements[ne].value;
      }
   }
   var r = nv.match(captcha_url_rx);
   if (!r) { r = new Array(); }
   return r;
}
// diff URL lists and hide captcha if nothing new was entered
function captcha_spamfree_no_new_urls() {
   var has_new_urls = captcha_find_urls_in_form().join(",") != captcha_form_urls.join(",");
   var s = document.getElementById("captcha").style;
   if (s.opacity) {
      s.opacity = has_new_urls ? "0.9" : "0.1";
   }
   else {
      s.display = has_new_urls ? "block" : "none";
   }
}

// if a certain solution length is reached, check it remotely (JS-RPC)
function captcha_check_solution() {
   var cid = document.getElementById("{$PARAM_ID}");
   var inf = document.getElementById("{$PARAM_INPUT}");
   var len = inf.value.length;
   // visualize processissing
   if (len >= 4) {
      inf.style.border = "2px solid #FF9955";
   }
   // if enough letters entered
   if (len >= 4) {
      // remove old <script> node
      var scr;
      if (src = document.getElementById("captcha_ajax_1")) {
         src.parentNode.removeChild(src);
      }
      // create new <script> node, initiate JS-RPC call thereby
      scr = document.createElement("script");
      var url = "$BASE_URL" + "?$PARAM_ID=" + cid.value + "&$PARAM_INPUT=" + inf.value;
      scr.setAttribute("src", url);
      scr.setAttribute("id", "captcha_ajax_1");
      document.getElementById("captcha").appendChild(scr);
      captcha_rpc = 1;
   }
   // visual feedback for editing
   var col = $COLOR_CALC len * 5;
       col = col.toString(16);
   inf.style.background = "#"+col+col+col;
}


END_____BASE__BASE__BASE__BASE__BASE__BASE__BASE__BASE_____END;
    }

    #-- javascript header (also prevent caching)

    /**
     * @param string $print
     */
    public function js_header($print = '')
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache, no-store, must-revalidate, private");
        header("Expires: " . gmdate("r", time()));
        header("Content-Type: text/javascript");
        if ($print) {
            print $print;
        }
    }


    #-- response javascript

    /**
     * @param $yes
     */
    public function js_rpc($yes)
    {
        $yes         = $yes ? 1 : 0;
        $PARAM_INPUT = CAPTCHA_PARAM_INPUT;
        easy_captcha_utility::js_header();
        print<<<END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END


// JS-RPC response
if (1) {
   captcha_rpc = 0;
   var inf = document.getElementById("{$PARAM_INPUT}");
   inf.style.borderColor = $yes ? "#22AA22" : "#AA2222";
}


END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END;
    }

    /* static */
    /**
     * @param $url
     * @return string
     */
    public function canonical_path($url)
    {
        $path = parse_url($url);

        if (is_array($path) && !empty($path['path'])) {
            $url = $path['path'];
        }

        $path    = array();
        $abspath = substr("$url ", 0, 1) == '/' ? '/' : '';
        $ncomp   = 0;

        foreach (explode('/', $url) as $comp) {
            switch ($comp) {
                case '':
                case '.':
                    break;
                case '..':
                    if ($ncomp--) {
                        array_pop($path);
                        break;
                    }
                    // Intentional break fall-through
                default:
                    $path[] = $comp;
                    $ncomp++;
                    break;
            }
        }

        $path = $abspath . implode('/', $path);

        return empty($path) ? '.' : $path;
    }  //patch contributed from Fedora downstream by Patrick Monnerat
}
