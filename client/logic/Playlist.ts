import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';
import { Song } from './Song';

export type Playlist = {
    id: number;
    name: string;
    owner: string;
    items: number;
    type: string;
};

export const getPlaylists = async (authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlists&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData as Playlist[];
        });
};

export const getPlaylistSongs = async (
    playlistID: number,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_songs&filter=${playlistID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData as Song[];
        });
};

export const addToPlaylist = async (
    playlistID: number,
    songID: number,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_add_song&filter=${playlistID}&song=${songID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return true;
        });
};

export const removeFromPlaylistWithSongID = async (
    playlistID: number,
    songID: number,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_remove_song&filter=${playlistID}&song=${songID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return true;
        });
};
