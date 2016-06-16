<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;

class AjaxController extends Controller
{
    protected function xml_from_array($array)
    {
        $string = '';
        foreach ($array as $key => $value) {
            // No numeric keys
            if (is_numeric($key)) {
                $key = 'item';
            }

            if (is_array($value)) {
                // Call ourself
                $value = xml_from_array($value, true);
                $string .= "\t<content div=\"$key\">$value</content>\n";
            } else {
                /* We need to escape the value */
                $string .= "\t<content div=\"$key\"><![CDATA[$value]]></content>\n";
            }
        // end foreach elements
        }
        $string = '<?xml version="1.0" encoding="utf-8" ?>' .
            "\n<root>\n" . $string . "</root>\n";

        return \UI::clean_utf8($string);
    }
    
    protected function returnError()
    {
        return $this->xml_from_array(['rfc3514' => '0x1']);
    }
}
