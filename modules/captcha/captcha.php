<?php
/*
   api: php
   title: Easy_CAPTCHA
   description: highly configurable, user-friendly and accessible CAPTCHA
   version: 2.0
   author: milki
   url: http://freshmeat.net/p/captchaphp
   config:
      <const name="CAPTCHA_PERSISTENT" value="1"  type="boolean" title="persistent cookie" description="sets a cookie after user successfully solved it, spares further captchas for a few days" />
      <const name="CAPTCHA_NEW_URLS" value="1"  type="boolean" title="new URLs only Javascript" description="uses Javascript detection to engage CAPTCHA only if a new URL was entered into any input box" />
      <const name="CAPTCHA_AJAX" value="1" type="boolean" title="AJAX quickcheck" description="verfies the solution (visually) while user enters it" />
      <const name="CAPTCHA_IMAGE_SIZE" value="200x60" type="string" regex="\d+x\d+" title="image size" description="height x width of CAPTCHA image" />
      <const name="CAPTCHA_INVERSE" value="1"  type="boolean" title="inverse color" description="make captcha white on black" />
      <const name="CAPTCHA_PIXEL" value="1" type="multi" multi="1=single pixel|2=greyscale 2x2|3=smooth color" title="smooth drawing" description="image pixel assembly method and speed" />
      <const name="CAPTCHA_ONCLICK_HIRES" value="1" type="boolean" title="onClick-HiRes" description="reloads a finer resolution version of the CAPTCHA if user clicks on it" />
      <const name="CAPTCHA_TIMEOUT" value="5000" type="string" regex="\d+" title="verification timeout" description="in seconds, maxiumum time to elaps from CAPTCHA display to verification" />
   type: intercept
   category: antispam
   priority: optional


   This library operates CAPTCHA form submissions, to block spam bots and
   alike. It is easy to hook into existing web sites and scripts. It also
   tries to be "smart" and more user-friendly.
   
   While the operation logic and identifier processing are extremley safe,
   this is a "weak" implementation. Specifically targetted and tweaked OCR
   software could overcome the visual riddle. And if enabled, the textual
   or mathematical riddles are rather simple to overcome if attacked.
   Generic spambots are however blocked already with the default settings.
   
   PRINT captcha::form()
     emits the img and input fields for inclusion into your submit <form>
   
   IF (captcha::solved())
     tests for a correctly entered solution on submit, returns true if ok
   
   Temporary files are created for tracking, verification and basic data
   storage, but will get automatically removed once a CAPTCHA was solved
   to prevent replay attacks. Additionally this library uses "AJAX" super
   powers *lol* to enhance usability. And a short-lasting session cookie
   is also added site-wide, so users may only have to solve the captcha
   once (can be disabled, because that's also just security by obscurity).
   
   Public Domain, available via http://freshmeat.net/p/captchaphp
*/

#-- behaviour
define("CAPTCHA_PERSISTENT", 0);     // cookie-pass after it's solved once (does not work if headers were already sent on innovocation of captcha::solved() check)
define("CAPTCHA_NEW_URLS", 1);       // force captcha only when URLs submitted
define("CAPTCHA_AJAX", 1);           // visual feedback while entering letters
define("CAPTCHA_LOG", 0);            // create /tmp/captcha/log file
define("CAPTCHA_NOTEXT", 0);         // disables the accessible text/math riddle

#-- look
define("CAPTCHA_IMAGE_TYPE", 2);     // 1=wave, 2=whirly
define("CAPTCHA_INVERSE", 1);        // white or black(=1)
define("CAPTCHA_IMAGE_SIZE", "200x60");  // randomly adapted a little
define("CAPTCHA_INPUT_STYLE", "height:46px; font-size:34px; font-weight:450;");
define("CAPTCHA_PIXEL", 1);          // set to 2 for smoother 2x2 grayscale pixel transform
define("CAPTCHA_ONCLICK_HIRES", 1);  // use better/slower drawing mode on reloading

#-- solving
define("CAPTCHA_FUZZY", 0.65);       // easier solving: accept 1 or 2 misguessed letters
define("CAPTCHA_TRIES", 5);          // maximum failures for solving the captcha
define("CAPTCHA_AJAX_TRIES", 25);    // AJAX testing limit (prevents brute-force cracking via check API)
define("CAPTCHA_MAXPASSES", 2);      // 2 passes prevent user annoyment with caching/reload failures
define("CAPTCHA_TIMEOUT", 5000);     // (in seconds/2) = 3:00 hours to solve a displayed captcha
define("CAPTCHA_MIN_CHARS", 7);      // how many letters to use
define("CAPTCHA_MAX_CHARS", 9);

#-- operation
define("CAPTCHA_TEMP_DIR", (@$_SERVER['TEMP'] ? $_SERVER['TEMP'] : '/tmp') . "/captcha/");
define("CAPTCHA_PARAM_ID", "__ec_i");
define("CAPTCHA_PARAM_INPUT", "__ec_s");
define("CAPTCHA_BGCOLOR", 0xFFFFFF);   // initial background color (non-inverse, white)
define("CAPTCHA_SALT", ",e?c:7<");
#define("CAPTCHA_DATA_URLS", 0);     // RFC2397-URLs exclude MSIE users
define("CAPTCHA_FONT_DIR", dirname(__FILE__));
//define("CAPTCHA_BASE_URL", "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]/" . substr(realpath(__FILE__), strlen(realpath($_SERVER["DOCUMENT_ROOT"]))));
define("CAPTCHA_BASE_URL", Config::get('web_path') . "/modules/captcha/captcha.php");

/* simple API */
class captcha {

   #-- tests submitted CAPTCHA solution against tracking data
   function solved() {
      $c = new easy_captcha();
      return $c->solved();
   }
   function check() {
      return captcha::solved();
   }

   #-- returns string with "<img> and <input>" fields for display in your <form>
   function form($text="") {
      $c = new easy_captcha();
      return $c->form("$text");
   }
}



#-- init (triggered if *this* script is called directly)
if (realpath(strtok("$_SERVER[DOCUMENT_ROOT]/$_SERVER[REQUEST_URI]","?#"))==realpath(__FILE__)) {
   easy_captcha_utility::API();
}










/* base logic and data storare */
class easy_captcha {


   #-- init data
   function easy_captcha($id=NULL, $ignore_expiration=0) {

      #-- load
      if (($this->id = $id) or ($this->id = preg_replace("/[^-,.\w]+/", "", @$_REQUEST[CAPTCHA_PARAM_ID]))) {
         $this->load();
      }

      #-- create new
      if (empty($this->id) || !$ignore_expiration && !$this->is_valid() && $this->log("new()", "EXPIRED", "regenerating store")) {
         $this->generate();
      }
   }


   #-- create solutions
   function generate() {

      #-- init
      srand(microtime() + time()/2 - 21017);
      if ($this->id) { $this->prev[] = $this->id; }
      $this->id = $this->new_id();
      
      #-- meta informations
      $this->created = time();
      $this->{'created$'} = gmdate("r", $this->created);
      $this->expires = $this->created + CAPTCHA_TIMEOUT;
      //$this->tries = 0;
      $this->passed = 0;

      #-- captcha processing info
      $this->sent = 0;
      $this->tries = CAPTCHA_TRIES;  // 5
      $this->ajax_tries = CAPTCHA_AJAX_TRIES;  // 25
      $this->passed = 0;
      $this->maxpasses = CAPTCHA_MAXPASSES;   // 2
      $this->failures = 0;
      $this->shortcut = array();
      $this->grant = 0;  // unchecked access
      
      #-- mk IMAGE/GRAPHIC
      $this->image = (CAPTCHA_IMAGE_TYPE <= 1)
                   ? new easy_captcha_graphic_image_waved()
                   : new easy_captcha_graphic_image_disturbed();
    //$this->image = new easy_captcha_graphic_cute_ponys();
      
      #-- mk MATH/TEXT riddle
      $this->text = (CAPTCHA_NOTEXT >= 1)
                  ? new easy_captcha_text_disable()
                  : new easy_captcha_text_math_formula();
    //$this->text = new easy_captcha_text_riddle();

      #-- process granting cookie
      if (CAPTCHA_PERSISTENT)
      $this->shortcut[] = new easy_captcha_persistent_grant();

      #-- spam-check: no URLs submitted
      if (CAPTCHA_NEW_URLS)
      $this->shortcut[] = new easy_captcha_spamfree_no_new_urls();

      #-- store record   
      $this->save();
   }
   
   
   #-- examine if captcha data is fresh
   function is_valid() {
      return isset($this->id) && ($this->created)
          && ($this->expires > time())
          && ($this->tries > 0)
          && ($this->failures < 500)
          && ($this->passed < $this->maxpasses)
          || $this->delete() || $this->log("is_valid", "EXPIRED", "and deleted") && false;
   }


   #-- new captcha tracking/storage id
   function new_id() {
      return "ec." . time() . "." . md5( $_SERVER["SERVER_NAME"] . CAPTCHA_SALT . rand(0,1<<30) );
   }


   #-- check backends for correctness of solution
   function solved() {
      $ok = false;

      #-- failure
      if ((0 >= $this->tries--) || !$this->is_valid()) {
         // log, this is either a frustrated user or a bot knocking
         $this->log("::solved", "INVALID", "tries exhausted ($this->tries) or expired(?) captcha");
      }
      
      #-- test
      elseif ($this->sent) {
         $in = $_REQUEST[CAPTCHA_PARAM_INPUT];  // might be empty string
      
         #-- check individual modules
         $ok = $this->grant;
         foreach ($this->shortcut as $test) {
            $ok = $ok || $test->solved($in);    // cookie & nourls
         }
         $ok = $ok  // either letters or math formula submitted
             || isset($this->image) && $this->image->solved($in)
             || isset($this->text) && $this->text->solved($in);
               
         #-- update state
         if ($ok) {
            $this->passed++;
            $this->log("::solved", "OKAY", "captcha passed ($in) for image({$this->image->solution}) and text({$this->text->solution})");
            
            #-- set cookie on success
            if (CAPTCHA_PERSISTENT) {
               $this->shortcut[0/*FIXME*/]->grant();
               $this->log("::solved", "PERSISTENT", "cookie granted");
            }
         }
         else {
            $this->failures++;
            $this->log("::solved", "WRONG", "solution failure ($in) for image({$this->image->solution}) and text({$this->text->solution})");
         }
      }

      #-- remove if done
      if (!$this->is_valid() /*&& !$this->delete()*/) {
         $this->generate(); // ensure object instance can be reused - for quirky form processing logic
      }
      #-- store state/result
      else {
         $this->save();
      }

      #-- return result
      return($ok);
   }
   
   
   #-- combines ->image and ->text data into form fields
   function form($add_text="") {

      #-- store object data
      $this->sent++;
      $this->save();
      
      #-- prepare output vars
      $p_id = CAPTCHA_PARAM_ID;
      $p_input = CAPTCHA_PARAM_INPUT;
      $base_url = CAPTCHA_BASE_URL . '?' . CAPTCHA_PARAM_ID . '=';
      $id = htmlentities($this->id);
      $img_url = $base_url . $id;
      $alt_text = htmlentities($this->text->question);
      $new_urls = CAPTCHA_NEW_URLS ? 0 : 1;
      $onClick = CAPTCHA_ONCLICK_HIRES ? 'onClick="this.src += this.src.match(/hires/) ? \'.\' : \'hires=1&\';"' : 'onClick="this.src += \'.\';"';
      $onKeyDown = CAPTCHA_AJAX ? 'onKeyDown="captcha_check_solution()"' : '';
      $javascript = CAPTCHA_AJAX ? '<script src="'.$base_url.'base.js&captcha_new_urls='.$new_urls.'" type="text/javascript" language="JavaScript" id="captcha_ajax_1"></script>' : '';

      #-- assemble
      $HTML =
         '<div id="captcha" class="captcha">' .
         '<input type="hidden" id="'.$p_id.'" name="'.$p_id.'" value="'.$id.'" />' .
         '<img src="'.$img_url .'&" width="'.$this->image->width.'" height="'.$this->image->height.'" alt="'.$alt_text.'" align="middle" '.$onClick.' title="click on image to redraw" />' .
         '&nbsp;' .
         $add_text .
         '<input title="please enter the letters you recognize in the CAPTCHA image to the left" type="text" '.$onKeyDown.' id="'.$p_input.'" name="'.$p_input.'" value="'.htmlentities($_REQUEST[$p_input]) .
         '" size="8" style="'.CAPTCHA_INPUT_STYLE.'" />' .
         $javascript .
         '</div>';

      return($HTML);
   }



   #-- noteworthy stuff goes here
   function log($error, $category, $message) {
      // append to text file
      if (CAPTCHA_LOG && ($log = @fopen(CAPTCHA_TEMP_DIR . "log", "a+"))) {
         flock($log, LOCK_EX);
         fwrite($log, "[$error] -$category- \"$message\" $_SERVER[REMOTE_ADDR] id={$this->id} tries={$this->tries} failures={$this->failures} created/time/expires=$this->created/".time()."/$this->expires \n");
         fclose($log);
      }
      return(TRUE);   // for if-chaining
   }


   #-- load object from saved captcha tracking data
   function load() {
      $fn = $this->data_file();
      if (file_exists($fn)) {
         $saved = (array)@unserialize(fread(fopen($fn, "r"), 1<<20));
         foreach ($saved as $i=>$v) {
            $this->{$i} = $v;
         }
      }
      else {
        // log
      }
   }

   #-- save $this captcha state
   function save() {
      $this->straighten_temp_dir();
      if ($fn = $this->data_file()) {
         $f = fopen($fn, "a");
         if (flock($f, LOCK_EX)) {
            ftruncate($f, 0);
            fwrite($f, serialize($this));
         }
         fclose($f);
      }
   }
   
   #-- remove $this data file
   function delete() {
      // delete current and all previous data files
      $this->prev[] = $this->id;
      if (isset($this->prev)) {
         foreach ($this->prev as $id) {
            @unlink($this->data_file($id));
         }
      }
      // clean object
      foreach ((array)$this as $name=>$val) {
         unset($this->{$name});
      }
      return(FALSE);  // far if-chaining in ->is_valid()
   }
   
   #-- clean-up or init temporary directory
   function straighten_temp_dir() {
      // create dir
      if (!file_exists($dir=CAPTCHA_TEMP_DIR)) {
         mkdir($dir);
      }
      // clean up old files
      if ((!rand(0,50)) && ($dh = opendir($dir))) {
         $t_kill = time() - CAPTCHA_TIMEOUT * 2;
         while($fn = readdir($dh)) if ($fn[0] != ".") {
            if (filemtime("$dir/$fn") < $t_kill) {
               @unlink("$dir/$fn");
            }
         }
      }
   }

   #-- where's the storage?
   function data_file($id=NULL) {
      return CAPTCHA_TEMP_DIR . preg_replace("/[^-,.\w]/", "", ($id?$id:$this->id)) . ".a()";
   }


   #-- unreversable hash from passphrase, with time() slice encoded
   function hash($text, $dtime=0, $length=1) {
      $text = strtolower($text);
      $pfix = (int) (time() / $length*CAPTCHA_TIMEOUT) + $dtime;
      return md5("captcha::$pfix:$text::".__FILE__.":$_SERVER[SERVER_NAME]:80");
   }
   
}









#-- checks the supplied solution, allows differences (incorrectly guessed letters)
class easy_captcha_fuzzy extends easy_captcha {

   #-- ratio of letters that may differ between solution and real password
   var $fuzzy = CAPTCHA_FUZZY;

   #-- compare
   function solved($in) {
      if ($in) {
         $pw = strtolower($this->solution);
         $in = strtolower($in);
         $diff = levenshtein($pw, $in);
         $maxdiff = strlen($pw) * (1 - $this->fuzzy);
         return ($pw == $in) or ($diff <= $maxdiff);  // either matches, or allows around 2 divergent letters
      }
   }

}









#-- image captchas, base and utility code
class easy_captcha_graphic extends easy_captcha_fuzzy {

   #-- config
   function easy_captcha_graphic($x=NULL, $y=NULL) {
      if (!$y) {
         $x = strtok(CAPTCHA_IMAGE_SIZE, "x,|/*;:");
         $y = strtok(",.");
         $x = rand($x * 0.9, $x * 1.2);
         $y = rand($y - 5, $y + 15);
      }
      $this->width = $x;
      $this->height = $y;
      $this->inverse = CAPTCHA_INVERSE;
      $this->bg = CAPTCHA_BGCOLOR;
      $this->maxsize = 0xFFFFF;
      $this->quality = 66;
      $this->solution = $this->mkpass();
   }


   #-- return a single .ttf font filename
   function font() {
      $fonts = array(/*"FreeMono-Medium.ttf"*/);
      $fonts += glob(CAPTCHA_FONT_DIR."/*.ttf");
      return $fonts[rand(0,count($fonts)-1)];
   }


   #-- makes string of random letters (for embedding into image)
   function mkpass() {
      $s = "";
      for ($n=0; $n<10; $n++) {
         $s .= chr(rand(0, 255));
      }
      $s = base64_encode($s);   // base64-set, but filter out unwanted chars
      $s = preg_replace("/[+\/=IG0ODQR]/i", "", $s);  // strips hard to discern letters, depends on used font type
      $s = substr($s, 0, rand(CAPTCHA_MIN_CHARS, CAPTCHA_MAX_CHARS));
      return($s);
   }


   #-- return GD color
   function random_color($a,$b) {
      $R = $this->inverse ? 0xFF : 0x00;
      return imagecolorallocate($this->img, rand($a,$b)^$R, rand($a,$b)^$R, rand($a,$b)^$R);
   }
   function rgb ($r,$g,$b) {
      $R = $this->inverse ? 0xFF : 0x00;
      return imagecolorallocate($this->img, $r^$R, $g^$R, $b^$R);
   }


   #-- generate JPEG output
   function output() {
      ob_start();
      ob_implicit_flush(0);
        imagejpeg($this->img, "", $this->quality);
        $jpeg = ob_get_contents();
      ob_end_clean();
      imagedestroy($this->img);
      unset($this->img);
      return($jpeg);
   }
}









#-- waived captcha image II
class easy_captcha_graphic_image_waved extends easy_captcha_graphic {


   /* returns jpeg file stream with unscannable letters encoded 
      in front of colorful disturbing background
   */
   function jpeg() {
      #-- step by step
      $this->create();
      $this->text();
      //$this->debug_grid();
      $this->fog();
      $this->distort();
      return $this->output();
   }


   #-- initialize in-memory image with gd library
   function create() {
      $this->img = imagecreatetruecolor($this->width, $this->height);
     // imagealphablending($this->img, TRUE);
      imagefilledrectangle($this->img, 0,0, $this->width,$this->height, $this->inverse ? $this->bg ^ 0xFFFFFF : $this->bg); //$this->rgb(255,255,255)
      if (function_exists("imageantialias")) {
         imageantialias($this->img, true);
      }
   }


   #-- add the real text to it
   function text() {
      $w = $this->width;
      $h = $this->height;
      $SIZE = rand(30,36);
      $DEG = rand(-2,9);
      $LEN = strlen($this->solution);
      $left = $w - $LEN * 25;
      $top = ($h - $SIZE - abs($DEG*2));
      imagettftext($this->img, $SIZE, $DEG, rand(5,$left-5), $h-rand(3, $top-3), $this->rgb(0,0,0), $this->font(), $this->solution);
   }

   #-- to visualize the sinus waves
   function debug_grid() {
      for ($x=0; $x<250; $x+=10) {
         imageline($this->img, $x, 0, $x, 70, 0x333333);
         imageline($this->img, 0, $x, 250, $x, 0x333333);
      }
   }
   
   #-- add lines
   function fog() {
      $num = rand(10,25);
      $x = $this->width;
      $y = $this->height;
      $s = rand(0,270);
      for ($n=0; $n<$num; $n++) {
         imagesetthickness($this->img, rand(1,2));
         imagearc($this->img,
            rand(0.1*$x, 0.9*$x), rand(0.1*$y, 0.9*$y),  //x,y
            rand(0.1*$x, 0.3*$x), rand(0.1*$y, 0.3*$y),  //w,h
            $s, rand($s+5, $s+90),     // s,e
            rand(0,1) ? 0xFFFFFF : 0x000000   // col
         );
      }
      imagesetthickness($this->img, 1);
   }

   
   #-- distortion: wave-transform
   function distort() {

      #-- init
      $single_pixel = (CAPTCHA_PIXEL<=1);   // very fast
      $greyscale2x2 = (CAPTCHA_PIXEL<=2);   // quicker than exact smooth 2x2 copy
      $width = $this->width;
      $height = $this->height;
      $i = & $this->img;
      $dest = imagecreatetruecolor($width, $height);
      
      #-- URL param ?hires=1 influences used drawing scheme
      if (isset($_GET["hires"])) {
         $single_pixel = 0;
      }

      #-- prepare distortion
      $wave = new easy_captcha_dxy_wave($width, $height);
      $spike = new easy_captcha_dxy_spike($width, $height);

      #-- generate each new x,y pixel individually from orig $img
      for ($y = 0; $y < $height; $y++) {
         for ($x = 0; $x < $width; $x++) {

            #-- pixel movement
            list($dx, $dy) = $wave->dxy($x, $y);   // x- and y- sinus wave
           // list($qx, $qy) = $spike->dxy($x, $y);
            
            #-- get source pixel, paint dest
            if ($single_pixel) {
               // single source dot: one-to-one duplicate (unsmooth, hard edges)
               imagesetpixel($dest, $x, $y, @imagecolorat($i, (int)$dx+$x, (int)$dy+$y));
            }
            elseif ($greyscale2x2) {
               // merge 2x2 simple/greyscale (3 times as slow)
               $cXY = $this->get_2x2_greyscale($i, $x+$dx, $y+$dy);
               imagesetpixel($dest, $x,$y, imagecolorallocate($dest, $cXY, $cXY, $cXY));
            }
            else {
               // exact and smooth transformation (5 times as slow)
               list($cXY_R, $cXY_G, $cXY_B) = $this->get_2x2_smooth($i, $x+$dx, $y+$dy);
               imagesetpixel($dest, $x,$y, imagecolorallocate($dest, (int)$cXY_R, (int)$cXY_G, (int)$cXY_B));
            }

         }
      }

      #-- simply overwrite ->img
      imagedestroy($i);      
      $this->img = $dest;
   }
   
   #-- get 4 pixels from source image, merges BLUE value simply
   function get_2x2_greyscale(&$i, $x, $y) {
       // this is pretty simplistic method, actually adds more artefacts
       // than it "smoothes"
       // it just merges the brightness from 4 adjoining pixels into one
       $cXY = (@imagecolorat($i, $x+$dx, $y+$dy) & 0xFF)
            + (@imagecolorat($i, $x+$dx, $y+$dy+1) & 0xFF)
            + (@imagecolorat($i, $x+$dx+1, $y+$dy) & 0xFF)
            + (@imagecolorat($i, $x+$dx+1, $y+$dy+1) & 0xFF);
       $cXY = (int) ($cXY / 4);
       return $cXY;
   }

   #-- smooth pixel reading (with x,y being reals, not integers)
   function get_2x2_smooth(&$i, $x, $y) {
       // get R,G,B values from 2x2 source area
       $c00 = $this->get_RGB($i, $x, $y);      //  +------+------+
       $c01 = $this->get_RGB($i, $x, $y+1);    //  |dx,dy | x1,y0|
       $c10 = $this->get_RGB($i, $x+1, $y);    //  | rx-> |      | 
       $c11 = $this->get_RGB($i, $x+1, $y+1);  //  +----##+------+
       // weighting by $dx/$dy fraction part   //  |    ##|<-ry  |
       $rx = $x - floor($x);  $rx_ = 1 - $rx;  //  |x0,y1 | x1,y1|
       $ry = $y - floor($y);  $ry_ = 1 - $ry;  //  +------+------+
       // this is extremely slow, but necessary for correct color merging,
       // the source pixel lies somewhere in the 2x2 quadrant, that's why
       // RGB values are added proportionately (rx/ry/_)
       // we use no for-loop because that would slow it even further
       $cXY_R = (int) (($c00[0]) * $rx_ * $ry_)
              + (int) (($c01[0]) * $rx_ * $ry)      // division by 4 not necessary,
              + (int) (($c10[0]) * $rx * $ry_)      // because rx/ry/rx_/ry_ add up
              + (int) (($c11[0]) * $rx * $ry);      // to 255 (=1.0) at most
       $cXY_G = (int) (($c00[1]) * $rx_ * $ry_)
              + (int) (($c01[1]) * $rx_ * $ry)
              + (int) (($c10[1]) * $rx * $ry_)
              + (int) (($c11[1]) * $rx * $ry);
       $cXY_B = (int) (($c00[2]) * $rx_ * $ry_)
              + (int) (($c01[2]) * $rx_ * $ry)
              + (int) (($c10[2]) * $rx * $ry_)
              + (int) (($c11[2]) * $rx * $ry);
       return array($cXY_R, $cXY_G, $cXY_B);
   }

   #-- imagegetcolor from current ->$img split up into RGB array
   function get_RGB(&$img, $x, $y) {
      $rgb = @imagecolorat($img, $x, $y);
      return array(($rgb >> 16) &0xFF, ($rgb >>8) &0xFF, ($rgb) &0xFF);
   }
}






#-- xy-wave deviation (works best for around 200x60)
#   cos(x,y)-idea taken from imagemagick
class easy_captcha_dxy_wave {

   #-- init params
   function easy_captcha_dxy_wave($max_x, $max_y) {
      $this->dist_x = $this->real_rand(2.5, 3.5);     // max +-x/y delta distance
      $this->dist_y = $this->real_rand(2.5, 3.5);
      $this->slow_x = $this->real_rand(7.5, 20.0);    // =wave-width in pixel/3
      $this->slow_y = $this->real_rand(7.5, 15.0);
   }
   
   #-- calculate source pixel position with overlapping sinus x/y-displacement
   function dxy($x, $y) {
      #-- adapting params
      $this->dist_x *= 1.000035;
      $this->dist_y *= 1.000015;
      #-- dest pixels (with x+y together in each of the sin() calcs you get more deformation, else just yields y-ripple effect)
      $dx = $this->dist_x * cos(($x/$this->slow_x) - ($y/1.1/$this->slow_y));
      $dy = $this->dist_y * sin(($y/$this->slow_y) - ($x/0.9/$this->slow_x));
      #-- result
      return array($dx, $dy);
   }
   
   #-- array of values with random start/end values
   function from_to_rand($max, $a, $b) {
      $BEG = $this->real_rand($a, $b);
      $DIFF = $this->real_rand($a, $b) - $BEG;
      $r = array();
      for ($i = 0; $i <= $max; $i++) {
         $r[$i] = $BEG + $DIFF * $i / $max;
      }
      return($r);
   }

   #-- returns random value in given interval
   function real_rand($a, $b) {
      $r = rand(0, 1<<30);
      return(  $r / (1<<30) * ($b-$a) + $a  );   // base + diff * (0..1)
   }
}


#-- with spike
class easy_captcha_dxy_spike {
   function dxy($x,$y) {
      #-- centre spike
      $y += 0.0;
      return array($x,$y);
   }
}








#-- colorful captcha image I
class easy_captcha_graphic_image_disturbed extends easy_captcha_graphic {


   /* returns jpeg file stream with unscannable letters encoded 
      in front of colorful disturbing background
   */
   function jpeg() {
      #-- step by step
      $this->create();
      $this->background_lines();
      $this->background_letters();
      $this->text();
      return $this->output();
   }


   #-- initialize in-memory image with gd library
   function create() {
      $this->img = imagecreatetruecolor($this->width, $this->height);
      imagefilledrectangle($this->img, 0,0, $this->width,$this->height, $this->random_color(222, 255));
      
      #-- encolour bg
      $wd = 20;
      $x = 0;
      while ($x < $this->width) {
         imagefilledrectangle($this->img, $x, 0, $x+=$wd, $this->height, $this->random_color(222, 255));
         $wd += max(10, rand(0, 20) - 10);
      }
   }
   

   #-- make interesting background I, lines
   function background_lines() {
      $c1 = rand(150, 185);
      $c2 = rand(195, 230);
      $wd = 4;
      $w1 = 0;
      $w2 = 0;
      for ($x=0; $x<$this->width; $x+=(int)$wd) {
         if ($x < $this->width) {   // verical
            imageline($this->img, $x+$w1, 0, $x+$w2, $this->height-1, $this->random_color($c1++,$c2));
         }
         if ($x < $this->height) {  // horizontally ("y")
            imageline($this->img, 0, $x-$w2, $this->width-1, $x-$w1, $this->random_color($c1,$c2--));
         }
         $wd += rand(0,8) - 4;
         if ($wd < 1) { $wd = 2; }
         $w1 += rand(0,8) - 4;
         $w2 += rand(0,8) - 4;
         if (($x > $this->height) && ($y > $this->height)) {
            break;
         }
      }
   }
   
      
   #-- more disturbing II, random letters
   function background_letters() {
      $limit = rand(30,90);
      for ($n=0; $n<$limit; $n++) {
         $letter = "";
         do {
            $letter .= chr(rand(31,125)); // random symbol
         } while (rand(0,1));
         $size = rand(5, $this->height/2);
         $half = (int) ($size / 2);
         $x = rand(-$half, $this->width+$half);
         $y = rand(+$half, $this->height);
         $rotation = rand(60, 300);
         imagettftext($this->img, $size, $rotation, $x, $y, $this->random_color(130, 240), $this->font(), $letter);
      }
   }
   

   #-- add the real text to it
   function text() {
      $phrase = $this->solution;
      $len = strlen($phrase);
      $w1 = 10;
      $w2 = $this->width / ($len+1);
      for ($p=0; $p<$len; $p++) {
         $letter = $phrase[$p];
         $size = rand(18, $this->height/2.2);
         $half = (int) $size / 2;
         $rotation = rand(-33, 33);
         $y = rand($size+3, $this->height-3);
         $x = $w1 + $w2*$p;
         $w1 += rand(-$this->width/90, $this->width/40);  // @BUG: last char could be +30 pixel outside of image
         $font = $this->font();
         list($r,$g,$b) = array(rand(30,99), rand(30,99), rand(30,99));
         imagettftext($this->img, $size, $rotation, $x+1, $y, $this->rgb($r*2,$g*2,$b*2), $font, $letter);
         imagettftext($this->img, $size, $rotation, $x, $y-1, $this->rgb($r,$g,$b), $font, $letter);
      }
   }

}









#-- arithmetic riddle
class easy_captcha_text_math_formula extends easy_captcha {

   var $question = "1+1";
   var $solution = "2";

   #-- set up
   function easy_captcha_text_math_formula() {
      $this->question = "What is " . $this->create_formula() . " = ";
      $this->solution = $this->calculate_formula($this->question);
      // we could do easier with iterated formula+result generation here, of course
      // but I had this code handy already ;) and it's easier to modify
   }

   #-- simple IS-EQUAL check
   function solved($result) {
      return (int)$this->solution == (int)$result;
   }

   #-- make new captcha formula string
   function create_formula() {
      $formula = array(
         rand(20,100) . " / " . rand(2,10),
         rand(50,150) . " - " . rand(2,100),
         rand( 2,100) . " + " . rand(2,100),
         rand( 2, 15) . " * " . rand(2,12),
         rand( 5, 10) . " * " . rand(5,10) . " - " . rand(1,20),
         rand(30,100) . " + " . rand(5,99) . " - " . rand(1,50),
   //    rand(20,100) . " / " . rand(2,10) . " + " . rand(1,50),
      );
      return $formula[rand(0,count($formula)-1)];
   }

   #-- remove non-arithmetic characters
   function clean($s) {
      return preg_replace("/[^-+*\/\d]/", "", $s);
   }

   #-- "solve" simple calculations
   function calculate_formula($formula) {
      preg_match("#^(\d+)([-+/*])(\d+)([-+/*])?(\d+)?$#", $this->clean($formula), $uu);
      @list($uu, $X, $op1, $Y, $op2, $Z) = $uu;
      if ($Y) $calc = array(
        "/" => $X / $Y,  // PHP+ZendVM catches division by zero already, and CAPTCHA "attacker" would get no advantage herefrom anyhow
        "*" => $X * $Y,
        "+" => $X + $Y,
        "-" => $X - $Y,
        "*-" => $X * $Y - $Z,
        "+-" => $X + $Y - $Z,
        "/+" => $X / $Y + $Z,
      );
      return(  $calc[$op1.$op2] ? $calc[$op1.$op2] : rand(0,1<<23)  );
   }

}

#-- to disable textual captcha part
class easy_captcha_text_disable extends easy_captcha {
   var $question = "";
   function solved($in) {
      return false;
   }
}








#-- shortcut, allow access for an user if captcha was previously solved
#   (should be identical in each instantiation, cookie is time-bombed)
class easy_captcha_persistent_grant extends easy_captcha {

   function easy_captcha_persistent_grant() {
   }
   

   #-- give ok, if captach had already been solved recently
   function solved($ignore=0) {
      if (CAPTCHA_PERSISTENT && isset($_COOKIE[$this->cookie()])) {
         return in_array($_COOKIE[$this->cookie()], array($this->validity_token(), $this->validity_token(-1)));
      }
   }
   
   #-- set captcha persistence cookie
   function grant() {
      if (!headers_sent()) {
         setcookie($this->cookie(), $this->validity_token(), time() + 175*CAPTCHA_TIMEOUT);
      }
      else {
         // $this->log("::grant", "COOKIES", "too late for cookies");
      }
   }

   #-- pseudo password (time-bombed)
   function validity_token($deviation=0) {
      return easy_captcha::hash("PERSISTENCE", $deviation, $length=100);
   }
   function cookie() {
      return "captcha_pass";
   }
}










#-- simply check if no URLs were submitted - that's what most spambots do,
#   and simply grant access then
class easy_captcha_spamfree_no_new_urls {

   #-- you have to adapt this, to check for newly added URLs only, in Wikis e.g.
   #   - for simple comment submission forms, this default however suffices:
   function solved($ignore=0) {
      $r = !preg_match("#(https?://\w+[^/,.]+)#ims", serialize($_GET+$_POST), $uu);
      return $r;
   }
}










#-- "AJAX" and utility code
class easy_captcha_utility {




   #-- script was called directly
   /*static*/ function API() {

      #-- load data
      if ($id = @$_GET[CAPTCHA_PARAM_ID]) {

         #-- special case
         if ($id == 'base.js') {
            easy_captcha_utility::js_base();
         }

         else {
            $c = new easy_captcha($id=NULL, $ignore_expiration=1);
            $expired = !$c->is_valid();

            #-- JS-RPC request, check entered solution on the fly
            if ($test = @$_REQUEST[CAPTCHA_PARAM_INPUT]) {
  
               #-- check
               if ($expired) {
               }
               if (0 >= $c->ajax_tries--) {
                  $c->log("::API", "JS-RPC", "ajax_tries exhausted ($c->ajax_tries)");
               }
               $ok = $c->image->solved($test) || $c->text->solved($test);

               #-- sendresult
               easy_captcha_utility::js_rpc($ok);
            }

            #-- generate and send image file
            else { 
               if ($expired) {
                  $type = "image/png";
                  $bin = easy_captcha_utility::expired_png();
               }
               else {
                  $type = "image/jpeg";
                  $bin = $c->image->jpeg();
               }
               header("Pragma: no-cache");
               header("Cache-Control: no-cache, no-store, must-revalidate, private");
               header("Expires: " . gmdate("r", time()));
               header("Content-Length: " . strlen($bin));
               header("Content-Type: $type");
               print $bin;
            }
         }
         exit;
      }
   }

   #-- hardwired error img
   function expired_png() {
      return base64_decode("iVBORw0KGgoAAAANSUhEUgAAADwAAAAUAgMAAACsbba6AAAADFBMVEUeEhFcMjGgWFf9jIrTTikpAAAACXBIWXMAAAsTAAALEwEAmpwYAAAA3UlEQVQY01XPzwoBcRAH8F9RjpSTm9xR9qQwtnX/latX0DrsA3gC8QDK0QO4bv7UOtmM+x4oZ4X5FQc1hlb41dR8mm/9ZhT/P7X/dDcpZPU3FYft9kWbLuWp4Bgt9v1oGG07Ja8ojfjxQFym02DVmoixkV/m2JI/TUtefR7nD9rkrhkC+6D77/8mUhDvw0ymLPwxf8esghEFRq8hqKcu2iG16Vlun1zYTO7RwCeFyoJqAgC3LQwzYiCokDj0MWRxb+Z6R8mPJb8Q77zlPbuCoJE8a/t7P773uv36tdcTmsXfRycoRJ8AAAAASUVORK5CYII=");
   }





   #-- send base javascript
   function js_base() {
      $captcha_new_urls = $_GET["captcha_new_urls"] ? 0 : 1;
      $base_url = CAPTCHA_BASE_URL;
      $param_id = "?" . CAPTCHA_PARAM_ID . "=";
      $param_input = "&" . CAPTCHA_PARAM_INPUT . "=";
      header("Content-Type: text/javascript");
      print<<<END_____BASE__BASE__BASE__BASE__BASE__BASE__BASE__BASE_____END


/* easy_captcha utility code */

// global vars
captcha_url_rx = /(https?:\/\/\w[^\/,\]\[=#]+)/ig;  //   
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
   var cid = document.getElementById("__ec_i");
   var inf = document.getElementById("__ec_s");
   var len = inf.value.length;
   // visualize processissing
   if (len >= 4) {
      inf.style.border = "2px solid #FF9955";
   }
   // if enough letters entered
   if (len >= 5) {
      // remove old <script> node
      var scr;
      if (src = document.getElementById("captcha_ajax_1")) {
         src.parentNode.removeChild(src);
      }
      // create new <script> node, initiate JS-RPC call thereby
      scr = document.createElement("script");
      scr.setAttribute("language", "JavaScript");
      scr.setAttribute("type", "text/javascript");
      scr.setAttribute("src", "$base_url" + "$param_id" + cid.value + "$param_input" + inf.value);
      scr.setAttribute("id", "captcha_ajax_1");
      document.getElementById("captcha").appendChild(scr);
      captcha_rpc = 1;
   }
   // visual feedback for editing
   var col = 10 + len*3;
   inf.style.background = "#"+col+col+col;
}


END_____BASE__BASE__BASE__BASE__BASE__BASE__BASE__BASE_____END;
   }







   #-- response javascript
   function js_rpc($yes) {
      $yes = $yes ? 1 : 0;
      header("Content-Type: text/javascript");
      print<<<END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END


// JS-RPC response
if (1) {
   captcha_rpc = 0;
   var inf = document.getElementById("__ec_s");
   inf.style.borderColor = $yes ? "#22AA22" : "#AA2222";
}


END_____JSRPC__JSRPC__JSRPC__JSRPC__JSRPC__JSRPC_____END;
   }



}







?>
