<?php
return array(
/*
 * File Pattern
 * This defines which file types Ampache will attempt to catalog
 * You can specify any file extension you want in here separating them
 * with a |
 * DEFAULT: mp3|mpc|m4p|m4a|aac|ogg|oga|wav|aif|aiff|rm|wma|asf|flac|opus|spx|ra|ape|shn|wv
 */
'catalog_file_pattern' => "mp3|mpc|m4p|m4a|aac|ogg|oga|wav|aif|aiff|rm|wma|asf|flac|opus|spx|ra|ape|shn|wv",

/*
 * Video Pattern
 * This defines which video file types Ampache will attempt to catalog
 * You can specify any file extension you want in here separating them with
 * a | but ampache may not be able to parse them
 * DEAFULT: avi|mpg|mpeg|flv|m4v|mp4|webm|mkv|wmv|ogv|mov|divx|m2ts
 */
 'catalog_video_pattern' => "avi|mpg|mpeg|flv|m4v|mp4|webm|mkv|wmv|ogv|mov|divx|m2ts",

/* Playlist Pattern
 * This defines which playlist types Ampache will attempt to catalog
 * You can specify any file extension you want in here separating them with
 * a | but ampache may not be able to parse them
 * DEFAULT: m3u|m3u8|pls|asx|xspf
 */
  'catalog_playlist_pattern' => "m3u|m3u8|pls|asx|xspf",

/* Prefix Pattern
 * This defines which prefix Ampache will ignore when importing tags from
 *  your music. You may add any prefix you want separating them with a |
 * DEFAULT: The|An|A|Die|Das|Ein|Eine|Les|Le|La
 */
 'catalog_prefix_pattern' => "The|An|A|Die|Das|Ein|Eine|Les|Le|La",

/* Catalog disable
 * This defines if catalog can be disabled without removing database entries
 * WARNING: this increase sensibly sql requests and slow down Ampache a lot
 *  DEFAULT: false
 */
 'catalog_disable' => true,


/* Delete from disk
 * This determines if catalog manager users can delete medias from disk.
 * DEFAULT: false
 */
 'delete_from_disk' => false,

/* Allow embedded catalog paths
 * This Will allow catalogs to be created with multiple sub-paths
 * DEFAULT: False
 */
 'allow_embedded_catalogs' => true,



);
