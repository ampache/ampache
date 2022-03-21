import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import flagItem from '~logic/Methods/Flag';
import updateArt from '~logic/Methods/Update_Art';
import { useQuery, UseQueryOptions } from 'react-query';
import { ampacheClient } from '~main';

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

const getAlbums = (includeSongs = false) => {
    const getUrl = `/server/json.server.php?action=albums&version=400001`;
    return ampacheClient
        .get(getUrl, { params: { include: [includeSongs ? 'songs' : ''] } })
        .then((response) => response.data.album as Album[]);
};

export const useGetAlbums = ({
    includeSongs = false,
    options
}: {
    includeSongs?: boolean;
    options?: Omit<
        UseQueryOptions<Album[], Error, Album[], 'albums'>,
        'queryKey' | 'queryFn'
    >;
} = {}) => {
    return useQuery<Album[], Error | AmpacheError>(
        'albums',
        () => getAlbums(includeSongs),
        options
    );
};

const getAlbum = (albumID: string, authKey: AuthKey, includeSongs = false) => {
    let includeString = '';
    if (includeSongs) {
        includeString += '&include[]=songs';
    }
    const getURL = `${process.env.ServerURL}/server/json.server.php?action=album&filter=${albumID}${includeString}&auth=${authKey}&version=400001`;
    return axios.get(getURL).then((response) => {
        const JSONData = response.data;
        if (!JSONData) {
            throw new Error('Server Error');
        }
        if (JSONData.error) {
            throw new AmpacheError(JSONData.error);
        }
        return JSONData as Album;
    });
};

const flagAlbum = (albumID: string, favorite: boolean, authKey: AuthKey) => {
    return flagItem('album', albumID, favorite, authKey);
};

const updateAlbumArt = (ID: string, overwrite: boolean, authKey: AuthKey) => {
    return updateArt('album', ID, overwrite, authKey);
};

export {
    getRandomAlbums,
    Album,
    getAlbum,
    getAlbumSongs,
    flagAlbum,
    updateAlbumArt
};
