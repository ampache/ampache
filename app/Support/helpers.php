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
    if (File::exists(base_path(Config::get('theme.theme') . DIRECTORY_SEPARATOR . $url)))
        return url('themes' . DIRECTORY_SEPARATOR . Config::get('theme.theme') . DIRECTORY_SEPARATOR . $url);
    
    return url($url);
}

function url_icon($icon)
{
    return url_theme('images/icons/icon_' . $icon . '.png');
}