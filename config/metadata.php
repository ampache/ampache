<?php
return [

/*
; This determines the tag order for all cataloged
; music. If none of the listed tags are found then
; ampache will randomly use whatever was found.
; POSSIBLE VALUES: ape asf avi id3v1 id3v2 lyrics3 matroska mpeg quicktime riff
;     vorbiscomment
; DEFAULT: id3v2 id3v1 vorbiscomment quicktime matroska ape asf avi mpeg riff
*/
  'getid3_tag_order' => "id3v2,id3v1,vorbiscomment,quicktime,matroska,ape,asf,avi,mpeg,riff",
/*
; This determines whether we try to autodetect the encoding for id3v2 tags.
; May break valid tags.
; DEFAULT: false
*/
  'getid3_detect_id3v2_encoding' => false,

/*
; This determines if we write the changes to files (as id3 tags) when modifying metadata, or only keep them in Ampache (the default).
; DEFAULT: false
*/
 'write_id3' => false,
/*
; This determines if we write the changes to files (as id3 tags) when modifying album art, or only keep them in Ampache (the default)
; as id3 metadata when updated.
; DEFAULT: false
*/
  'write_id3_art' => false,

/*
; This determines the order in which metadata sources are used (and in the
; case of plugins, checked)
; POSSIBLE VALUES (builtins): filename and getID3
; POSSIBLE VALUES (plugins): MusicBrainz,TheAudioDb, plus any others you've installed.
; DEFAULT: getID3 filename
*/
  'metadata_order' => ['filename'],

/*
; This determines the order in which metadata sources are used (and in the
; case of plugins, checked) for video files
; POSSIBLE VALUES (builtins): filename and getID3
; POSSIBLE VALUES (plugins): Tvdb,Tmdb,Omdb, plus any others you've installed.
; DEFAULT: filename getID3
*/
  'metadata_order_video' => "filename,getID3",

/*
; This determines if extended metadata grabbed from external services should be deferred.
; If enabled, extended metadata is retrieved when browsing the library item.
; If disabled, extended metadata is retrieved at catalog update.
; Today, only Artist information (summary, place formed, ...) can be deferred.
; DEFAULT: true
*/
  'deferred_ext_metadata' => true,

/*
; Some taggers use delimiters other than \0 for fields
; This list specifies possible delimiters additional to \0
; This setting takes a regex pattern.
; DEFAULT: // / \ | , ;
*/
  'additional_genre_delimiters' => "[/]{2}|[/|\\\\|\|,|;]",

/*
; Enable importing custom metadata from files.
; This will need a bit of time during the import. So you may want to disable this
; if you have troubles with huge databases.
; DEFAULT: false
*/
  'enable_custom_metadata' => false,
];
