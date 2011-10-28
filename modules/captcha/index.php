<?php

  // load library and preset a few options
  define("CAPTCHA_INVERSE", 1);    // black background
  define("CAPTCHA_NEW_URLS", 0);   // no auto-disabling/hiding for the demo
  include("captcha.php");

?>
<html>
<head>
  <title>captcha.php 2.2</title>
  <style type="text/css"><!--
html, body {
  padding: 0;
  margin: 0;
  background: #2C2C2C;
}
* {
  color: #999;
  font-family: Verdana, "Trebuchet MS", "Teen", "Verdana", "Impact","Arial", sans-serif;
  font-size: 12pt;
  text-weight: thin;
}
.container {
  background: no-repeat url("bg.jpeg");
  width: 600px;
  height: 1000px;
  padding: 200px 150px 100px 150px;
}
p {
  width: 430px;
  line-height: 20pt;
}
.download {
  margin-top: 120pt;
  width: 140px;
  float: right;
}
.download a {
  font-size: 18pt;
  text-decoration: none;
  color: #DDCC11;
}
.download a:hover {
  color: #11BB11;
}
.download a:hover img {
  width: 40px;
  height: 30px;
  padding: 5px;
}

form {
}
input, textarea {
  background: #222;
  border: 1px solid #333;
  color: #999;
}
.captcha {
  margin: 5pt;
}

  //--></style>
</head>
<body>
<div class="container">


 <?php
   // simple post
   if ($_POST || $_GET) {
      if (captcha::solved()) {
         echo "<p>Captcha solved</p>";
      }
      else {
         echo "<p>Incorrect solution</p>";
      }
      die("</div></body></html>"); // yes,yes,yes; but this is just for testing here
   }
 
 ?>

 <div class="download">
  <a href="captcha-2.2.tgz">
  download (52K)
  <img src="download.png" width="50" height="40" alt="&darr;" align="top" border="0" />
  </a>
 </div>

 <p>
 Unlike similar implementations, this PHP CAPTCHA class works without
 cookies. It is extremely easy to integrate into existing sites and
 your &lt;form&gt; processing logic. And it requires no database.
 <p>
 
 <p>
 It strives to be more user-friendly. It gives visual feedback while
 you enter a solution (AJAX). And it accepts a few misguessed letters.
 Additionally it has an accessible riddle built in, optional modules
 and many configuration settings.
 </p>
 
 <p>
 Following is a pseudo blog comment field - only here for testing:
 </p>
 




<form action="index.php" method="GET" x-enctype="multipart/form-data" accept-encoding="UTF-8">
  <textarea name="texta1" cols="40" rows="5">...</textarea>
  <?php
     // output CAPTCHA img + input box
     echo captcha::form("&rarr;&nbsp;");
  ?>
  <input type="submit" name="submit" value="Submit">
</form>


 <p style="margin-top: 30pt;">
 Distributed here as Public Domain, which makes it compatible and convertible
 to <em>all</em> open  source licenses. 
 </p>


 <div>
  <a href="http://www.freshmeat.net/p/captchaphp">get updates (freshmeat.net)</a>
  |
  <a href="captcha.tgz">download</a>
  |
  <a href="http://upgradephp.berlios.de/">upgrade.php</a>
 </div>

</div>
</body>
</html>
