<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //01
        DB::table('preferences')->insert(['name' => 'download', 'value' => '0', 'description' => 'Allow Downloads',
            'level' => 75, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
        //002
        DB::table('preferences')->insert(['name' => 'popular_threshold', 'value' => '10', 'description' => 'Popular Threshold',
            'level' => 75, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'query',
        ]);
        //003
        DB::table('preferences')->insert(['name' => 'transcode_bitrate', 'value' => '64', 'description' => 'Transcode Bitrate',
            'level' => 25, 'type' => 'string', 'category' => 'streaming', 'subcategory' => 'transcoding',
        ]);
        //04
        DB::table('preferences')->insert(['name' => 'site_title', 'value' => "Ampache :: For the love of Music",
            'description' => 'Website Title', 'level' => 100, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //05
        DB::table('preferences')->insert(['name' => 'lock_songs', 'value' => '0', 'description' => 'Lock Songs',
             'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => '',
        ]);

        //06
        DB::table('preferences')->insert(['name' => 'force_http_play', 'value' => '0', 'description' => 'Forces Http play regardless of port',
             'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => '',
        ]);

        //07
        DB::table('preferences')->insert(['name' => 'play_type', 'value' => '0', 'description' => 'Forces Http play regardless of port',
             'level' => 25, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => '',
        ]);

        //08
        DB::table('preferences')->insert(['name' => 'lang', 'value' => "en_EN", 'description' => 'Language',
             'level' => 100, 'type' => 'special', 'category' => 'interface', 'subcategory' => '',
        ]);
 
        //09
        DB::table('preferences')->insert(['name' => 'playlist_type', 'value' => "m3u", 'description' => 'Playlist Type',
            'level' => 100, 'type' => 'special', 'category' => 'playlist', 'subcategory' => '',
        ]);
        
        
        //10
        DB::table('preferences')->insert(['name' => 'theme_name', 'value' => "Reborn", 'description' => 'Theme Name',
            'level' => 0, 'type' => 'special', 'category' => 'interface', 'subcategory' => 'theme',
        ]);
        
        //11
        DB::table('preferences')->insert(['name' => 'localplay_level', 'value' => '0', 'description' => 'Local Play Access',
            'level' => 100, 'type' => 'special', 'category' => 'options', 'subcategory' => 'localplay',
        ]);
  
        //12
        DB::table('preferences')->insert(['name' => 'localplay_controller', 'value' => '0', 'description' => 'Local Play Type',
            'level' => 100, 'type' => 'special', 'category' => 'options', 'subcategory' => 'localplay',
        ]);
  
        //13
        DB::table('preferences')->insert(['name' => 'allow_stream_playback', 'value' => '1', 'description' => 'Allow Streaming',
            'level' => 100, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
 
        //14
        DB::table('preferences')->insert(['name' => 'allow_democratic_playback', 'value' => '0', 'description' => 'Allow Democratic Play',
            'level' => 100, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
 
        //15
        DB::table('preferences')->insert(['name' => 'allow_localplay_playback', 'value' => '0', 'description' => 'Allow Localplay Play',
            'level' => 100, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'localplay',
        ]);
 
        //16
        DB::table('preferences')->insert(['name' => 'stats_threshold', 'value' => '7', 'description' => 'Statistics Day Threshold',
            'level' => 25, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'query',
        ]);

        //17
        DB::table('preferences')->insert(['name' => 'offset_limit', 'value' => '50', 'description' => 'Offset Limit',
            'level' => 5, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'query',
        ]);

        //18
        DB::table('preferences')->insert(['name' => 'rate_limit', 'value' => '8192', 'description' => 'Rate Limit',
            'level' => 100, 'type' => 'integer', 'category' => 'streaming', 'subcategory' => 'transcoding',
        ]);

        //19
        DB::table('preferences')->insert(['name' => 'playlist_method', 'value' => "default", 'description' => 'Playlist Method',
            'level' => 5, 'type' => 'string', 'category' => 'playlist', 'subcategory' => '',
        ]);

        //20
        DB::table('preferences')->insert(['name' => 'transcode', 'value' => "default", 'description' => 'Transcoding',
            'level' => 25, 'type' => 'string', 'category' => 'streaming', 'subcategory' => 'transcoding',
        ]);

        //21
        DB::table('preferences')->insert(['name' => 'show_lyrics', 'value' => '0', 'description' => 'Show Lyrics',
            'level' => 0, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'player',
        ]);

        //22
        DB::table('preferences')->insert(['name' => 'mpd_active', 'value' => '0', 'description' => 'MPD Active Instance',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'mpd',
        ]);

        //23
        DB::table('preferences')->insert(['name' => 'httpq_active', 'value' => '0', 'description' => 'HTTPQ Active Instance',
            'level' => 25, 'type' => 'integer', 'category' => 'internal', 'subcategory' => 'httpq',
        ]);

        //24
        DB::table('preferences')->insert(['name' => 'shoutcast_active', 'value' => '0', 'description' => 'Shoutcast Active Instance',
            'level' => 25, 'type' => 'integer', 'category' => 'internal', 'subcategory' => 'shoutcast',
        ]);

        //25
        DB::table('preferences')->insert(['name' => 'now_playing_per_user', 'value' => '1', 'description' => 'Now playing filtered per user',
            'level' => 50, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'home',
        ]);

        //26
        DB::table('preferences')->insert(['name' => 'album_sort', 'value' => '0', 'description' => 'Album Default Sort',
            'level' => 25, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'library',
        ]);

        //27
        DB::table('preferences')->insert(['name' => 'show_played_times', 'value' => '0', 'description' => 'Show # played',
            'level' => 25, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'library',
        ]);
        
        //28
        DB::table('preferences')->insert(['name' => 'song_page_title', 'value' => '1', 'description' => 'Show current song in Web player page title',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'player',
        ]);
        
        //29
        DB::table('preferences')->insert(['name' => 'subsonic_backend', 'value' => '1', 'description' => 'Use SubSonic backend',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'backend',
        ]);
        
        //30
        DB::table('preferences')->insert(['name' => 'plex_backend', 'value' => '0', 'description' => 'Use Plex backend',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'backend',
        ]);
        
        //31
        DB::table('preferences')->insert(['name' => 'webplayer_flash', 'value' => '1', 'description' => 'Authorize Flash Web Player(s)',
            'level' => 25, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => 'player',
        ]);
        
        //32
        DB::table('preferences')->insert(['name' => 'webplayer_html5', 'value' => '1', 'description' => 'Authorize HTML5 Web Player(s)',
            'level' => 25, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => 'player',
        ]);
        
        //33
        DB::table('preferences')->insert(['name' => 'allow_personal_info_now', 'value' => '1', 'description' => 'Personal information visibility - Now playing',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'privacy',
        ]);
        
        //34
        DB::table('preferences')->insert(['name' => 'allow_personal_info_recent', 'value' => '1', 'description' => 'Personal information visibility - Recently played',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'privacy',
        ]);
        
        //35
        DB::table('preferences')->insert(['name' => 'allow_personal_info_time', 'value' => '1', 'description' => 'Personal information visibility - Recently played - Allow to show streaming date/time',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'privacy',
        ]);
        
        //36
        DB::table('preferences')->insert(['name' => 'allow_personal_info_agent', 'value' => '1', 'description' => 'Personal information visibility - Recently played - Allow to show streaming agent',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'privacy',
        ]);
        
        //36
        DB::table('preferences')->insert(['name' => 'allow_personal_info_agent', 'value' => '1', 'description' => 'Personal information visibility - Recently played - Allow to show streaming agent',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'privacy',
        ]);
        
        //37
        DB::table('preferences')->insert(['name' => 'ui_fixed', 'value' => '0', 'description' => 'Fix header position on compatible themes',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'theme',
        ]);
         
        //38
        DB::table('preferences')->insert(['name' => 'autoupdate', 'value' => '1', 'description' => 'Check for Ampache updates automatically',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'update',
        ]);
        
        //39
        DB::table('preferences')->insert(['name' => 'autoupdate_lastcheck', 'value' => '', 'description' => 'Check for Ampache updates automatically',
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'update',
        ]);
        
        //40
        DB::table('preferences')->insert(['name' => 'autoupdate_lastversion', 'value' => '', 'description' => 'AutoUpdate last version from last check',
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'update',
        ]);
        
        //41
        DB::table('preferences')->insert(['name' => 'autoupdate_lastversion_new', 'value' => '', 'description' => 'AutoUpdate last version from last check is newer',
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'update',
        ]);
        
        //42
        DB::table('preferences')->insert(['name' => 'webplayer_confirmclose', 'value' => '0', 'description' => 'Confirmation when closing current playing window',
            'level' => 100, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'player',
        ]);
         
        //43
        DB::table('preferences')->insert(['name' => 'webplayer_pausetabs', 'value' => '1', 'description' => 'Auto-pause betweens tabs',
            'level' => 100, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'player',
        ]);
        
        //44
        DB::table('preferences')->insert(['name' => 'stream_beautiful_url', 'value' => '0', 'description' => 'Enable url rewriting',
            'level' => 100, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => '',
        ]);
        
        //45
        DB::table('preferences')->insert(['name' => 'share', 'value' => '1', 'description' => 'Allow Share',
            'level' => 100, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
        
        //46
        DB::table('preferences')->insert(['name' => 'share_expire', 'value' => '7', 'description' => 'Share links default expiration days (0=never)',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'share',
        ]);
         
        //47
        DB::table('preferences')->insert(['name' => 'slideshow_time', 'value' => '0', 'description' => 'Artist slideshow inactivity time',
            'level' => 25, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'player',
        ]);
         
        //48
        DB::table('preferences')->insert(['name' => 'broadcast_by_default', 'value' => '0', 'description' => 'Broadcast web player by default',
            'level' => 25, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => 'player',
        ]);
          
        //49
        DB::table('preferences')->insert(['name' => 'concerts_limit_future', 'value' => '0', 'description' => 'Limit number of future events',
            'level' => 25, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'query',
        ]);
          
        //50
        DB::table('preferences')->insert(['name' => 'concerts_limit_past', 'value' => '0', 'description' => 'Limit number of past events',
            'level' => 25, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'query',
        ]);
          
        //51
        DB::table('preferences')->insert(['name' => 'album_group', 'value' => '0', 'description' => 'Album - Group multiple disks',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'query',
        ]);
          
        //52
        DB::table('preferences')->insert(['name' => 'topmenu', 'value' => '0', 'description' => 'Top menu',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'theme',
        ]);
          
        //53
        DB::table('preferences')->insert(['name' => 'demo_clear_sessions', 'value' => '0', 'description' => 'Clear democratic votes of expired user sessions',
            'level' => 25, 'type' => 'boolean', 'category' => 'playlist', 'subcategory' => '',
        ]);
           
        //54
        DB::table('preferences')->insert(['name' => 'show_donate', 'value' => '1', 'description' => 'Show donate button in footer',
            'level' => 100, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => '',
        ]);
        
        //55
        DB::table('preferences')->insert(['name' => 'upload_catalog', 'value' => '-1', 'description' => 'Uploads catalog destination',
            'level' => 75, 'type' => 'integer', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //56
        DB::table('preferences')->insert(['name' => 'allow_upload', 'value' => '0', 'description' => 'Allow users to upload media',
            'level' => 75, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
         
        //57
        DB::table('preferences')->insert(['name' => 'upload_subdir', 'value' => '0', 'description' => "Create a subdirectory per user (recommended)",
            'level' => 75, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
         
        //58
        DB::table('preferences')->insert(['name' => 'upload_user_artist', 'value' => '0', 'description' => "Consider the user sender as the track's artist",
            'level' => 75, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //59
        DB::table('preferences')->insert(['name' => 'upload_script', 'value' => '', 'description' => 'Run the following script after upload (default directory = upload target directory)',
            'level' => 75, 'type' => 'string', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //60
        DB::table('preferences')->insert(['name' => 'upload_allow_edit', 'value' => '0', 'description' => 'Allow users to edit uploaded songs',
            'level' => 75, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //61
        DB::table('preferences')->insert(['name' => 'daap_backend', 'value' => '0', 'description' => 'Use DAAP backend',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'backend',
        ]);
        
        //62
        DB::table('preferences')->insert(['name' => 'daap_pass', 'value' => '', 'description' => 'DAAP backend password',
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'backend',
        ]);
        
        //63
        DB::table('preferences')->insert(['name' => 'upnp_backend', 'value' => '1', 'description' => 'Use UPnP backend',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'backend',
        ]);
        
        //64
        DB::table('preferences')->insert(['name' => 'allow_video', 'value' => '1', 'description' => 'Allow video features',
            'level' => 100, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
        
        //65
        DB::table('preferences')->insert(['name' => 'album_release_type', 'value' => '1', 'description' => "Album - Group per release type",
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'library',
        ]);
        
        //66
        DB::table('preferences')->insert(['name' => 'ajax_load', 'value' => '1', 'description' => 'Ajax page load',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => '',
        ]);
        
        //67
        DB::table('preferences')->insert(['name' => 'direct_play_limit', 'value' => '1', 'description' => 'Limit direct play to maximum media count',
            'level' => 25, 'type' => 'integer', 'category' => 'interface', 'subcategory' => 'player',
        ]);
        
        //68
        DB::table('preferences')->insert(['name' => 'home_moment_albums', 'value' => '1', 'description' => 'Show Albums of the moment at home page',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'home',
        ]);
        
        //69
        DB::table('preferences')->insert(['name' => 'home_moment_videos', 'value' => '1', 'description' => 'Show Videos of the moment at home page',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'home',
        ]);
        
        //70
        DB::table('preferences')->insert(['name' => 'home_recently_played', 'value' => '1', 'description' => 'Show Recently Played at home page',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'home',
        ]);
        
        //71
        DB::table('preferences')->insert(['name' => 'home_now_playing', 'value' => '1', 'description' => 'Show Now Playing at home page',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'home',
        ]);
        
        //72
        DB::table('preferences')->insert(['name' => 'custom_logo', 'value' => '', 'description' => 'Custom logo url',
            'level' => 25, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //73
        DB::table('preferences')->insert(['name' => 'album_release_type_sort', 'value' => 'album,ep,live,single', 'description' => "Album - Group per release type Sort",
            'level' => 25, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'library',
        ]);
        
        //74
        DB::table('preferences')->insert(['name' => 'browser_notify', 'value' => '0', 'description' => 'WebPlayer browser notifications',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'notification',
        ]);
        
        //75
        DB::table('preferences')->insert(['name' => 'browser_notify_timeout', 'value' => '10', 'description' => "WebPlayer browser notifications timeout (seconds)",
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'notification',
        ]);
        
        //76
        DB::table('preferences')->insert(['name' => 'geolocation', 'value' => '0', 'description' => 'Allow geolocation',
            'level' => 25, 'type' => 'boolean', 'category' => 'options', 'subcategory' => 'feature',
        ]);
        
        //77
        DB::table('preferences')->insert(['name' => 'webplayer_aurora', 'value' => '1', 'description' => "Authorize JavaScript decoder (Aurora.js) in Web Player(s)",
            'level' => 25, 'type' => 'boolean', 'category' => 'streaming', 'subcategory' => 'player',
        ]);
        
        //78
        DB::table('preferences')->insert(['name' => 'upload_allow_remove', 'value' => '1', 'description' => "Upload: allow users to remove uploaded songs",
            'level' => 75, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //79
        DB::table('preferences')->insert(['name' => 'custom_text_footer', 'value' => '', 'description' => 'Custom text footer',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //80
        DB::table('preferences')->insert(['name' => 'custom_favicon', 'value' => '', 'description' => 'Custom favicon url',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //81
        DB::table('preferences')->insert(['name' => 'custom_login_logo', 'value' => '', 'description' => 'Custom login page logo url',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //82
        DB::table('preferences')->insert(['name' => 'webdav_backend', 'value' => '0', 'description' => 'Use WebDAV backend',
            'level' => 100, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
        
        //83
        DB::table('preferences')->insert(['name' => 'notify_email', 'value' => '0', 'description' => "Receive notifications by email (shouts, private messages, ...)",
            'level' => 100, 'type' => 'boolean', 'category' => 'opyions', 'subcategory' => '',
        ]);
        
        //84
        DB::table('preferences')->insert(['name' => 'theme_color', 'value' => 'dark', 'description' => 'Theme color',
            'level' => 0, 'type' => 'special', 'category' => 'interface', 'subcategory' => 'theme',
        ]);
        
        //85
        DB::table('preferences')->insert(['name' => 'disabled_custom_metadata_fields', 'value' => '', 'description' => "Disable custom metadata fields (ctrl / shift click to select multiple)",
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'metadata',
        ]);
        
        //86
        DB::table('preferences')->insert(['name' => 'disabled_custom_metadata_fields_input', 'value' => '', 'description' => "Disable custom metadata fields. Insert them in a comma separated list. They will add to the fields selected above.",
            'level' => 100, 'type' => 'string', 'category' => 'system', 'subcategory' => 'metadata',
        ]);
        
        //87
        DB::table('preferences')->insert(['name' => 'podcast_keep', 'value' => '10', 'description' => "Podcast: # latest episodes to keep.",
            'level' => 100, 'type' => 'integer', 'category' => 'system', 'subcategory' => 'metadata',
        ]);
        
        //88
        DB::table('preferences')->insert(['name' => 'podcast_new_download', 'value' => '1', 'description' => "Podcast: # episodes to download when new episodes are available",
            'level' => 100, 'type' => 'integer', 'category' => 'system', 'subcategory' => 'metadata',
        ]);
        
        //89
        DB::table('preferences')->insert(['name' => 'libitem_contextmenu', 'value' => '1', 'description' => 'Library item context menu',
            'level' => 100, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'library',
        ]);
        
        //90
        DB::table('preferences')->insert(['name' => 'upload_catalog_pattern', 'value' => '0', 'description' => 'Rename uploaded file according to catalog pattern',
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'upload',
        ]);
        
        //91
        DB::table('preferences')->insert(['name' => 'catalog_check_duplicate', 'value' => '0', 'description' => "Check library item at import time and don't import duplicates",
            'level' => 100, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'catalog',
         ]);
       
        //92
        DB::table('preferences')->insert(['name' => 'browse_filter', 'value' => '0', 'description' => 'Show filter box on browse',
            'level' => 25, 'type' => 'boolean', 'category' => 'system', 'subcategory' => 'library',
        ]);

        //93
        DB::table('preferences')->insert(['name' => 'sidebar_light', 'value' => '0', 'description' => 'Simple sidebar by default',
            'level' => 25, 'type' => 'boolean', 'category' => 'interface', 'subcategory' => 'theme',
        ]);

        //94
        DB::table('preferences')->insert(['name' => 'custom_blankalbum', 'value' => '', 'description' => 'Custom blank album default image',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
 
        //95
        DB::table('preferences')->insert(['name' => 'custom_blankmovie', 'value' => '', 'description' => 'Custom blank video default image',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'custom',
        ]);
  
        //96
        DB::table('preferences')->insert(['name' => 'libitem_browse_alpha', 'value' => '', 'description' => 'Alphabet browsing by default for following library items (album,artist,...)',
            'level' => 75, 'type' => 'string', 'category' => 'interface', 'subcategory' => 'library',
        ]);
  
        //97
        DB::table('preferences')->insert(['name' => 'lastfm_challenge', 'value' => '', 'description' => 'Last.FM Submit Challenge',
            'level' => 25, 'type' => 'string', 'category' => 'internal', 'subcategory' => 'lastfm',
        ]);
  
        //98
        DB::table('preferences')->insert(['name' => 'lastfm_grant_link', 'value' => '', 'description' => "Last.FM Grant URL",
            'level' => 25, 'type' => 'string', 'category' => 'internal', 'subcategory' => 'lastfm',
        ]);
    }
}
