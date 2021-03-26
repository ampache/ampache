---
title: "API 4 advanced_search"
metaTitle: "API 4 advanced_search"
metaDescription: "API documentation"
---

Advanced search is the API method to access the search rules used in the WEB UI. It can be confusing to process how the rules are generated so this has been split into it's own page.

## Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
Use operator ('and'|'or') to choose whether to join or separate each rule when searching.

* Rule arrays must contain the following:
  * rule name (e.g. rule_1['title'], rule_2['album'])
  * rule operator (e.g. rule_1_operator[0], rule_2_operator[3])
  * rule input (e.g. rule_1_input['Prodigy'], rule_2_input['Land'])

### Available search rules

Select the type of search based on the type of data you are searching for. (songs, playlists, etc)

| rule_1            | Title                   | Type              |           Valid Items            |
|-------------------|-------------------------|-------------------|:--------------------------------:|
| anywhere          | Any searchable text     | text              |               song               |
| title             | Title / Name            | text              | song,album,artist,playlist,label |
| album             | Album                   | text              |           song,artist            |
| artist            | Artist                  | text              |        song,album,artist         |
| album_artist      | Album Artist            | text              |               song               |
| composer          | Composer                | text              |               song               |
| year              | Year                    | numeric           |            song,album            |
| original_year     | Original Year           | numeric           |              album               |
| yearformed        | Year                    | numeric           |              artist              |
| placeformed       | Place                   | text              |              artist              |
| release_type      | Release Type            | text              |              album               |
| myrating          | My Rating               | numeric           |        song,album,artist         |
| rating            | Rating (Average)        | numeric           |        song,album,artist         |
| albumrating       | My Rating (Album)       | numeric           |               song               |
| artistrating      | My Rating (Artist)      | numeric           |            song,album            |
| favorite          | Favorites               | text              |        song,album,artist         |
| played_times      | # Played                | numeric           |        song,album,artist         |
| skipped_times     | # Skipped               | numeric           |               song               |
| play_skip_ratio   | Played/Skipped ratio    | numeric           |               song               |
| last_play         | My Last Play            | days              |        song,album,artist         |
| played            | Played                  | boolean           |               song               |
| myplayed          | Played by Me            | boolean           |        song,album,artist         |
| myplayedalbum     | Played by Me (Album)    | boolean           |               song               |
| myplayedartist    | Played by Me (Artist)   | boolean           |               song               |
| time              | Length (in minutes)     | numeric           |        song,album,artist         |
| tag               | Tag                     | tags              |        song,album,artist         |
| album_tag         | Album tag               | tags              |               song               |
| artist_tag        | Artist tag              | tags              |               song               |
| other_user        | Another User            | user_numeric      |        song,album,artist         |
| other_user_album  | Another User (Album)    | user_numeric      |               song               |
| other_user_artist | Another User (Artist)   | user_numeric      |               song               |
| label             | Label                   | text              |               song               |
| license           | Music License           | boolean_numeric   |               song               |
| playlist          | Playlist                | boolean_numeric   |               song               |
| smartplaylist     | Smart Playlist          | boolean_subsearch |               song               |
| playlist_name     | Playlist Name           | text              |               song               |
| comment           | Comment                 | text              |               song               |
| lyrics            | Lyrics                  | text              |               song               |
| file              | Filename                | text              |            song,video            |
| bitrate           | Bitrate                 | numeric           |               song               |
| added             | Added                   | date              |               song               |
| updated           | Updated                 | date              |               song               |
| recent_added      | Recently Added          | date              |               song               |
| recent_updated    | Recently Updated        | date              |               song               |
| catalog           | Catalog                 | boolean_numeric   |            song,album            |
| mbid              | MusicBrainz ID          | text              |        song,album,artist         |
| mbid_album        | MusicBrainz ID (Album)  | text              |               song               |
| mbid_artist       | MusicBrainz ID (Artist) | text              |               song               |
| metadata          | Metadata                | metadata (mixed)  |               song               |
| has_image         | Local Image             | boolean           |           album,artist           |
| image_height      | Image Height            | numeric           |           album,artist           |
| image_width       | Image Width             | numeric           |           album,artist           |
| username          | Username                | text              |               user               |
| category          | Category                | text              |              label               |

### Available search operators

Select your operator (integer only!) based on the type or your selected search

| rule_1_operator | Text / Tags / Metadata            | Numeric / user_numeric                       | Date   | Boolean, Numeric, Subsearch / Days |
|:---------------:|-----------------------------------|----------------------------------------------|--------|------------------------------------|
|        0        | contains                          | is greater than or equal to / has loved      | before | is true / before (x) days ago      |
|        1        | does not contain                  | is less than or equal to / has rated 5 stars | after  | is false / after (x) days ago      |
|        2        | starts with                       | equals / has rated 4 stars                   |        |                                    |
|        3        | ends with                         | does not equal / has rated 3 stars           |        |                                    |
|        4        | is                                | is greater than / has rated 2 stars          |        |                                    |
|        5        | is not                            | is less than / has rated 1 stars             |        |                                    |
|  6 (Text Only)  | sounds like                       |                                              |        |                                    |
|  7 (Text Only)  | does not sound like               |                                              |        |                                    |
|  8 (Text Only)  | matches regular expression        |                                              |        |                                    |
|  9 (Text Only)  | does not match regular expression |                                              |        |                                    |

Send the correct input based on the type of search.

| rule_1_input |
|--------------|
| text         |
| integer      |
| boolean      |

**NOTE** To search metadata you need to add a 4th rule "rule_X_subtype"
Operators for metadata are using the text/tag types **AND** numeric types in a single list as they can be ints/strings/dates.
Currently there is not a simple way to identify what metadata types you have saved. New methods will be created for this.

### Metadata operator table

| rule_1_operator | Metadata                          |
|:---------------:|-----------------------------------|
|        0        | contains                          |
|        1        | does not contain                  |
|        2        | starts with                       |
|        3        | ends with                         |
|        4        | is                                |
|        5        | is not                            |
|  6 (Text Only)  | sounds like                       |
|  7 (Text Only)  | does not sound like               |
|  8 (Text Only)  | matches regular expression        |
|  9 (Text Only)  | does not match regular expression |
|       10        | is greater than or equal to       |
|       11        | is less than or equal to          |
|       12        | is                                |
|       13        | is not                            |
|       14        | is greater than                   |
|       15        | is less than                      |

To search a mixed type like metadata you must search using 4 rules.

* Search rule 1 for band containing 'Prodigy', Search Rule 2 for bbm > 120
  * rule name (e.g. rule_1['metadata'], rule_2['metadata'])
  * rule operator (e.g. rule_1_operator[0], rule_2_operator[12])
  * rule input (e.g. rule_1_input['Prodigy'], rule_2_input['120'])
  * rule subtype (e.g. rule_1_subtype['4'], rule_2_subtype['9'])

### advanced_search parameters

@param array $input

    INPUTS
    * ampache_url = (string)
    * ampache_API = (string)
    * operator = (string) 'and'|'or' (whether to match one rule or all)
    * rules = (array) = [[rule_1,rule_1_operator,rule_1_input], [rule_2,rule_2_operator,rule_2_input], [etc]]
    * type = (string) 'song', 'album', 'artist', 'playlist', 'label', 'user', 'video'
    * random = (integer) 0|1 (random order of results; default to 0)
    * offset = (integer)
    * limit' = (integer)
