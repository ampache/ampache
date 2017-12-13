<?php

return [
    
    'site_charset' => 'utf-8',

/* ; Locale Charset
 * Local charset (mainly for file operations) if different
 * from site_charset.
 * This is disabled by default, enable only if needed
 * (for Windows please set lc_charset to ISO8859-1)
 * DEFAULT: ISO8859-1
*/
 'lc_charset' => "ISO8859-1",

    
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

/*
 *  Allow guest use. Guests will have very limited use
 *  
 */
    'allow_guests' => false,
    
/*
 * Un comment if don't want ampache to follow symlinks
 * DEFAULT: false
 */
  'no_symlinks'	=> false,
 
/* Caching
  ; This turns the caching mechanisms on or off, due to a large number of
  ; problems with people with very large catalogs and low memory settings
  ; this is off by default as it does significantly increase the memory
  ; requirments on larger catalogs. If you have the memory this can create
  ; a 2-3x speed improvement.
  ; DEFAULT: false
 */
   'memory_cache' => false,
      
  /* Memory Limit
  ; This defines the "Min" memory limit for PHP if your php.ini
  ; has a lower value set Ampache will set it up to this. If you
  ; set it below 16MB getid3() will not work!
  ; DEFAULT: 32
  */
    'memory_limit' => 32
  
    
];
