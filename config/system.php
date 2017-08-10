<?php

return [
    
    'site_charset' => 'utf-8',

    'max_upload_size' => '2048000',
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
    'album_art_max_width' => 1024,
        
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
    'album_art_max_height' => 1024,
        

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
    
/* Statistical Graphs * Requires PHP-GD *
*  Set this to true if you want Ampache to generate statistical
*  graphs on usages / users.
*  DEFAULT: false
*/
    'statistical_graphs' => false,
    
];
