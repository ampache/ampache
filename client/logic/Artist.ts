import axios from 'axios';
import { AuthKey } from '~logic/Auth';
import AmpacheError from '~logic/AmpacheError';
import { Album } from '~logic/Album';
import { Song } from '~logic/Song';
import updateArt from '~logic/Methods/Update_Art';
import { useQuery, useQueryClient } from 'react-query';
import { ampacheClient } from '~main';
import { OptionType } from '~types';

export type Artist = {
    id: string;
    name: string;
    prefix: string | null;
    basename: string;
    albums: Album[];
    albumcount: number;
    songs: Song[];
    songcount: number;
    genre: [
        {
            id: string;
            name: string;
        }
    ];
    art: string;
    flag: boolean;
    rating: number;
    averagerating: number;
    mbid: string;
    summary: string;
    time: number;
    yearformed: number;
    placeformed: string;
};

export const updateArtistInfo = (artistID: string, authKey: AuthKey) => {
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

export const useGetArtists = (includeAlbums = false, includeSongs = false) => {
    const queryClient = useQueryClient();
    return useQuery<Artist[], Error | AmpacheError>(
        ['artists', includeAlbums, includeSongs],
        () =>
            ampacheClient
                .get('', {
                    params: {
                        action: 'artists',
                        version: '6.0.0',
                        include: [
                            includeSongs && 'songs',
                            includeAlbums && 'albums'
                        ]
                    }
                })
                .then((response) => {
                    response.data.artist.map((artist) => {
                        queryClient.setQueryData(
                            ['artist', artist.id, includeSongs, includeAlbums],
                            artist
                        );
                    });
                    return response.data.artist;
                })
    );
};

const getArtist = (
    albumID: string,
    includeSongs = false,
    includeAlbums = false
) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'artist',
                version: '6.0.0',
                filter: albumID,
                include: [includeSongs && 'songs', includeAlbums && 'albums']
            }
        })
        .then((response) => response.data as Artist);
};

export const useGetArtist = ({
    artistID,
    includeAlbums = false,
    includeSongs = false,
    options
}: {
    artistID: string;
    includeAlbums?: boolean;
    includeSongs?: boolean;
    options?: OptionType<Artist>;
}) => {
    return useQuery<Artist, Error | AmpacheError>(
        ['artist', artistID, includeSongs, includeAlbums],
        () => getArtist(artistID, includeSongs, includeAlbums),
        options
    );
};

export const updateArtistArt = (ID: string, overwrite: boolean) => {
    return updateArt('artist', ID, overwrite);
};
