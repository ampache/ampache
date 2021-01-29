# PLUGINS - Ampache v.4.2.0

Plugins are placed in modules/plugins; the name of the file must be "./Name/Name.plugin.php". e.g. Dummy/Dummy.plugin.php.
The file must declare a corresponding class and the name of the class must be prefixed with Ampache, e.g. AmpacheDummy.

The following public variables must be declared:

* name (string)
* description (string)
* version (int) - This plugin's version
* min_ampache (int) - Minimum Ampache DB version required
* max_ampache (int) - Maximum Ampache DB version supported

The following public methods must be implemented:

* install
* uninstall
* load

The following public methods may be implemented:

* upgrade

Finally, for the plugin to actually be useful one or more of the following hooks should be implemented as a public method:

* display_home() Display something in the home page / index
* display_on_footer() Same as home, except in the page footer
* display_user_field(library_item $libitem = null) This display the module in user page
* display_map(array $points) Used for graphs and charts
* external_share(string $public_url, string $share_name) Send a shared object to an external site
* gather_arts(string $type, array $options, integer $limit) Search for art externally
* get_avatar_url(User $user)
* get_lyrics(Song $song)
* get_location_name(float $latitude float $longitude)
* get_metadata(array $gather_types, array $media_info) Array of object types and array of info for that object
* get_photos(string $search_name)
* get_song_preview(string $track_mbid, string $artist_name, string $title)
* process_wanted(Wanted $wanted)
* save_mediaplay(Song $song)
* save_rating(Rating $rating, integer $new_value)
* set_flag(Song $song, boolean $flagged)
* shortener(string $url)
* stream_control(array $object_ids)
* stream_song_preview(string $file)
