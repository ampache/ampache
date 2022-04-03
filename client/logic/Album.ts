import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import updateArt from '~logic/Methods/Update_Art';
import { useQuery } from 'react-query';
import { ampacheClient } from '~main';
import { OptionType } from '~types';

type Album = {
    id: string;
    name: string;
    artist: {
        id: string;
        name: string;
    };
    time: number;
    year: number;
    tracks: Song[];
    songcount: number;
    disk: number;
    genre: [
        {
            id: string;
            name: string;
        }
    ];
    art: string;
    flag: boolean;
    preciserating: number;
    rating: number;
    averagerating: number;
    mbid: string;
};

const getRandomAlbums = (username: string, count: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=stats&username=${username}&type=album&filter=random&limit=${count}&auth=${authKey}&version=500001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData.album as Album[];
        });
};

const getAlbumSongs = (albumID: string, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=album_songs&filter=${albumID}&auth=${authKey}&version=400001`
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

type AlbumsInput = {
    filter?: string;
    exact?: boolean;
    add?: any;
    update?: any;
    offset?: number;
    limit?: number;
    includeSongs?: boolean;
    options?: OptionType<Album[]>;
};

export const useGetAlbums = (input: AlbumsInput = {}) => {
    const {
        add,
        exact,
        limit,
        offset,
        update,
        filter,
        includeSongs = false,
        options
    } = input;
    return useQuery<Album[], Error | AmpacheError>(
        ['albums', input],
        () =>
            ampacheClient
                .get('', {
                    params: {
                        action: 'albums',
                        version: 400001,
                        include: [includeSongs ? 'songs' : ''],
                        limit,
                        offset
                    }
                })
                .then((response) => response.data.album as Album[]),
        options
    );
};

const getAlbum = (albumID: string, includeSongs = false) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'album',
                version: 400001,
                filter: albumID,
                include: [includeSongs ? 'songs' : '']
            }
        })
        .then((response) => response.data as Album);
};

export const useGetAlbum = ({
    albumID,
    includeSongs = false,
    options
}: {
    albumID: string;
    includeSongs?: boolean;
    options?: OptionType<Album>;
}) => {
    return useQuery<Album, Error | AmpacheError>(
        ['album', albumID, includeSongs],
        () => getAlbum(albumID, includeSongs),
        options
    );
};

const flagAlbum = (albumID: string, favorite: boolean, authKey: AuthKey) => {
    // return flagItem('album', albumID, favorite, authKey);
};

const updateAlbumArt = (ID: string, overwrite: boolean, authKey: AuthKey) => {
    return updateArt('album', ID, overwrite, authKey);
};

export { getRandomAlbums, Album, getAlbumSongs, flagAlbum, updateAlbumArt };
