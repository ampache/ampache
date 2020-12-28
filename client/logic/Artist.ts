import axios from 'axios';
import { AuthKey } from '~logic/Auth';
import AmpacheError from '~logic/AmpacheError';
import { Album } from '~logic/Album';
import { Song } from '~logic/Song';

export type Artist = {
    id: number;
    name: string;
    tags: [];
    albums?: Album[];
    songs?: Song[];
    art: string;
    preciserating: number;
    rating: number;
    averagerating: number;
    flag: boolean;
    mbid: string;
    summary: string;
    yearformed: number;
    placeformed: string;
};

export const updateArtistInfo = (artistID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=update_artist_info&id=${artistID}&auth=${authKey}&version=400001`
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

export const getAlbumsFromArtist = (albumID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=artist_albums&filter=${albumID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData as Album[];
        });
};

export const getArtists = (
    authKey: AuthKey,
    includeAlbums = false,
    includeSongs = false
) => {
    let includeString = '';
    if (includeAlbums) {
        includeString += '&include[]=albums';
    }
    if (includeSongs) {
        includeString += '&include[]=songs';
    }
    const getUrl = `${process.env.ServerURL}/server/json.server.php?action=artists&auth=${authKey}${includeString}&version=400001`;
    return axios.get(getUrl).then((response) => {
        const JSONData = response.data;
        if (!JSONData) {
            throw new Error('Server Error');
        }
        if (JSONData.error) {
            throw new AmpacheError(JSONData.error);
        }
        return JSONData.artist as Artist[];
    });
};

export const getArtist = (
    artistID: number,
    authKey: AuthKey,
    includeAlbums = false,
    includeSongs = false
) => {
    let includeString = '';
    if (includeAlbums) {
        includeString += '&include[]=albums';
    }
    if (includeSongs) {
        includeString += '&include[]=songs';
    }
    const getUrl = `${process.env.ServerURL}/server/json.server.php?action=artist&filter=${artistID}${includeString}&auth=${authKey}&version=400001`;

    return axios.get(getUrl).then((response) => {
        const JSONData = response.data;
        if (!JSONData) {
            throw new Error('Server Error');
        }
        if (JSONData.error) {
            throw new AmpacheError(JSONData.error);
        }
        return JSONData.artist[0] as Artist;
    });
};
