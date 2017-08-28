<?php

function T_($msgid)
{
    if (function_exists('__')) {
        return __($msgid);
    }
    
    return $msgid;
}

function url_theme($url)
{
    if (File::exists(base_path(Config::get('theme.theme') . DIRECTORY_SEPARATOR . $url))) {
        return url('themes' . DIRECTORY_SEPARATOR . Config::get('theme.theme') . DIRECTORY_SEPARATOR . $url);
    }
    
    return url($url);
}

function url_icon($icon)
{
    return url_theme('images/icons/icon_' . $icon . '.png');
}

/**
 * scrub_in
 * Run on inputs, stuff that might get stuck in our db
 */
function scrub_in($input)
{
    if (!is_array($input)) {
        return stripslashes(htmlspecialchars(strip_tags($input), ENT_QUOTES, config('system.site_charset')));
    } else {
        $results = array();
        foreach ($input as $item) {
            $results[] = scrub_in($item);
        }

        return $results;
    }
} // scrub_in

/**
 * scrub_out
 * This function is used to escape user data that is getting redisplayed
 * onto the page, it htmlentities the mojo
 * This is the inverse of the scrub_in function
 */
function scrub_out($string)
{
    return htmlentities($string, ENT_QUOTES, config('system.site_charset'));
} // scrub_out
