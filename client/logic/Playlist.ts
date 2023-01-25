import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';
import { Song } from './Song';
import { ampacheClient } from '~main';
import { OptionType } from '~types';
import { useQuery, useQueryClient } from 'react-query';

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

export const getPlaylists = () => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlists',
                version: '6.0.0'
            }
        })
        .then((res) => res.data.playlist as Playlist[]);
};

export const getPlaylistSongs = (playlistID: string) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlist_songs',
                filter: playlistID,
                version: '6.0.0'
            }
        })
        .then((response) => response.data.song as Song[]);
};

export const useGetPlaylistSongs = (
    playlistID: string,
    options?: OptionType<Song[]>
) => {
    const queryClient = useQueryClient();
    return useQuery<Song[], Error | AmpacheError>(
        ['playlistSongs', playlistID],
        () => getPlaylistSongs(playlistID),
        {
            onSuccess: (songs) => {
                songs.map((song) => {
                    queryClient.setQueryData(['song', song.id], song);
                });
            },
            ...options
        }
    );
};

export const addToPlaylist = (playlistID: string, songID: string) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlist_add_song',
                filter: playlistID,
                song: songID,
                version: '6.0.0'
            }
        })
        .then(() => true);
};

export const removeFromPlaylistWithSongID = (
    playlistID: string,
    songID: string
) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'playlist_remove_song',
                filter: playlistID,
                song: songID,
                version: '6.0.0'
            }
        })
        .then(() => true);
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
