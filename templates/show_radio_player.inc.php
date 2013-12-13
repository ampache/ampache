</head>
<body bgcolor="#343434">
<?php
if ($iframed) {
?>
  <div class="jp-close">
    <a href="javascript:ExitPlayer();" title="Close Player"><img src="images/close.png" border="0" /></a>
  </div>
<?php
}
?>
<?php
    $swffile = AmpConfig::get('web_path') . "/modules/muses/muses.swf";
    $flashvars = "url=" . $radio->url . "&lang=auto&codec=" . $radio->codec . "&volume=80&introurl=&tracking=true&jsevents=true&skin=" .
        AmpConfig::get('web_path')."/modules/muses/skins/ffmp3-faredirfare.xml&title=" . urlencode($radio->title) . "&welcome=Ampache&autoplay=true";
?>
<div style="text-align: center;">
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="269" height="50" bgcolor="#343434">
<param name="movie" value="<?php echo $swffile; ?>" />
<param name="flashvars" value="<?php echo $flashvars; ?>" />
<param name="wmode" value="window" />
<param name="allowscriptaccess" value="always" />
<param name="bgcolor" value="#FFFFFF" />
<param name="scale" value="noscale" />
<embed src="<?php echo $swffile; ?>" flashvars="<?php echo $flashvars; ?>" width="269" scale="noscale" height="50" wmode="window" bgcolor="#343434" allowscriptaccess="always" type="application/x-shockwave-flash" />
</object>
</div>
</body>
</html>
