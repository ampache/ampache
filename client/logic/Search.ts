import { Song } from './Song';
import { Album } from './Album';
import { Artist } from '~logic/Artist';
import { ampacheClient } from '~main';

const searchSongs = (searchQuery: string, limit = 100) => {
    return ampacheClient
        .get(``, {
            params: {
                action: 'search_songs',
                filter: searchQuery,
                limit,
                version: 400001
            }
        })
        .then((res) => res.data.song as Song[]);
};

//Finds albums by matching either artist name or album name.
const searchAlbums = (searchQuery: string, limit = 100) => {
    return ampacheClient
        .get(``, {
            params: {
                action: 'advanced_search',
                rule_1: 'title',
                rule_1_operator: '0',
                rule_1_input: searchQuery,
                rule_2: 'artist',
                rule_2_operator: '0',
                rule_2_input: searchQuery,
                type: 'album',
                operator: 'or',
                limit,
                version: 400001
            }
        })
        .then((res) => res.data.album as Album[]);
};

//Finds artist by matching artist name. TODO: Need a way to match by album/song name
const searchArtists = (searchQuery: string, limit = 100) => {
    return ampacheClient
        .get(``, {
            params: {
                action: 'advanced_search',
                rule_1: 'title',
                rule_1_operator: '0',
                rule_1_input: searchQuery,
                type: 'artist',
                operator: 'or',
                limit,
                version: 400001
            }
        })
        .then((res) => res.data.artist as Artist[]);
};

export { searchSongs, searchAlbums, searchArtists };
