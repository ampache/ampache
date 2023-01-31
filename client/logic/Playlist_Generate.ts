import { Song } from './Song';
import { ampacheClient } from '~main';

//Broken
export const generateSongsFromArtist = (artistID: string) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlist_generate',
                artist: artistID,
                version: '6.0.0'
            }
        })
        .then((res) => res.data.song as Song[]);
};

export const generateSongsFromAlbum = (albumID: string) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlist_generate',
                album: albumID,
                version: '6.0.0'
            }
        })
        .then((res) => res.data.song as Song[]);
};
