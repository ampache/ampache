<?php
/*
   Does emit a CAPTCHA graphic and form fields, which allows to tell real
   people from bots.
   Though a textual description is generated as well, this sort of access
   restriction will knock out visually impaired users, and frustrate all
   others anyhow. Therefore this should only be used as last resort for
   defending against spambots. Because of the readable text and the used
   colorspaces this is a weak implementation, not completely OCR-secure.

   captcha::form() will return a html string to be inserted into textarea/
   [save] <forms> and alike. User input is veryfied with captcha::check().
   You should leave the sample COLLEGE.ttf next to this script, else you
   have to define the _FONT_DIR constant correctly. Use only one type.

   Includes a sluggish workaround for Internet Explorer; but this script
   must reside in a www-accessible directory then.
   Public Domain, available via http://freshmeat.net/p/captchaphp
*/


#-- config
define("EWIKI_FONT_DIR", dirname(__FILE__));  // which fonts to use
define("CAPTCHA_INVERSE", 0);                 // white or black(=1)
define("CAPTCHA_TIMEOUT", 5000);              // in seconds (=max 4 hours)


/* static - (you could instantiate it, but...) */
class captcha {


   /* gets parameter from $_REQUEST[] array (POST vars) and so can
      verify input, @returns boolean
   */
   function check() {
      if (($hash = $_REQUEST["captcha_hash"])
      and ($pw = trim($_REQUEST["captcha_input"]))) {
         return((captcha::hash($pw)==$hash) || (captcha::hash($pw,-1)==$hash));
      }
   }

   
   /* yields <input> fields html string (no complete form), with captcha
      image already embedded as data:-URI
   */
   public static function form($title="&rarr; retype that here", $more="<small><br>Enter the correct letters and numbers from the image into the text box. <br>This small test serves as access restriction against malicious bots. <br>Simply reload the page if this graphic is too hard to read.</small>") {
      $pw = captcha::mkpass();
      $hash = captcha::hash($pw);
     $maxsize = (strpos("MSIE", $_SERVER["HTTP_USER_AGENT"]) ? 1000 : 6000);
      @header("Vary: User-Agent");
      $img = "data:image/jpeg;base64,"
           . base64_encode(captcha::image($pw, 200, 60, CAPTCHA_INVERSE, $maxsize));
      $alt = htmlentities(captcha::textual_riddle($pw));
      $test = substr($img,22);
      $html = 
        '<table summary="captcha input"><tr>'
      . '<td><img name="captcha_image" id="captcha_image" src="'.$img. '" height="60" width="200" alt="'.$alt. '" /></td>'
      . '<td>'.$title. '<br/><input name="captcha_hash" type="hidden" value="'.$hash. '" />'
      . '<font color="red">*</font><input name="captcha_input" type="text" size="7" maxlength="16" style="height:23px; font-size:16px; font-weight:450;" />'
      . '</td><td>'.$more.'</td>'
      . '</tr></table>';

      #-- js/html fix if ("MSIE")
      {
//         $base = "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]".$conf('web_path')."/modules/captcha/captcha.php";
	 $base = $_SERVER['PHP_SELF'];
         $html .= <<<END
<script language="Javascript"><!--
if (/Microsoft/.test(navigator.appName)) {
//    var msg= "You are using IE Please download firefox in order to register. http://www.mozilla.org/products/firefox";
//    alert(msg);
   var img = document.captcha_image;
  img.src = "$base?_ddu=$test"; 
  //alert (img.src);
    }
--></script>
END;
      }
      $html = "<div class=\"captcha\">$html</div>";
      return($html);
   }


   /* generates alternative (non-graphic), human-understandable
      representation of the passphrase
   */
   public static function textual_riddle($phrase) {
      $symbols0 = '"\'-/_:';
      $symbols1 = array("\n,", "\n;", ";", "\n&", "\n-", ",", ",", "\nand then", "\nfollowed by", "\nand", "\nand not a\n\"".chr(65+rand(0,26))."\",\nbut");
      $s = "Guess the letters and numbers\n(passphrase riddle)\n--\n";
      for ($p=0; $p<strlen($phrase); $p++) {
         $c = $phrase[$p];
         $add = "";
         #-- asis
         if (!rand(0,3)) {
            $i = $symbols0[rand(0,strlen($symbols0)-1)];
            $add = "$i$c$i";
         }
         #-- letter
         elseif ($c >= 'A') {
            $type = ($c >= 'a' ? "small " : "");
            do {
               $n = rand(-3,3);
               $c2 = chr((ord($c) & 0x5F) + $n);
            }
            while (($c2 < 'A') || ($c2 > 'Z'));
            if ($n < 0) {
               $n = -$n;
               $add .= "$type'$c2' +$n letters";
            }
            else {
               $add .= "$n chars before $type$c2";
            }
         }
         #-- number
         else {
            $add = "???";
            $n = (int) $c;
            do {
               do { $x = rand(1, 10); } while (!$x);
               $op = rand(0,11);
               if ($op <= 2) {
                  $add = "($add * $x)"; $n *= $x;
               }
               elseif ($op == 3) {
                  $x = 2 * rand(1,2);
                  $add = "($add / $x)"; $n /= $x;
               }
               elseif ($sel % 2) {
                  $add = "($add + $x)"; $n += $x;
               }
               else {
                  $add = "($add - $x)"; $n -= $x;
               }
            }
            while (rand(0,1));
            $add .= " = $n";
         }
         $s .= "$add";
         $s .= $symbols1[rand(0,count($symbols1)-1)] . "\n";
      }
      return($s);
   }


   /* returns jpeg file stream with unscannable letters encoded
      in front of colorful disturbing background
   */
   public static function image($phrase, $width=200, $height=60, $inverse=0, $maxsize=0xFFFFF) {
   
      #-- initialize in-memory image with gd library
      srand(microtime()*21017);
      $img = imagecreatetruecolor($width, $height);
      $R = $inverse ? 0xFF : 0x00;
      imagefilledrectangle($img, 0,0, $width,$height, captcha::random_color($img, 222^$R, 255^$R));
      $c1 = rand(150^$R, 185^$R);
      $c2 = rand(195^$R, 230^$R);
      
      #-- configuration
      $fonts = array(
        // "COLLEGE.ttf",
      );
      $fonts += glob(EWIKI_FONT_DIR."/*.ttf");
      
      #-- encolour bg
      $wd = 20;
      $x = 0;
      while ($x < $width) {
         imagefilledrectangle($img, $x, 0, $x+=$wd, $height, captcha::random_color($img, 222^$R, 255^$R));
         $wd += max(10, rand(0, 20) - 10);
      }

      #-- make interesting background I, lines
      $wd = 4;
      $w1 = 0;
      $w2 = 0;
      for ($x=0; $x<$width; $x+=(int)$wd) {
         if ($x < $width) {   // verical
            imageline($img, $x+$w1, 0, $x+$w2, $height-1, captcha::random_color($img,$c1,$c2));
         }
         if ($x < $height) {  // horizontally ("y")
            imageline($img, 0, $x-$w2, $width-1, $x-$w1, captcha::random_color($img,$c1,$c2));
         }
         $wd += rand(0,8) - 4;
         if ($wd < 1) { $wd = 2; }
         $w1 += rand(0,8) - 4;
         $w2 += rand(0,8) - 4;
         if (($x > $height) && ($y > $height)) {
            break;
         }
      }
      
      #-- more disturbing II, random letters
      $limit = rand(30,90);
      for ($n=0; $n<$limit; $n++) {
         $letter = "";
         do {
            $letter .= chr(rand(31,125)); // random symbol
         } while (rand(0,1));
         $size = rand(5, $height/2);
         $half = (int) ($size / 2);
         $x = rand(-$half, $width+$half);
         $y = rand(+$half, $height);
         $rotation = rand(60, 300);
         $c1  = captcha::random_color($img, 130^$R, 240^$R);
         $font = $fonts[rand(0, count($fonts)-1)];
         imagettftext($img, $size, $rotation, $x, $y, $c1, $font, $letter);
      }

      #-- add the real text to it
      $len = strlen($phrase);
      $w1 = 10;
      $w2 = $width / ($len+1);
      for ($p=0; $p<$len; $p++) {
         $letter = $phrase[$p];
         $size = rand(18, $height/2.2);
         $half = (int) $size / 2;
         $rotation = rand(-33, 33);
         $y = rand($size+3, $height-3);
         $x = $w1 + $w2*$p;
         $w1 += rand(-$width/90, $width/40);  // @BUG: last char could be +30 pixel outside of image
         $font = $fonts[rand(0, count($fonts)-1)];
         $r=rand(30,99); $g=rand(30,99); $b=rand(30,99); // two colors for shadow
         $c1  = imagecolorallocate($img, $r*1^$R, $g*1^$R, $b*1^$R);
         $c2  = imagecolorallocate($img, $r*2^$R, $g*2^$R, $b*2^$R);
         imagettftext($img, $size, $rotation, $x+1, $y, $c2, $font, $letter);
         imagettftext($img, $size, $rotation, $x, $y-1, $c1, $font, $letter);
      }

      #-- let JFIF stream be generated
/* Drop down quality if browser is MSIE */
if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$_SERVER["HTTP_USER_AGENT"],$matched)){
      $quality = 8;
      } else {
      $quality = 100;
      }

      $s = array();
      do {
         ob_start(); ob_implicit_flush(0);
         imagejpeg($img, "", (int)$quality);
         $jpeg = ob_get_contents(); ob_end_clean();
         $size = strlen($jpeg);
         $s_debug[] = ((int)($quality*10)/10) . "%=$size";
         $quality = $quality * ($maxsize/$size) * 0.93 - 1.7;  // -($quality/7.222)*
      }
      while (($size > $maxsize) && ($quality >= 16));
      imagedestroy($img);
#print_r($s_debug);
      return($jpeg);
   }


   /* helper code */
   public static function random_color($img, $a,$b) {
      return imagecolorallocate($img, rand($a,$b), rand($a,$b), rand($a,$b));
   }


   /* unreversable hash from passphrase, with time() slice encoded */
   public static function hash($text, $dtime=0) {
      $text = strtolower($text);
      $pfix = (int) (time() / CAPTCHA_TIMEOUT) + $dtime;
      return md5("captcha::$pfix:$text::".__FILE__.":$_SERVER[SERVER_NAME]:80");
   }


   /* makes string of random letters for embedding into image and for
      encoding as hash, later verification
   */
   public static function mkpass() {
      $s = "";
      for ($n=0; $n<10; $n++) {
         $s .= chr(rand(0, 255));
      }
      $s = base64_encode($s);   // base64-set, but filter out unwanted chars
      $s = preg_replace("/[+\/=IG0ODQR]/i", "", $s);  // (depends on YOUR font)
      $s = substr($s, 0, rand(5,7));
      return($s);
   }
}


#-- IE workaround
if (isset($_REQUEST["_ddu"])) {
   header("Content-Type: image/jpeg");
   die(base64_decode(substr($_REQUEST["_ddu"], 0)));
}


?>
