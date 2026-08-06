#!/usr/bin/env python3
"""Human descriptions for every browse filter and sort, keyed by name.

generate_browse_docs.py reads the filter and sort names out of the PHP query
classes (src/Module/Database/Query/*Query.php) and looks each one up here, so
the published pages can say what a name actually does instead of dumping the
raw arrays. A name the code accepts but this file does not describe fails the
generator, which is how a new filter or sort gets documented with the code that
adds it.

Descriptions may use these placeholders, filled in from the TYPES entry:

    {item}   singular noun for one row of the browse, e.g. "album"
    {items}  plural noun, e.g. "albums"
    {match}  what the text filters compare against, e.g. "the album name"
    {Match}  {match} with the first letter capitalised

FILTERS/SORTS hold the description used for every browse type; the *_OVERRIDES
maps replace it for one type where that type really does behave differently.
"""
from __future__ import annotations

# Browse types published as a page, in sidebar order. `type` is the string passed to
# Browse::set_type(), `query` the class that implements it.
TYPES: dict[str, dict[str, object]] = {
    "album": {
        "title": "Album Browse",
        "type": "album",
        "query": "AlbumQuery",
        "item": "album",
        "items": "albums",
        "match": "the album name",
        "notes": [
            "An album row is per catalog, so the same album found in two catalogs is two albums with two ids.",
            "When the per-user `album_group` preference is off the web interface browses [album disks](https://ampache.org/api/browse/album_disk-browse) instead; the `albums` method never changes shape with that preference.",
        ],
    },
    "album_disk": {
        "title": "Album Disk Browse",
        "type": "album_disk",
        "query": "AlbumDiskQuery",
        "item": "album disk",
        "items": "album disks",
        "match": "the album name",
        "notes": [
            "**API8 only.** API3 to API6 have no album disk methods, so a client that wants disks has to ask for them by name.",
            "A disk has no name, dates or tags of its own; text filters, genre filters and the date filters all reach through to its album and songs.",
        ],
    },
    "artist": {
        "title": "Artist Browse",
        "type": "artist",
        "query": "ArtistQuery",
        "item": "artist",
        "items": "artists",
        "match": "the artist name",
        "notes": [
            "The `album_artist` and `song_artist` browses are this browse with the matching filter already applied, so they take the same filters and sorts.",
            "An artist is shared across catalogs (there is no artist catalog column), so the catalog filters resolve through `catalog_map`.",
        ],
    },
    "catalog": {
        "title": "Catalog Browse",
        "type": "catalog",
        "query": "CatalogQuery",
        "item": "catalog",
        "items": "catalogs",
        "match": "the catalog name",
        "notes": [],
    },
    "folder": {
        "title": "Folder Browse",
        "type": "folder",
        "query": "FolderQuery",
        "item": "folder",
        "items": "folders",
        "match": "the folder name or its full path",
        "notes": [
            "**API8 only.** The rows come from `folder_map`, so a row is either a folder or a media file inside one and the `id` is a `type-id` string like `folder-12` or `song-2280`.",
            "`int_id` is the plain numeric id of the object the row points at.",
        ],
    },
    "follower": {
        "title": "Follower Browse",
        "type": "follower",
        "query": "FollowerQuery",
        "item": "follow",
        "items": "follows",
        "match": "nothing (this browse has no text filters)",
        "notes": [
            "A row is one follow: `user` is the account being followed and `follow_user` is the account following them.",
            "The `followers` method sets `user` for you, so a `cond` sent with it narrows that list further rather than replacing it.",
        ],
    },
    "genre": {
        "title": "Genre Browse",
        "type": "tag",
        "query": "TagQuery",
        "item": "genre",
        "items": "genres",
        "match": "the genre name",
        "notes": [
            "Genres are stored as tags, so the browse type is `tag` and the `tag` filters and sorts are aliases of the `genre` ones.",
        ],
    },
    "label": {
        "title": "Label Browse",
        "type": "label",
        "query": "LabelQuery",
        "item": "label",
        "items": "labels",
        "match": "the label name",
        "notes": [],
    },
    "license": {
        "title": "License Browse",
        "type": "license",
        "query": "LicenseQuery",
        "item": "license",
        "items": "licenses",
        "match": "the license name",
        "notes": [
            "Licenses have no rating or favourite of their own, so this browse has none of the `rating` or `user_flag` filters and sorts the other types carry.",
        ],
    },
    "live_stream": {
        "title": "Live Stream Browse",
        "type": "live_stream",
        "query": "LiveStreamQuery",
        "item": "live stream",
        "items": "live streams",
        "match": "the live stream name",
        "notes": [],
    },
    "playlist": {
        "title": "Playlist Browse",
        "type": "playlist",
        "query": "PlaylistQuery",
        "item": "playlist",
        "items": "playlists",
        "match": "the playlist name",
        "notes": [
            "This browse is real playlists only. The `playlists` method combines them with smartlists through the [playlist_search browse](https://ampache.org/api/browse/playlist_search-browse) unless `hide_search=1` is sent.",
            "The items inside a playlist are returned by `playlist_songs`, which uses the `playlist_media` browse. That browse hands back the stored order and takes no filters or sorts at all.",
        ],
    },
    "playlist_search": {
        "title": "Playlist Search Browse",
        "type": "playlist_search",
        "query": "PlaylistSearchQuery",
        "item": "list",
        "items": "lists",
        "match": "the list name",
        "notes": [
            "This is the combined playlist and smartlist browse used by `playlists`, `index`, `list` and `stats`. Smartlist ids come back prefixed, so search `2256` is returned as `smart_2256`.",
            "It reads a union of the `playlist` and `search` tables, so it only offers the columns both of them have.",
        ],
    },
    "smartplaylist": {
        "title": "Smartlist Browse",
        "type": "smartplaylist",
        "query": "SmartplaylistQuery",
        "item": "smartlist",
        "items": "smartlists",
        "match": "the smartlist name",
        "notes": [
            "Smartlists are saved searches (the `search` table), so this browse can sort on the search's own `limit` and `random` settings.",
            "A saved smartlist loaded by id is always a song search; the object type it was built for is not stored.",
        ],
    },
    "podcast": {
        "title": "Podcast Browse",
        "type": "podcast",
        "query": "PodcastQuery",
        "item": "podcast",
        "items": "podcasts",
        "match": "the podcast title",
        "notes": [],
    },
    "podcast_episode": {
        "title": "Podcast Episode Browse",
        "type": "podcast_episode",
        "query": "PodcastEpisodeQuery",
        "item": "episode",
        "items": "episodes",
        "match": "the episode title",
        "notes": [],
    },
    "share": {
        "title": "Share Browse",
        "type": "share",
        "query": "ShareQuery",
        "item": "share",
        "items": "shares",
        "match": "nothing (this browse has no text filters)",
        "notes": [
            "The share browse accepts no filters at all, so `cond` is ignored by the `shares` method. Users only ever see their own shares unless they are an admin.",
        ],
    },
    "song": {
        "title": "Song Browse",
        "type": "song",
        "query": "SongQuery",
        "item": "song",
        "items": "songs",
        "match": "the song title",
        "notes": [],
    },
    "user": {
        "title": "User Browse",
        "type": "user",
        "query": "UserQuery",
        "item": "user",
        "items": "users",
        "match": "the full name, username or email",
        "notes": [
            "Users have no rating or favourite of their own, so this browse has no `user_flag` or `user_rating` filters.",
        ],
    },
    "video": {
        "title": "Video Browse",
        "type": "video",
        "query": "VideoQuery",
        "item": "video",
        "items": "videos",
        "match": "the video title",
        "notes": [],
    },
}

# Browse types that are really another type's page. Browse::set_type('album_artist')
# sets up an artist browse with the album_artist filter applied.
TYPE_ALIASES: dict[str, str] = {
    "album_artist": "artist",
    "song_artist": "artist",
    "tag": "genre",
    "tag_hidden": "genre",
    "license_hidden": "license",
}

# filter name -> (value column, description)
FILTERS: dict[str, tuple[str, str]] = {
    "access": (
        "integer",
        "Only {items} with this access level: `5` guest, `25` user, `50` content manager, `75` catalog manager, `100` admin.",
    ),
    "add_gt": ("UNIX timestamp", "Only {items} added to the catalog at or after this time."),
    "add_lt": ("UNIX timestamp", "Only {items} added to the catalog at or before this time."),
    "album": ("album id", "Only {items} from this album."),
    "album_artist": (
        "artist id",
        "Only {items} whose album artist is this artist. Send `0` for {items} that have no album artist.",
    ),
    "album_disk": ("album disk id", "Only {items} on this album disk."),
    "alpha_match": ("string", "Alias of `like`."),
    "artist": ("artist id", "Only {items} this artist is credited on."),
    "catalog": (
        "catalog id",
        "Only {items} in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.",
    ),
    "catalog_enabled": ("none", "Only {items} in an enabled catalog. Needs no value."),
    "disabled": ("0 or 1", "Send `1` for disabled {items}, `0` for enabled ones."),
    "disk": ("integer", "Only {items} on this disk number."),
    "enabled": ("0 or 1", "Send `1` for enabled {items}, `0` for disabled ones."),
    "equal": ("string", "{Match} is exactly this value. Matching is case insensitive."),
    "exact_match": ("string", "Alias of `equal`."),
    "follow_user": ("user id", "Only follows made by this user."),
    "gather_type": ("string", "Only catalogs gathering this media type: `music`, `video` or `podcast`."),
    "gather_types": (
        "array",
        "The array form of `gather_type`. `cond` can only send one string, so use `gather_type` from the API.",
    ),
    "genre": ("genre id", "Only {items} tagged with this genre."),
    "hidden": ("0 or 1", "Send `1` for hidden {items}, `0` for visible ones."),
    "hide_dupe_smartlist": (
        "none",
        "Drop a smartlist when a playlist of the same name belongs to the same user. Needs no value.",
    ),
    "id": (
        "array",
        "Only these {item} ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing.",
    ),
    "int_id": ("integer", "Only the row for this numeric object id. Send `-1` to match nothing."),
    "label": ("label id", "Only {items} signed to this label."),
    "license": ("license id", "Only {items} released under this license."),
    "like": ("string", "{Match} contains this value."),
    "no_genre": ("none", "Only {items} with no genre at all. Needs no value, and clears any `genre` filter."),
    "no_tag": ("none", "Alias of `no_genre`."),
    "not_like": ("string", "{Match} does not contain this value."),
    "not_starts_with": ("string", "{Match} does not start with this value."),
    "object_type": ("string", "Only {items} used by this object type."),
    "playlist_open": (
        "user id",
        "Only lists this user can open: public ones, their own, ones they collaborate on and ones shared with them.",
    ),
    "playlist_type": (
        "0 or 1",
        "Send `0` for lists owned by the current user, `1` for every list they can open. Sending it twice toggles it.",
    ),
    "playlist_user": ("user id", "Only lists owned by this user."),
    "podcast": ("podcast id", "Only episodes of this podcast."),
    "regex_match": (
        "regex",
        "{Match} matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.",
    ),
    "regex_not_match": (
        "regex",
        "{Match} does not match this MySQL regular expression. Setting it clears any `regex_match` filter.",
    ),
    "smartlist": ("none", "Only smartlists, the rows with a `smart_` prefixed id. Needs no value."),
    "song_artist": (
        "artist id",
        "Only {items} with a song credited to this artist. Send `0` for {items} with no such song.",
    ),
    "starts_with": ("string", "{Match} starts with this value."),
    "tag": ("genre id", "Alias of `genre`."),
    "top50": (
        "artist id",
        "Only songs credited to this artist, joined to their play counts so a `object_count` sort can build a top 50.",
    ),
    "unplayed": (
        "1",
        "Only {items} that have never been played. The value must be `1`; anything else, including an empty value, is ignored.",
    ),
    "update_gt": ("UNIX timestamp", "Only {items} whose tags were last updated at or after this time."),
    "update_lt": ("UNIX timestamp", "Only {items} whose tags were last updated at or before this time."),
    "user": ("user id", "Only {items} belonging to this user."),
    "user_catalog": ("none", "Only {items} in a catalog the current user is allowed to see. Needs no value."),
    "user_flag": (
        "0 or 1",
        "Send `1` for {items} the current user has favourited, `0` for the ones they have not.",
    ),
    "user_rating": (
        "0 to 5",
        "Only {items} the current user rated this value. Send `0` for {items} they have not rated at all.",
    ),
}

# type -> filter name -> (value column, description), replacing the generic text
FILTER_OVERRIDES: dict[str, dict[str, tuple[str, str]]] = {
    "album": {
        "add_gt": ("UNIX timestamp", "Only albums with a song added to the catalog at or after this time."),
        "add_lt": ("UNIX timestamp", "Only albums with a song added to the catalog at or before this time."),
        "update_gt": ("UNIX timestamp", "Only albums with a song whose tags were updated at or after this time."),
        "update_lt": ("UNIX timestamp", "Only albums with a song whose tags were updated at or before this time."),
        "artist": ("artist id", "Only albums this artist appears on, as the album artist or on one of the songs."),
    },
    "album_disk": {
        "add_gt": ("UNIX timestamp", "Only disks with a song added to the catalog at or after this time."),
        "add_lt": ("UNIX timestamp", "Only disks with a song added to the catalog at or before this time."),
        "update_gt": ("UNIX timestamp", "Only disks with a song whose tags were updated at or after this time."),
        "update_lt": ("UNIX timestamp", "Only disks with a song whose tags were updated at or before this time."),
        "album": ("album id", "Only the disks of this album."),
        "artist": ("artist id", "Only disks whose album has this artist on it, as the album artist or on a song."),
        "genre": ("genre id", "Only disks whose album is tagged with this genre."),
        "tag": ("genre id", "Alias of `genre`."),
        "no_genre": ("none", "Only disks whose album has no genre at all. Needs no value."),
        "no_tag": ("none", "Alias of `no_genre`."),
    },
    "artist": {
        "add_gt": ("UNIX timestamp", "Only artists with a song added to the catalog at or after this time."),
        "add_lt": ("UNIX timestamp", "Only artists with a song added to the catalog at or before this time."),
        "update_gt": ("UNIX timestamp", "Only artists with a song whose tags were updated at or after this time."),
        "update_lt": ("UNIX timestamp", "Only artists with a song whose tags were updated at or before this time."),
        "album_artist": ("0 or 1", "Send `1` for artists credited as an album artist, `0` for artists never credited as one."),
        "song_artist": ("0 or 1", "Send `1` for artists credited on a song, `0` for artists never credited on one."),
        "catalog": (
            "catalog id",
            "Only artists with something in this catalog, resolved through `catalog_map`. `0` means every catalog.",
        ),
        "user_catalog": ("none", "Only artists with something in a catalog the current user can see. Needs no value."),
    },
    "catalog": {
        "user": ("user id", "Only catalogs this user is allowed to see."),
        "enabled": ("0 or 1", "Send `1` for enabled catalogs, `0` for disabled ones."),
    },
    "folder": {
        "id": ("string", "Only the row with this `type-id` id, e.g. `folder-12`."),
        "equal": ("string", "The folder name or its full path is exactly this value."),
        "like": ("string", "The folder name or its full path contains this value."),
        "not_like": ("string", "Neither the folder name nor its full path contains this value."),
        "starts_with": ("string", "The folder name or its full path starts with this value."),
        "not_starts_with": ("string", "Neither the folder name nor its full path starts with this value."),
        "regex_match": ("regex", "The folder name or its full path matches this MySQL regular expression."),
        "regex_not_match": ("regex", "Neither the folder name nor its full path matches this MySQL regular expression."),
    },
    "follower": {
        "user": ("user id", "Only follows of this user, i.e. the people following them."),
    },
    "genre": {
        "genre": ("genre id", "Only this genre id."),
        "tag": ("genre id", "Alias of `genre`."),
        "hidden": ("0 or 1", "Send `1` for hidden genres, `0` for visible ones."),
        "object_type": (
            "string",
            "Only genres applied to this object type: `song`, `album`, `artist` or `video`. The tag map join is not deduplicated, so a genre comes back once per object carrying it.",
        ),
    },
    "license": {
        "hidden": (
            "0 or 1",
            "Send `1` for licenses hidden from the edit menus (sort order `0`), `0` for the ones offered there.",
        ),
    },
    "playlist": {
        "user_rating": ("0 to 5", "Only playlists the current user rated this value. Send `0` for unrated ones."),
    },
    "playlist_search": {
        "id": (
            "array",
            "Only these list ids, including `smart_` prefixed ones. It takes a list, which `cond` cannot send, so `cond=id,1` returns nothing.",
        ),
    },
    "song": {
        "artist": ("artist id", "Only songs this artist is credited on, through `artist_map`."),
        "genre": ("genre id", "Only songs tagged with this genre."),
        "enabled": ("0 or 1", "Send `1` for enabled songs, `0` for songs disabled by a catalog clean."),
        "disk": ("integer", "Only songs on this disk number of their album."),
    },
    "user": {
        "equal": ("string", "The full name, username or email is exactly this value."),
        "like": ("string", "The full name, username or email contains this value."),
        "not_like": ("string", "The full name, username or email does not contain this value."),
        "starts_with": ("string", "The full name, username or email starts with this value."),
        "not_starts_with": ("string", "None of the full name, username or email starts with this value."),
        "regex_match": ("regex", "The full name, username or email matches this MySQL regular expression."),
        "regex_not_match": ("regex", "None of the full name, username or email matches this MySQL regular expression."),
        "disabled": ("0 or 1", "Send `1` for disabled accounts, `0` for active ones."),
    },
    "video": {
        "add_gt": ("UNIX timestamp", "Only videos added to the catalog at or after this time."),
        "add_lt": ("UNIX timestamp", "Only videos added to the catalog at or before this time."),
    },
}

# sort name -> description
SORTS: dict[str, str] = {
    "access": "Access level, lowest first.",
    "active": "Whether the label is still active.",
    "addition_time": "When it was added to the catalog.",
    "album": "Album name, then disk and track number.",
    "album_artist": "Album artist name.",
    "album_artist_album_sort": "Album artist name, then whatever the server's `album_sort` setting says, which is album name and year by default.",
    "album_artist_title": "Album artist name, then album name.",
    "album_count": "How many albums the artist has.",
    "album_disk": "Album name, then disk and track number.",
    "album_id": "The id of the album the disk belongs to.",
    "allow_download": "Whether the share allows downloads.",
    "allow_stream": "Whether the share allows streaming.",
    "artist": "Artist name.",
    "author": "Episode author.",
    "barcode": "Album barcode.",
    "catalog": "The catalog id it belongs to.",
    "catalog_number": "Album catalog number.",
    "catalog_type": "Catalog backend: `local`, `beets`, `remote`, `seafile`, `subsonic` or `dropbox`.",
    "category": "Episode category.",
    "city": "City on the user's profile.",
    "codec": "Stream codec.",
    "composer": "Composer tag.",
    "counter": "How many times the share has been used.",
    "country": "Label country.",
    "create_date": "When the account was created.",
    "creation_date": "When it was created.",
    "date": "When the {item} was created.",
    "disabled": "Disabled accounts group together.",
    "disk": "Disk number.",
    "disk_count": "How many disks the album has.",
    "disksubtitle": "Disk subtitle, then album name and disk number.",
    "email": "Email address.",
    "enabled": "Enabled catalogs group together.",
    "episodes": "How many episodes the podcast has.",
    "expire": "How many days the share lasts.",
    "external_link": "The license URL.",
    "follow_date": "When the follow was made.",
    "follow_user": "The id of the user doing the following.",
    "fullname": "The user's full name.",
    "fullname_public": "Whether the user shows their full name publicly.",
    "gather_types": "The media type the catalog gathers: `music`, `video` or `podcast`.",
    "generic_artist": "Album artist name, falling back to the artist of a song on the album when the album has no album artist.",
    "genre": "Genre string on the stream.",
    "id": "The {item} id, which is the order they were created in.",
    "int_id": "The numeric id of the object the row points at.",
    "last_add": "When something was last added to the catalog.",
    "last_clean": "When the catalog was last cleaned.",
    "last_count": "How many items the {item} held when it was last counted.",
    "last_seen": "When the user was last seen.",
    "last_update": "When it was last updated.",
    "lastvisit_date": "When the share was last opened.",
    "length": "Running time in seconds.",
    "limit": "The row limit saved on the smartlist.",
    "max_counter": "The share's maximum allowed uses.",
    "mbid": "MusicBrainz id.",
    "name": "{Item} name.",
    "name_original_year": "Album name, then original release year, falling back to the release year.",
    "name_year": "Album name, then release year.",
    "object": "Shared object type, then object id.",
    "object_count": "How many times the songs have been played, counted per song.",
    "object_type": "The type of object the row points at.",
    "order": "The sort order set on the license. `0` hides it from the edit menus.",
    "original_year": "Original release year, falling back to the release year, then when it was added.",
    "placeformed": "Where the artist was formed.",
    "podcast": "The podcast id, then publication date.",
    "pubdate": "When the episode was published.",
    "rand": "Random order, applied per request. Paging through it repeats and skips {items}, so ask for everything in one call.",
    "random": "Whether the smartlist returns its rows randomly.",
    "rating": "Your own rating, then when you set it. {Items} you have not rated group together.",
    "release_status": "Album release status, e.g. `official` or `bootleg`.",
    "release_type": "Album release type, e.g. `album`, `ep` or `single`.",
    "rename_pattern": "The catalog's rename pattern.",
    "resolution": "Video width in pixels.",
    "site_url": "The stream's home page.",
    "song_count": "How many songs it has.",
    "sort_pattern": "The catalog's sort pattern.",
    "state": "Processing state of the episode.",
    "subtitle": "Album subtitle.",
    "time": "Total running time in seconds.",
    "title": "Alias of `name`.",
    "total_count": "How many times it has been played.",
    "total_skip": "How many times it has been skipped.",
    "track": "Track number.",
    "type": "Visibility: `public` or `private`.",
    "update_time": "When its tags were last updated.",
    "url": "The stream URL.",
    "user": "The id of the user who owns it.",
    "user_flag": "When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together.",
    "user_flag_rating": "When you added it to your favourites, then your rating.",
    "userflag": "Alias of `user_flag`.",
    "username": "The owner's username.",
    "version": "Album version.",
    "website": "Website address.",
    "year": "Release year, then when it was added to the catalog.",
    "yearformed": "The year the artist formed.",
}

# type -> sort name -> description, replacing the generic text
SORT_OVERRIDES: dict[str, dict[str, str]] = {
    "album": {
        "name": "Album name.",
        "time": "Total running time of the album in seconds.",
    },
    "album_disk": {
        "name": "Album name, then original release year and disk number.",
        "album_artist_title": "Album artist name, then album name and disk number.",
        "album_artist_album_sort": "Album artist name, then whatever the server's `album_sort` setting says, then disk number.",
        "disk": "Disk number, then album name and disk number again.",
        "name_original_year": "Album name, then original release year and disk number.",
        "name_year": "Album name, then release year and disk number.",
        "addition_time": "When the album was added to the catalog. A disk has no addition time of its own.",
        "catalog": "The catalog id, then album name and disk number.",
        "disk_count": "How many disks the album has, then album name and disk number.",
        "song_count": "How many songs the disk has, then album name and disk number.",
        "time": "Running time of the disk in seconds, then album name and disk number.",
        "total_count": "How many times the disk has been played, then album name and disk number.",
        "barcode": "Album barcode. Taken from the album, not the disk.",
        "catalog_number": "Album catalog number. Taken from the album, not the disk.",
        "release_status": "Album release status. Taken from the album, not the disk.",
        "release_type": "Album release type. Taken from the album, not the disk.",
        "subtitle": "Album subtitle. Taken from the album, not the disk.",
        "version": "Album version. Taken from the album, not the disk.",
        "year": "Release year, then when the album was added to the catalog.",
    },
    "artist": {
        "name": "Artist name.",
        "time": "Total running time of the artist's songs in seconds.",
        "total_count": "How many times the artist's songs have been played.",
    },
    "catalog": {
        "name": "Catalog name.",
        "id": "The catalog id, which is the order they were created in.",
    },
    "folder": {
        "name": "Folders first, then name. This is the only sort that keeps folders above the files in them.",
        "id": "The `type-id` id string, which groups folders and files apart.",
    },
    "genre": {
        "name": "Genre name.",
        "id": "The genre id, which is the order they were first seen in.",
        "artist": "How many artists carry this genre.",
        "album": "How many albums carry this genre.",
        "song": "How many songs carry this genre.",
        "video": "How many videos carry this genre.",
        "tag": "Alias of `id`.",
    },
    "label": {
        "name": "Label name.",
        "user": "The id of the user who added the label.",
    },
    "license": {
        "name": "License name.",
    },
    "live_stream": {
        "name": "Stream name.",
    },
    "playlist": {
        "name": "Playlist name, then id, so playlists with the same name keep a stable order.",
        "date": "When the playlist was created.",
        "last_count": "How many items the playlist held when it was last counted.",
    },
    "playlist_search": {
        "name": "List name, then id, so lists with the same name keep a stable order.",
        "id": "The list id. Smartlist ids are prefixed with `smart_`, so they sort apart from playlists.",
        "date": "When the list was created.",
        "last_count": "How many items the list held when it was last counted.",
    },
    "smartplaylist": {
        "name": "Smartlist name, then id, so smartlists with the same name keep a stable order.",
        "date": "When the smartlist was created.",
        "last_count": "How many items the smartlist returned when it was last counted.",
    },
    "podcast": {
        "name": "Podcast title.",
        "total_count": "How many times the podcast's episodes have been played.",
    },
    "podcast_episode": {
        "name": "Episode title.",
        "time": "Episode running time in seconds.",
    },
    "share": {
        "user": "The id of the user who created the share.",
    },
    "song": {
        "name": "Song title.",
        "artist": "Artist name. This is the song artist, not the album artist.",
        "album": "Album name, then the song's own disk and track number.",
        "album_disk": "Album name, then the album disk's number and the track number.",
        "time": "Song length in seconds.",
        "year": "Release year on the song tag.",
    },
    "user": {
        "id": "The user id, which is the order the accounts were created in.",
    },
    "video": {
        "name": "Video title.",
        "codec": "Video codec.",
        "length": "Video length in seconds.",
    },
}
