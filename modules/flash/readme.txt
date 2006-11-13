XSPF Player - Extended Version

This version is completely similar, like functionality, to the previous version
but it adds the possibility to visualize the photo of album (a JPG 80x80 pixel) 
beyond that the eventual one link to the page of information.

XSPF Player does not have need of no language scripting server side.

XSPF Player is not open source (the .fla is not disclosed), can be unloaded and used free of charge in the Creative respect of the license Commons
and in the respect of the Conditions of I use indicated to the end of this page. See license.txt for more information.

Parameters to pass to the SWF

variable 	value
folder		Absolute distance that it indicates where finds the rows FMP3.swf 
		(http://www.enricolai.com/FMP3/) as an example. 
		The rows that come tried must be called .fmp3.swf. therefore not rinominate the rows.
playlist 	Absolute URL of the playlist in format XSPF to read. 
		As an example http://www.enricolai.com/FMP3/kahvi.xml 
		(rows XML must reside in the same domain in which the application is found).
		In contrary case you must use one script server side that functions from Gateway.
		The URL can also make reference to a dynamic script that gives back playlist the XSPF to the flight.
color		Hexdecimal color (as an example FFCC00) in order to change the color of the background of the player.
loop		possible values: yes | not | playlist
		If it comes set up yes the rows mp3 comes reproduced from the every beginning which time arrives to the term. 
		If it comes no instead set up contrary of yes. 
		If it comes set up on playlist the player it passes to the successive trace once finished a trace 
		and continues therefore on the entire list of rows mp3.
lma	 	(loop mode active) possible values: yes | no
		it allows to activate or less the key of the Loop mode that it allows to the customer of set or less the Loop option.
textcolor	Hexdecimal color (as an example FFCC00) in order to change the color of the text and the icon Audio.
action		possible values: play | stop
		The player sets up so that the trace mp3 leaves endured or not. 
		In modality stop the customer must click to play in order to start the trace.
vol		(volume begins them) is possible to set up a value for the volume from 0 to 100. 
		If the value is not set up the player it reproduces the rows mp3 to maximum volume (100)
display 	Example 1@. - @0@ - @. It tightens that it allows to personalize the visualization of the list. 
		Default the player it visualizes the values of the tag annotation present in playlist the XSPF. 
		The first value before the @ indicates if to visualize or less the progressive numeration for every trace (1, 2, 3 etc etc). 
		According to value it indicates tightens to use in order to separate it the numerical value of the trace from the text.
		The third value is a numerical value that allows to choose the tag of the XML to visualize using as separatory it tightens 
		it specified in the fourth value. The possible values for the third parameter are:
		0 - it visualizes the values of the tag title
		1 - |separatore 4° valore| + visualizes the values of tag creator + title
		2 - |separatore 4° valore| + visualizes the values of tag title + creator
		3 - |separatore 4° valore| + visualizes the values of tag album + title
		4 - |separatore 4° valore| + visualizes the values of tag title + album
viewinfo 	If set on true it visualizes push-button INFO on the photo of the album and allows to activate
		the link to the page specified in the tag info of rows XSPF.
