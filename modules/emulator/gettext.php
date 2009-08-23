<?php
/*

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*
  api: PHP
  type: functions
  title: gettext()
  description: emulates PHP gettext extension functionality
  priority: auto
  category: library

   This include script simulates gettext() functionality.
    - It could read translation data from .mo and .po files.
    - Lookup of plural forms mostly work (but not 100% compliant,
      no real interpreter for Plural-Forms: expression).
    - Categories/codesets are ignored.

   Besides using setlocale() you should change the $_ENV["LANG"] var
   to the desired language manually. Additionally all your scripts
   could contain following (may also work with standard gettext):
     $_ENV["LANGUAGE"] = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
   That's often more user-friendly than hardwired server-side values.
*/


#-- emulate only if not present in current PHP interpreter
if (!function_exists("gettext")) {

   #-- all-in-one combined implementation
   #   (in original API only the first parameter is present)
   function gettext($msg, $msg2=NULL, $domain=NULL, $category=NULL, $plural=NULL) {
      global $_GETTEXT;

      #-- get default params if corresponding args are empty
      if (!isset($domain)) {
         $domain = $_GETTEXT['%domain'];
      }
      if (empty($_GETTEXT[$domain])) {
         bindtextdomain($domain);  // auto load from system dirs
      }

      #-- plural array position
      if (!isset($plural)) {
         $pli = 0;
      }
      elseif ($ph = $_GETTEXT[$domain]['%plural-c']) {
         $pli = gettext___plural_guess($ph, $plural);
      }
      else {
         $pli = ($plural != 1) ? 1 : 0;   // English
      }

      #-- look up string
      if (isset($_GETTEXT[$domain][$msg]) && ($trans = $_GETTEXT[$domain][$msg])
      or ($pli) and ($trans = $_GETTEXT[$domain][$msg2]))
      {
         // handle plural entries
         if (is_array($trans)) {
            if (!isset($trans[$pli])) {
               $pli = 0;   // missing translation
            }
            $trans = $trans[$pli];
         }
         // only return, if something found
         if (strlen($trans)) {
            $msg = $trans;
         }
      }

      #-- handle $category (???)
      // recode() ...

      #-- give out whatever we have
      return($msg);
   }
   

   #-- return plural form array index for algorithm type
   #   (compacted from C expression string beforehand)
   function gettext___plural_guess(&$type, $n) {
   
      #-- guess from string with C expression and set integer shorthand
      if (is_string($type)) {
         if (($type == "nplurals=1;plural=0;") || !strlen($type)) {
            $type = -1; // no plurals
         }
         elseif ($type == "nplurals=2;plural=n!=1;") {
            $type = 1;  // English
         }
         elseif ($type == "nplurals=2;plural=n>1;") {
            $type = 2;  // French
         }
         // special cases
         elseif (strpos($type, "n%100!=11")) {
            if (strpos($type, "n!=0")) {
               $type == 21;  // Latvian
            }
            if (strpos($type, "n%10<=4")) {
               $type = 22;   // a few Slavic langs (code similar to Polish below)
            }
            if (strpos($type, "n%10>=2")) {  // Lithuanian
               $type = 23;
            }
            $type = 0;
         }
         // specials, group 2
         elseif (strpos($type, "n<=4")) {   // Slovak
            $type = 25;
         }
         elseif (strpos($type, "n==2")) {   // Irish
            $type = 31;
         }
         elseif (strpos($type, "n%10>=2")) {   // Polish
            $type = 26;
         }
         elseif (strpos($type, "n%100==3")) {   // Slovenian
            $type = 28;
         }
         // fallbacks
         elseif (strpos($type, ";plural=n;")) {
            $type = 7;  // unused
         }
         // first at this point a tokenizer/parser/interpreter would have made sense
         else {
            $type = 0;  // no plurals
         }
      }

      #-- return plural index value from pre-set formulas
      switch ($type) {
         case -1:  // no plural forms
            return(0);
         case 1:   // English, and lots of others...
            return($n != 1 ? 1 : 0);
         case 2:   // French, Brazilian Protuguese
            return($n > 1 ? 1 : 0);
         case 7:   // unused
            return($n);

         case 21:  // Latvian
            return  (($n%10==1) && ($n%100!=11)) ? (0) :  ($n!=0 ? 1 : 2)  ;
         case 22:  // Slavic langs
            return  ($n%10==1) && ($n%100!=11) ? 0 :
               ( ($n%10>=2) && ($n%10<=4) && ($n%100<10 || $n%100>=20) ? 1 : 2  )  ;
         case 23:  // Lithuanian
            return  ($n%10==1) && ($n%100!=11) ? 0 :
               ( ($n%10>=2) && ($n%100<10 || $n%100>=20) ? 1 : 2  )  ;
         case 25:  // Slovak
            return  $n==1 ? 0 : ($n>=2 && $n<=4 ? 1 : 2)  ;
         case 26:  // Polish
             return  $n==1 ? 0 : ( $n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2 )  ;
         case 28:  // Slovenian
            return  $n%100==1 ? 0 : ($n%100==2 || $n%100==3 || $n%100==4 ? 2 : 3)  ;
         case 31:  // Irish
            return  ($n == 1) ? (0) : (($n == 2) ? 1 : 2)  ;

         default:
            $type = -1;
      }   // unsupported, always return non-plural index [0]
      return(0);
   }


   #-- wrappers around monster function above
   function ngettext($msg1, $msg2, $plural) {
      return gettext($msg1, $msg2, NULL, NULL, $plural);
   }
   function dngettext($domain, $msg1, $msg2, $plural) {
      return gettext($msg1, $msg2, $domain, NULL, $plural);
   }
   function dcngettext($domain, $msg1, $msg2, $plural, $category) {
      return gettext($msg1, $msg2, $domain, $category, $plural);
   }
   function dcgettext($domain, $msg, $category) {
      return gettext($msg, NULL, $domain, $category);
   }
   function dgettext($domain, $msg) {
      return gettext($msg, NULL, $domain);
   }


   #-- sets current translation data source
   #   (must have been loaded beforehand)
   function textdomain($default="NULL") {
      global $_GETTEXT;
      $prev = isset($_GETTEXT['%domain']) ? $_GETTEXT['%domain'] : NULL;
      if (isset($default)) {
         $_GETTEXT['%domain'] = $default;
      }
      return $prev;
   }


   #-- loads data files
   function bindtextdomain($domain, $directory="/usr/share/locale:/usr/local/share/locale:./locale") {
      global $_GETTEXT;
      if (isset($_GETTEXT[$domain]) && (count($_GETTEXT[$domain]) > 3)) {
         return;  // don't load twice
      }
      $_GETTEXT[$domain]['%dir'] = $directory;
      $_GETTEXT['%locale'] = setlocale(LC_CTYPE, 0);

      #-- allowed languages
      $langs = @$_ENV['LANGUAGE'] . ',' . @$_ENV['LC_ALL'] . ','
             . @$_ENV['LC_MESSAGE'] .',' . @$_ENV['LANG'] . ','
             . @$_GETTEXT['%locale'] . ',' . @$_SERVER['HTTP_ACCEPT_LANGUAGE']
             . ',C,en';
          
      #-- add shortened language codes (en_UK.UTF-8 -> + en_UK, en)
      foreach (explode(',', $langs) as $d) {
         $d = trim($d);
         // $dir2[] = $d;
         $d = strtok($d, "@.-+=%:; ");
         if (strlen($d)) {
            $dir2[] = $d;
         }
         if (strpos($d, '_')) {
            $dir2[] = strtok($d, '_');
         }
      }

      #-- search for matching directory and load data file
      foreach (explode(':', $directory) as $directory) {
         foreach ($dir2 as $lang) {
            $base_fn = "$directory/$lang/LC_MESSAGES/$domain";

#echo "GETTEXT:$lang:$base_fn\n";

            #-- binary format
            if (file_exists($fn = "$base_fn.mo") && ($f = fopen($fn, "rb")))
            {
               gettext___load_mo($f, $domain);
               break 2;
            }

            #-- text file
            elseif (function_exists("gettext___load_po")
            and file_exists($fn = "$base_fn.po") && ($f = fopen($fn, "r")))
            {
               gettext___load_po($f, $domain);
               break 2;
            }
         }
      }//foreach
      
      #-- extract headers
      if ($head = $_GETTEXT[$domain][""]) {
         foreach (explode("\n", $head) as $line) {
            $header = strtok(':', $line);
            $line = trim(strtok("\n"));
            $_GETTEXT[$domain]['%po-header'][strtolower($header)] = $line;
         }
      
         #-- plural-forms header
         if (function_exists("gettext___plural_guess")
         and ($h = @$_GETTEXT[$domain]['%po-header']["plural-forms"]))
         {
            $h = preg_replace("/[(){}\[\]^\s*\\]+/", "", $h);  // rm whitespace
            gettext___plural_guess($h, 0);  // pre-decode into algorithm type integer
            $_GETTEXT[$domain]['%plural-c'] = $h;
         }
      }

      #-- set as default textdomain
      if (empty($_GETTEXT['%domain'])) {
         textdomain($domain);
      }
      return($domain);
   }


   #-- load string data from binary .mo files (ign checksums)
   function gettext___load_mo($f, $domain) {
      global $_GETTEXT;

      #-- read in data file completely
      $data = fread($f, 1<<20);
      fclose($f);

      #-- extract header fields and check file magic
      if ($data) {
         $header = substr($data, 0, 20);
         $header = unpack("L1magic/L1version/L1count/L1o_msg/L1o_trn", $header);
         extract($header);
         if ((dechex($magic) == "950412de") && ($version == 0)) {

            #-- fetch all entries
            for ($n=0; $n<$count; $n++) {

               #-- msgid
               $r = unpack("L1len/L1offs", substr($data, $o_msg + $n * 8, 8));
               $msgid = substr($data, $r["offs"], $r["len"]);
               unset($msgid_plural);
               if (strpos($msgid, "\000")) {
                  list($msgid, $msgid_plural) = explode("\000", $msgid);
               }

               #-- translation(s)
               $r = unpack("L1len/L1offs", substr($data, $o_trn + $n * 8, 8));
               $msgstr = substr($data, $r["offs"], $r["len"]);
               if (strpos($msgstr, "\000")) {
                  $msgstr = explode("\000", $msgstr);
               }

               #-- add
               $_GETTEXT[$domain][$msgid] = $msgstr;
               if (isset($msgid_plural)) {
                  $_GETTEXT[$domain][$msgid_plural] = &$_GETTEXT[$domain][$msgid];
               }
            }

         }
      }
   }


   #-- read from textual .po source file (not fully correct, and redundant
   #   because the original gettext/libintl doesn't support this at all)
   function gettext___load_po($f, $domain) {
      global $_GETTEXT;
      $c_esc = array("\\n"=>"\n", "\\r"=>"\r", "\\\\"=>"\\", "\\f"=>"\f", "\\t"=>"\t", "\\"=>"");

      #-- read line-wise from text file   
      do {
         $line = trim(fgets($f));

         #-- check what's in the current line
         $space = strpos($line, " ");
         // comment
         if ($line[0] == "#") {
            //continue;
         }
         // msgid
         elseif (strncmp($line, "msgid", 5)==0) {
            $msgid[] = trim(substr($line, $space+1), '"');
         }
         // translation
         elseif (strncmp($line, "msgstr", 6)==0) {
            $msgstr[] = trim(substr($line, $space+1), '"');
         }
         // continued (could be _id or _str)
         elseif ($line[0] == '"') {
            $line = trim($line, '"');
            if (isset($msggstr)) {
               $msgstr[count($msgstr)] .= $line;
            }
            else {
               $msgid[count($msgid)] .= $line;
            }
         }

         #-- append to global $_GETTEXT hash as soon as we have a complete dataset
         if (isset($msgid) && isset($msgstr) && (empty($line) || ($line[0]=="#") || feof($f)) )
         {
            $msgid[0] = strtr($msgid[0], $c_esc);
            foreach ($msgstr as $v) {
               $_GETTEXT[$domain][$msgid[0]] = strtr($v, $c_esc);
            }
            if ($msgid[1]) {
               $msgid[1] = strtr($msgid[1], $c_esc);
               $_GETTEXT[$domain][$msgid[1]] = &$_GETTEXT[$domain][$msgid[0]];
            }

            $msgid = array();
            $msgstr = array();
         }

      } while (!feof($f));
      fclose($f);
   }


   #-- ignored setting (no idea what it really should do)
   function bind_textdomain_codeset($domain, $codeset) {
      global $_GETTEXT;
      $_GETTEXT[$domain]["%codeset"] = $codeset;
      return($domain);
   }


}


#-- define gettexts preferred function name _ separately
if (!function_exists("_")) {
   function _($str) {
      return gettext($str);
   }
}



?>
