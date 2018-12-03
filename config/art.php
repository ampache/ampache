<?php

return [
 /*
 ; Album Art Store on Disk
 ; This defines if arts should be stored on disk instead of database.
 ; DEFAULT: false
 */
    
 'album_art_store_disk' => false,
    
 'max_avatar_size' => '100000',
    
/*
 * Album Art Minimum Width
 * 		Specify the minimum width for arts (in pixel).
 *	 	DEFAULT: none
 */
    'album_art_min_width' => 100,
/*
 *		; Album Art Maximum Width
 *		; Specify the maximum width for arts (in pixel).
 *		; DEFAULT: none
*/
    'album_art_max_width' => 259,
        
/*
 *		; Album Art Minimum Height
 *		; Specify the minimum height for arts (in pixel).
 *		; DEFAULT: none
 */
    'album_art_min_height' => 100,

/*
 *		; Album Art Maximum Height
 *		; Specify the maximum height for arts (in pixel).
 *		; DEFAULT: none
 */
    'album_art_max_height' => 250,
        

    'avatar_max_size' => 2048000,

/*
 *  Avatar Minimum Width
 *  Specify the minimum width for arts (in pixel).
 *  DEFAULT: none
 */
    'avatar_min_width' => 100,

/*
 *  Avatar Maximum Width
 *	Specify the maximum width for arts (in pixel).
 *	DEFAULT: none
 */
    'avatar_max_width' => 500,
        
/*
 *  Avatar Minimum Height
 *	Specify the minimum height for arts (in pixel).
 *	DEFAULT: none
 */
    'avatar_min_height' => 100,
        
/*
 *	Avatar Maximum Height
 *	Specify the maximum height for arts (in pixel).
 *	DEFAULT: none
 */
    'avatar_max_height' => 500,
    
 /* ; Art Gather Order
 * Simply arrange the following in the order you would like
 * ampache to search. If you want to disable one of the search
 * methods simply leave it out. DB should be left as the first
 * method unless you want it to overwrite what's already in the
 * database
 * POSSIBLE VALUES (builtins): db tags folder lastfm musicbrainz google
 * POSSIBLE VALUES (plugins): Amazon,TheAudioDb,Tmdb,Omdb,Flickr
 * DEFAULT: db,tags,folder,musicbrainz,lastfm,google
 */
 'art_order' => "db,tags,folder",
    
];
