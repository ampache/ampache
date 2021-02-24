import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';
import { Song } from './Song';
import flagItem from '~logic/Methods/Flag';

export type Playlist = {
    id: string;
    name: string;
    owner: string;
    items: number;
    type: string;
    art: string;
    flag: boolean;
    preciserating: number;
    rating: number;
    averagerating: number;
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
            return JSONData.playlist as Playlist[];
        });
};

export const getPlaylistSongs = (playlistID: string, authKey: AuthKey) => {
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
            return JSONData.song as Song[];
        });
};

export const addToPlaylist = (
    playlistID: string,
    songID: string,
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
    playlistID: string,
    songID: string,
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
    playlistID: string,
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

export const deletePlaylist = (playlistID: string, authKey: AuthKey) => {
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

export const flagPlaylist = (
    playlistID: string,
    favorite: boolean,
    authKey: AuthKey
) => {
    return flagItem('playlist', playlistID, favorite, authKey);
};
