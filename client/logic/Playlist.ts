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
    preciserating: number;
    rating: number;
    averagerating: number;
    flag: boolean;
};

export const getPlaylists = (authKey: AuthKey) => {
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

export const getPlaylistSongs = (playlistID: number, authKey: AuthKey) => {
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

export const addToPlaylist = (
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

export const removeFromPlaylistWithSongID = (
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

export const createPlaylist = (
    name: string,
    authKey: AuthKey,
    type = 'public'
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_create&name=${name}&type=${type}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData[0] as Playlist;
        });
};

export const renamePlaylist = (
    playlistID: number,
    newName: string,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_edit&filter=${playlistID}&name=${newName}&auth=${authKey}&version=400001`
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

export const deletePlaylist = (playlistID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_delete&filter=${playlistID}&auth=${authKey}&version=400001`
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
