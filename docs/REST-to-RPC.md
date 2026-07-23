# RESTful Resource Path Structure

Key characteristics:

* Resource-oriented URL design
* Plural resource naming
* Hierarchical relationship modelling
* Proper HTTP verb usage
* OpenAPI-compliant specification
* Clear separation between transport and intent

## Base Path

All REST urls require the API version and format as part of the base url.

```URL
{ampacheURL}/rest/{version}/{format}
```

e.g. `https://demo.ampache.dev/rest/8/json`

## Resources and Items

You access objects from resource paths.

```URL
{ampacheURL}/rest/{version}/{format}/{resource}
```

e.g. `https://demo.ampache.dev/rest/8/json/albums`

Resources can them be filtered into individual items.

```URL
{ampacheURL}/rest/{version}/{format}/{resource}/{identifier}
```

e.g. `https://demo.ampache.dev/rest/8/json/albums/21`

## Nested Resource

When you've filtered to an individual resource you can perform actions on that resource or fetch children of the object.

```URL
{ampacheURL}/rest/{version}/{format}/{parent}/{id}/{child}
```

e.g. `https://demo.ampache.dev/rest/8/json/albums/21/songs`

## REST path to RPC call map

If you're struggling to map the new paths to actions this table will allow you to ID the calls you want to make using the old RPC methods.

All REST path URLs are prefixed with the Ampache [Base Path](#base-path) to make sure you are given the correct format and version response.

e.g. To make a REST call for 10 random albums your URL would be `https://demo.ampache.dev/rest/8/json/albums/?limit=10&sort=rand`

| HTTP   | REST                                                 | RPC action                                                                       | Alternative action |
|--------|------------------------------------------------------|----------------------------------------------------------------------------------|--------------------|
| GET    | `albums/{album_id}/art`                              | `?action=get_art&filter={album_id}&type=album`                                   |                    |
| GET    | `albums/{album_id}/fetch-metadata`                   | `?action=get_external_metadata&filter={album_id}&type=album`                     |                    |
| POST   | `albums/{album_id}/flag`                             | `?action=flag&filter={album_id}&type=album`                                      |                    |
| POST   | `albums/{album_id}/rate`                             | `?action=rate&filter={album_id}&type=album`                                      |                    |
| POST   | `albums/{album_id}/share`                            | `?action=share_create&filter={album_id}&type=album`                                     |                    |
| GET    | `albums/{album_id}/disks`                            | `?action=album_disks&filter={album_id}&type=album`                               |                    |
| GET    | `albums/{album_id}/songs`                            | `?action=album_songs&filter={album_id}&type=album`                               |                    |
| POST   | `albums/{album_id}/update-art`                       | `?action=update_art&filter={album_id}&type=album`                                |                    |
| POST   | `albums/{album_id}/update-tags`                      | `?action=update_from_tags&filter={album_id}&type=album`                          |                    |
| GET    | `albums/{album_id}`                                  | `?action=album&filter={album_id}`                                                |                    |
| GET    | `albums/search`                                      | `?action=search&type=album`                                                      | `advanced_search`  |
| GET    | `albums/stats`                                       | `?action=stats&type=album`                                                       |                    |
| GET    | `albums`                                             | `?action=albums`                                                                 |                    |
| GET    | `album-disks/{album_disk_id}/art`                    | `?action=get_art&filter={album_disk_id}&type=album_disk`                         |                    |
| POST   | `album-disks/{album_disk_id}/flag`                   | `?action=flag&filter={album_disk_id}&type=album_disk`                            |                    |
| POST   | `album-disks/{album_disk_id}/rate`                   | `?action=rate&filter={album_disk_id}&type=album_disk`                            |                    |
| GET    | `album-disks/{album_disk_id}/songs`                  | `?action=album_disk_songs&filter={album_disk_id}&type=album_disk`                |                    |
| GET    | `album-disks/{album_disk_id}`                        | `?action=album_disk&filter={album_disk_id}`                                      |                    |
| GET    | `album-disks/search`                                 | `?action=search&type=album_disk`                                                 | `advanced_search`  |
| GET    | `album-disks/stats`                                  | `?action=stats&type=album_disk`                                                  |                    |
| GET    | `artists/{artist_id}/albums`                         | `?action=artist_albums&filter={artist_id}`                                       |                    |
| GET    | `artists/{artist_id}/art`                            | `?action=get_art&filter={artist_id}&type=artist`                                 |                    |
| POST   | `artists/{artist_id}/fetch-info`                     | `?action=update_artist_info&filter={artist_id}&type=artist`                      |                    |
| GET    | `artists/{artist_id}/fetch-metadata`                 | `?action=get_external_metadata&filter={artist_id}&type=artist`                   |                    |
| POST   | `artists/{artist_id}/flag`                           | `?action=flag&filter={artist_id}&type=artist`                                    |                    |
| POST   | `artists/{artist_id}/rate`                           | `?action=rate&filter={artist_id}&type=artist`                                    |                    |
| POST   | `artists/{artist_id}/share`                          | `?action=share_create&filter={artist_id}&type=artist`                                   |                    |
| GET    | `artists/{artist_id}/similar`                        | `?action=get_similar&filter={artist_id}&type=artist`                             |                    |
| GET    | `artists/{artist_id}/songs`                          | `?action=artist_songs&filter={artist_id}&type=artist`                            |                    |
| POST   | `artists/{artist_id}/update-art`                     | `?action=update_art&filter={artist_id}&type=artist`                              |                    |
| POST   | `artists/{artist_id}/update-tags`                    | `?action=update_from_tags&filter={artist_id}&type=artist`                        |                    |
| GET    | `artists/{artist_id}`                                | `?action=artist&filter={artist_id}`                                              |                    |
| GET    | `artists/search`                                     | `?action=search&type=artist`                                                     | `advanced_search`  |
| GET    | `artists/stats`                                      | `?action=stats&type=artist`                                                      |                    |
| GET    | `artists`                                            | `?action=artists`                                                                |                    |
| GET    | `bookmarks/{bookmark_id}`                            | `?action=bookmark&filter={bookmark_id}`                                          | `get_bookmark`     |
| PATCH  | `bookmarks/{bookmark_id}`                            | `?action=bookmark_edit&filter={bookmark_id}`                                     |                    |
| DELETE | `bookmarks/{bookmark_id}`                            | `?action=bookmark_delete&filter={bookmark_id}`                                   |                    |
| GET    | `bookmarks`                                          | `?action=bookmarks`                                                              |                    |
| PUT    | `bookmarks`                                          | `?action=bookmark_create&filter={object_id}&type={object_type}`                  |                    |
| GET    | `browse`                                             | `?action=browse`                                                                 |                    |
| POST   | `catalogs/{catalog_id}/action`                       | `?action=catalog_action&filter={catalog_id}&task={task}`                         |                    |
| POST   | `catalogs/{catalog_id}/add`                          | `?action=catalog_action&filter={catalog_id}&task=add_to_catalog`                 |                    |
| GET    | `catalogs/{catalog_id}/browse/albums/{album_id}`     | `?action=browse&filter={album_id}&type=album&catalog={catalog_id}`               |                    |
| GET    | `catalogs/{catalog_id}/browse/artists/{artist_id}`   | `?action=browse&filter={artist_id}&type=artist&catalog={catalog_id}`             |                    |
| GET    | `catalogs/{catalog_id}/browse/podcasts/{podcast_id}` | `?action=browse&filter={podcast_id}&type=podcast&catalog={catalog_id}`           |                    |
| GET    | `catalogs/{catalog_id}/browse`                       | `?action=browse&filter={catalog_id}&type=catalog`                                |                    |
| POST   | `catalogs/{catalog_id}/clean`                        | `?action=catalog_action&filter={catalog_id}&task=clean_catalog`                  |                    |
| POST   | `catalogs/{catalog_id}/file`                         | `?action=catalog_file&filter={catalog_id}`                                       |                    |
| GET    | `catalogs/{catalog_id}/folder`                       | `?action=catalog_folder&filter={catalog_id}`                                     |                    |
| POST   | `catalogs/{catalog_id}/folder`                       | `?action=catalog_folder&filter={catalog_id}`                                     |                    |
| POST   | `catalogs/{catalog_id}/update`                       | `?action=catalog_action&filter={catalog_id}&task=update_catalog`                 |                    |
| POST   | `catalogs/{catalog_id}/verify`                       | `?action=catalog_action&filter={catalog_id}&task=verify_catalog`                 |                    |
| GET    | `catalogs/{catalog_id}`                              | `?action=catalog&filter={catalog_id}`                                            |                    |
| DELETE | `catalogs/{catalog_id}`                              | `?action=catalog_delete&filter={catalog_id}`                                     |                    |
| GET    | `catalogs`                                           | `?action=catalogs`                                                               |                    |
| PUT    | `catalogs`                                           | `?action=catalog_create`                                                         | `catalog_add`      |
| POST   | `democratic/{object_id}/localplay`                   | `?action=localplay&command=add&filter={object_id}&type=democratic`               |                    |
| POST   | `democratic/{object_id}`                             | `?action=democratic&filter={object_id}`                                          |                    |
| GET    | `folder`                                             | `?action=folder`                                                                 |                    |
| GET    | `folders`                                            | `?action=folders`                                                                |                    |
| GET    | `genres/{genre_id}/albums`                           | `?action=genre_albums&filter={genre_id}`                                         |                    |
| GET    | `genres/{genre_id}/artists`                          | `?action=genre_artists&filter={genre_id}`                                        |                    |
| GET    | `genres/{genre_id}/songs`                            | `?action=genre_songs&filter={genre_id}`                                          |                    |
| GET    | `genres/{genre_id}`                                  | `?action=genre&filter={genre_id}`                                                |                    |
| GET    | `genres/search`                                      | `?action=search&type=genre`                                                      | `advanced_search`  |
| GET    | `genres`                                             | `?action=genres`                                                                 |                    |
| POST   | `goodbye`                                            | `?action=goodbye`                                                                |                    |
| POST   | `handshake`                                          | `?action=handshake`                                                              |                    |
| GET    | `index`                                              | `?action=index`                                                                  |                    |
| GET    | `labels/{label_id}/artists`                          | `?action=label_artists&filter={label_id}`                                        |                    |
| GET    | `labels/{label_id}/fetch-metadata`                   | `?action=get_external_metadata&filter={label_id}&type=label`                     |                    |
| GET    | `labels/{label_id}`                                  | `?action=label&filter={label_id}`                                                |                    |
| GET    | `labels/search`                                      | `?action=search&type=label`                                                      | `advanced_search`  |
| GET    | `labels`                                             | `?action=labels`                                                                 |                    |
| GET    | `licenses/{license_id}/songs`                        | `?action=license_songs&filter={license_id}`                                      |                    |
| GET    | `licenses/{license_id}`                              | `?action=license&filter={license_id}`                                            |                    |
| GET    | `licenses`                                           | `?action=licenses`                                                               |                    |
| GET    | `list`                                               | `?action=list`                                                                   |                    |
| POST   | `live-streams/{live_stream_id}/localplay`            | `?action=localplay&command=add&filter={live_stream_id}&type=live_stream`         |                    |
| GET    | `live-streams/{live_stream_id}`                      | `?action=live_stream&filter={live_stream_id}`                                    |                    |
| PATCH  | `live-streams/{live_stream_id}`                      | `?action=live_stream_edit&filter={live_stream_id}`                               |                    |
| DELETE | `live-streams/{live_stream_id}`                      | `?action=live_stream_delete&filter={live_stream_id}`                             |                    |
| GET    | `live-streams`                                       | `?action=live_streams`                                                           |                    |
| PUT    | `live-streams`                                       | `?action=live_stream_create`                                                     |                    |
| POST   | `localplay/add`                                      | `?action=localplay&command=add&filter={object_id}&type={object_type}`            |                    |
| POST   | `localplay/delete-all`                               | `?action=localplay&command=delete_all`                                           |                    |
| POST   | `localplay/next`                                     | `?action=localplay&command=next`                                                 |                    |
| POST   | `localplay/pause`                                    | `?action=localplay&command=pause`                                                |                    |
| POST   | `localplay/play`                                     | `?action=localplay&command=play`                                                 |                    |
| POST   | `localplay/prev`                                     | `?action=localplay&command=prev`                                                 |                    |
| POST   | `localplay/skip`                                     | `?action=localplay&command=skip`                                                 |                    |
| GET    | `localplay/songs`                                    | `?action=localplay_songs`                                                        |                    |
| GET    | `localplay/status`                                   | `?action=localplay&command=status`                                               |                    |
| POST   | `localplay/stop`                                     | `?action=localplay&command=stop`                                                 |                    |
| POST   | `localplay/volume-down`                              | `?action=localplay&command=volume_down`                                          |                    |
| POST   | `localplay/volume-mute`                              | `?action=localplay&command=volume_mute`                                          |                    |
| POST   | `localplay/volume-up`                                | `?action=localplay&command=volume_up`                                            |                    |
| GET    | `me/friends-timeline`                                | `?action=friends_timeline`                                                       |                    |
| GET    | `me/last-shouts`                                     | `?action=last_shouts`                                                            |                    |
| GET    | `me/lost-password`                                   | `?action=lost_password`                                                          |                    |
| GET    | `me/now-playing`                                     | `?action=now_playing`                                                            |                    |
| GET    | `me/playlists`                                       | `?action=user_playlists`                                                         |                    |
| GET    | `me/smartlists`                                      | `?action=user_smartlists`                                                        |                    |
| GET    | `me`                                                 | `?action=user`                                                                   |                    |
| GET    | `ping`                                               | `?action=ping`                                                                   |                    |
| POST   | `playlists/{playlist_id}/add`                        | `?action=playlist_add&filter={playlist_id}`                                      |                    |
| GET    | `playlists/{playlist_id}/art`                        | `?action=get_art&filter={playlist_id}&type=playlist`                             |                    |
| GET    | `playlists/{playlist_id}/download`                   | `?action=download&filter={playlist_id}&type=playlist`                            |                    |
| POST   | `playlists/{playlist_id}/flag`                       | `?action=flag&filter={playlist_id}`                                              |                    |
| GET    | `playlists/{playlist_id}/hash`                       | `?action=playlist_hash&filter={playlist_id}`                                     |                    |
| POST   | `playlists/{playlist_id}/rate`                       | `?action=rate&filter={playlist_id}`                                              |                    |
| POST   | `playlists/{playlist_id}/remove-song`                | `?action=playlist_remove_song&filter={playlist_id}&song={song_id}`               |                    |
| POST   | `playlists/{playlist_id}/remove`                     | `?action=playlist_remove&filter={playlist_id}&id={object_id}&type={object_type}` |                    |
| POST   | `playlists/{playlist_id}/share`                      | `?action=share_create&filter={playlist_id}`                                             |                    |
| GET    | `playlists/{playlist_id}/songs`                      | `?action=playlist_songs&filter={playlist_id}`                                    |                    |
| GET    | `playlists/{playlist_id}/stream`                     | `?action=stream&filter={playlist_id}&type=playlist`                              |                    |
| GET    | `playlists/{playlist_id}`                            | `?action=playlist&filter={playlist_id}`                                          |                    |
| PATCH  | `playlists/{playlist_id}`                            | `?action=playlist_edit&filter={playlist_id}`                                     |                    |
| DELETE | `playlists/{playlist_id}`                            | `?action=playlist_delete&filter={playlist_id}`                                   |                    |
| GET    | `playlists/search`                                   | `?action=search&type=playlist`                                                   | `advanced_search`  |
| GET    | `playlists/stats`                                    | `?action=stats&type=playlist`                                                    |                    |
| GET    | `playlists`                                          | `?action=playlists`                                                              |                    |
| PUT    | `playlists`                                          | `?action=playlist_create`                                                        |                    |
| GET    | `podcast-episodes/{episode_id}/bookmark`             | `?action=bookmark&filter={episode_id}&type=podcast_episode`                      | `get_bookmark`     |
| PATCH  | `podcast-episodes/{episode_id}/bookmark`             | `?action=bookmark_edit&filter={episode_id}&type=podcast_episode`                 |                    |
| DELETE | `podcast-episodes/{episode_id}/bookmark`             | `?action=bookmark_delete&filter={episode_id}&type=podcast_episode`               |                    |
| PUT    | `podcast-episodes/{episode_id}/bookmark`             | `?action=bookmark_create&filter={episode_id}&type=podcast_episode`               |                    |
| GET    | `podcast-episodes/{episode_id}/download`             | `?action=download&filter={episode_id}&type=podcast_episode`                      |                    |
| POST   | `podcast-episodes/{episode_id}/flag`                 | `?action=flag&filter={episode_id}&type=podcast_episode`                          |                    |
| POST   | `podcast-episodes/{episode_id}/localplay`            | `?action=localplay&command=add&filter={episode_id}&type=podcast_episode`         |                    |
| POST   | `podcast-episodes/{episode_id}/playback`             | `?action=player&filter={episode_id}&type=podcast_episode`                        |                    |
| POST   | `podcast-episodes/{episode_id}/rate`                 | `?action=rate&filter={episode_id}&type=podcast_episode`                          |                    |
| POST   | `podcast-episodes/{episode_id}/share`                | `?action=share_create&filter={episode_id}&type=podcast_episode`                         |                    |
| GET    | `podcast-episodes/{episode_id}/stream`               | `?action=stream&filter={episode_id}&type=podcast_episode`                        |                    |
| GET    | `podcast-episodes/{episode_id}`                      | `?action=podcast_episode&filter={episode_id}`                                    |                    |
| DELETE | `podcast-episodes/{episode_id}`                      | `?action=podcast_episode_delete&filter={episode_id}`                             |                    |
| GET    | `podcast-episodes/deleted`                           | `?action=deleted_podcast_episodes`                                               |                    |
| GET    | `podcast-episodes/search`                            | `?action=search&type=podcast_episode`                                            | `advanced_search`  |
| GET    | `podcast-episodes/stats`                             | `?action=stats&type=podcast_episode`                                             |                    |
| GET    | `podcast-episodes`                                   | `?action=podcast_episodes`                                                       |                    |
| GET    | `podcasts/{podcast_id}/art`                          | `?action=get_art&filter={podcast_id}&type=podcast`                               |                    |
| POST   | `podcasts/{podcast_id}/flag`                         | `?action=flag&filter={podcast_id}`                                               |                    |
| GET    | `podcasts/{podcast_id}/podcast-episodes`             | `?action=podcast_episodes&filter={podcast_id}`                                   |                    |
| POST   | `podcasts/{podcast_id}/rate`                         | `?action=rate&filter={podcast_id}`                                               |                    |
| POST   | `podcasts/{podcast_id}/share`                        | `?action=share_create&filter={podcast_id}`                                              |                    |
| POST   | `podcasts/{podcast_id}/sync`                         | `?action=update_podcast&filter={podcast_id}`                                     | `podcast_update`   |
| GET    | `podcasts/{podcast_id}`                              | `?action=podcast&filter={podcast_id}`                                            |                    |
| PATCH  | `podcasts/{podcast_id}`                              | `?action=podcast_edit&filter={podcast_id}`                                       |                    |
| DELETE | `podcasts/{podcast_id}`                              | `?action=podcast_delete&filter={podcast_id}`                                     |                    |
| GET    | `podcasts/search`                                    | `?action=search&type=podcast`                                                    | `advanced_search`  |
| GET    | `podcasts/stats`                                     | `?action=stats&type=podcast`                                                     |                    |
| GET    | `podcasts`                                           | `?action=podcasts`                                                               |                    |
| PUT    | `podcasts`                                           | `?action=podcast_create`                                                         |                    |
| GET    | `preferences/{preference_name}`                      | `?action=user_preference&filter={preference_name}`                              | `preferences`      |
| PATCH  | `preferences/{preference_name}`                      | `?action=preference_edit&filter={preference_name}`                               |                    |
| DELETE | `preferences/{preference_name}`                      | `?action=preference_delete&filter={preference_name}`                             |                    |
| GET    | `preferences`                                        | `?action=user_preferences`                                                       | `preferences`      |
| PUT    | `preferences`                                        | `?action=preference_create`                                                      |                    |
| GET    | `random`                                             | `?action=random&type={type}`                                                     |                    |
| POST   | `register`                                           | `?action=register`                                                               |                    |
| POST   | `scrobble`                                           | `?action=scrobble`                                                               |                    |
| GET    | `search/{search_type}/groups`                        | `?action=search_group&filter={search_type}`                                      |                    |
| GET    | `search/{search_type}/rules`                         | `?action=search_rules&filter={search_type}`                                      |                    |
| GET    | `shares/{share_id}`                                  | `?action=share&filter={share_id}`                                                |                    |
| PATCH  | `shares/{share_id}`                                  | `?action=share_edit&filter={share_id}`                                           |                    |
| DELETE | `shares/{share_id}`                                  | `?action=share_delete&filter={share_id}`                                         |                    |
| GET    | `shares`                                             | `?action=shares`                                                                 |                    |
| PUT    | `shares`                                             | `?action=share_create`                                                           |                    |
| GET    | `smartlists/{smartlist_id}/art`                      | `?action=get_art&filter={smartlist_id}&type=smartlist`                           |                    |
| GET    | `smartlists/{smartlist_id}/download`                 | `?action=download&filter={smartlist_id}&type=smartlist`                          |                    |
| POST   | `smartlists/{smartlist_id}/flag`                     | `?action=flag&filter={smartlist_id}&type=smartlist`                              |                    |
| POST   | `smartlists/{smartlist_id}/rate`                     | `?action=rate&filter={smartlist_id}&type=smartlist`                              |                    |
| POST   | `smartlists/{smartlist_id}/share`                    | `?action=share_create&filter={smartlist_id}&type=smartlist`                             |                    |
| GET    | `smartlists/{smartlist_id}/songs`                    | `?action=smartlist_songs&filter={smartlist_id}`                                  |                    |
| GET    | `smartlists/{smartlist_id}/stream`                   | `?action=stream&filter={smartlist_id}&type=smartlist`                            |                    |
| GET    | `smartlists/{smartlist_id}`                          | `?action=smartlist&filter={smartlist_id}`                                        |                    |
| DELETE | `smartlists/{smartlist_id}`                          | `?action=smartlist_delete&filter={smartlist_id}`                                 |                    |
| GET    | `smartlists`                                         | `?action=smartlists`                                                             |                    |
| GET    | `songs/{song_id}/art`                                | `?action=get_art&filter={song_id}&type=song`                                     |                    |
| GET    | `songs/{song_id}/bookmark`                           | `?action=bookmark&filter={song_id}&type=song`                                    | `get_bookmark`     |
| PATCH  | `songs/{song_id}/bookmark`                           | `?action=bookmark_edit&filter={song_id}&type=song`                               |                    |
| DELETE | `songs/{song_id}/bookmark`                           | `?action=bookmark_delete&filter={song_id}&type=song`                             |                    |
| PUT    | `songs/{song_id}/bookmark`                           | `?action=bookmark_create&filter={song_id}&type=song`                             |                    |
| GET    | `songs/{song_id}/download`                           | `?action=download&filter={song_id}`                                              |                    |
| GET    | `songs/{song_id}/fetch-metadata`                     | `?action=get_external_metadata&filter={song_id}&type=song`                       |                    |
| POST   | `songs/{song_id}/flag`                               | `?action=flag&filter={song_id}`                                                  |                    |
| POST   | `songs/{song_id}/localplay`                          | `?action=localplay&command=add&filter={song_id}&type=song`                       |                    |
| GET    | `songs/{song_id}/lyrics`                             | `?action=get_lyrics&filter={song_id}`                                            |                    |
| POST   | `songs/{song_id}/playback`                           | `?action=player&filter={song_id}&type=song`                                      |                    |
| POST   | `songs/{song_id}/rate`                               | `?action=rate&filter={song_id}`                                                  |                    |
| POST   | `songs/{song_id}/record-play`                        | `?action=record_play&filter={song_id}`                                           |                    |
| POST   | `songs/{song_id}/share`                              | `?action=share_create&filter={song_id}`                                                 |                    |
| GET    | `songs/{song_id}/similar`                            | `?action=get_similar&filter={song_id}&type=song`                                 |                    |
| GET    | `songs/{song_id}/stream`                             | `?action=stream&filter={song_id}&type=song`                                      |                    |
| GET    | `songs/{song_id}/tags`                               | `?action=song_tags&filter={song_id}`                                             |                    |
| POST   | `songs/{song_id}/update-tags`                        | `?action=update_from_tags&filter={song_id}&type=song`                            |                    |
| GET    | `songs/{song_id}`                                    | `?action=song&filter={song_id}`                                                  |                    |
| DELETE | `songs/{song_id}`                                    | `?action=song_delete&filter={song_id}`                                           |                    |
| GET    | `songs/deleted`                                      | `?action=deleted_songs`                                                          |                    |
| GET    | `songs/lookup/url-to-song`                           | `?action=url_to_song`                                                            |                    |
| GET    | `songs/playlist-generate`                            | `?action=playlist_generate`                                                      |                    |
| GET    | `songs/search`                                       | `?action=search&type=song`                                                       | `advanced_search`  |
| GET    | `songs/stats`                                        | `?action=stats&type=song`                                                        |                    |
| GET    | `songs`                                              | `?action=songs`                                                                  | `search_songs`     |
| GET    | `system-preferences/{preference_name}`               | `?action=system_preference&filter={preference_name}`                             |                    |
| GET    | `system-preferences`                                 | `?action=system_preferences`                                                     |                    |
| GET    | `update`                                             | `?action=system_update`                                                          |                    |
| POST   | `users/{user_id}/follow`                             | `?action=toggle_follow&filter={user_id}`                                         |                    |
| GET    | `users/{user_id}/followers`                          | `?action=followers&filter={user_id}`                                             |                    |
| GET    | `users/{user_id}/following`                          | `?action=following&filter={user_id}`                                             |                    |
| GET    | `users/{user_id}/timeline`                           | `?action=timeline&filter={user_id}`                                              |                    |
| GET    | `users/{user_id}`                                    | `?action=user&filter={user_id}`                                                  |                    |
| PATCH  | `users/{user_id}`                                    | `?action=user_edit&filter={user_id}`                                             |                    |
| DELETE | `users/{user_id}`                                    | `?action=user_delete&filter={user_id}`                                           |                    |
| GET    | `users/search`                                       | `?action=search&type=user`                                                       | `advanced_search`  |
| GET    | `users`                                              | `?action=users`                                                                  |                    |
| PUT    | `users`                                              | `?action=user_create`                                                            |                    |
| GET    | `videos/{video_id}/bookmark`                         | `?action=bookmark&filter={video_id}&type=video`                                  | `get_bookmark`     |
| PATCH  | `videos/{video_id}/bookmark`                         | `?action=bookmark_edit&filter={video_id}&type=video`                             |                    |
| DELETE | `videos/{video_id}/bookmark`                         | `?action=bookmark_delete&filter={video_id}&type=video`                           |                    |
| PUT    | `videos/{video_id}/bookmark`                         | `?action=bookmark_create&filter={video_id}&type=video`                           |                    |
| POST   | `videos/{video_id}/flag`                             | `?action=flag&filter={video_id}&type=video`                                      |                    |
| POST   | `videos/{video_id}/localplay`                        | `?action=localplay&command=add&filter={video_id}&type=video`                     |                    |
| POST   | `videos/{video_id}/playback`                         | `?action=player&filter={video_id}&type=video`                                    |                    |
| POST   | `videos/{video_id}/rate`                             | `?action=rate&filter={video_id}&type=video`                                      |                    |
| POST   | `videos/{video_id}/share`                            | `?action=share_create&filter={video_id}&type=video`                                     |                    |
| GET    | `videos/{video_id}`                                  | `?action=video&filter={video_id}`                                                |                    |
| GET    | `videos/deleted`                                     | `?action=deleted_videos`                                                         |                    |
| GET    | `videos/search`                                      | `?action=search&type=video`                                                      | `advanced_search`  |
| GET    | `videos/stats`                                       | `?action=stats&type=video`                                                       |                    |
| GET    | `videos`                                             | `?action=videos`                                                                 |                    |
