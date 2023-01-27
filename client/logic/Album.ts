import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import updateArt from '~logic/Methods/Update_Art';
import { useQuery, useQueryClient } from 'react-query';
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

const getAlbumSongs = (albumID: string) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'album_songs',
                filter: albumID,
                version: '6.0.0'
            }
        })
        .then((response) => response.data.song as Song[]);
};

export const useGetAlbumSongs = ({
    albumID,
    options
}: {
    albumID: string;
    options?: OptionType<Song[]>;
}) => {
    const queryClient = useQueryClient();
    return useQuery<Song[], Error | AmpacheError>(
        ['albumSongs', albumID],
        () => getAlbumSongs(albumID),
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
    const queryClient = useQueryClient();

    return useQuery<Album[], Error | AmpacheError>(
        ['albums', input],
        () =>
            ampacheClient
                .get('', {
                    params: {
                        action: 'albums',
                        version: '6.0.0',
                        include: [includeSongs ? 'songs' : ''],
                        limit,
                        offset
                    }
                })
                .then((response) => {
                    response.data.album.map((album) => {
                        queryClient.setQueryData(
                            ['album', album.id, includeSongs],
                            album
                        );
                    });
                    return response.data.album as Album[];
                }),
        options
    );
};

const getAlbum = (albumID: string, includeSongs = false) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'album',
                version: '6.0.0',
                filter: albumID,
                include: [includeSongs && 'songs']
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
    const queryClient = useQueryClient();
    return useQuery<Album, Error | AmpacheError>(
        ['album', albumID, includeSongs],
        () => getAlbum(albumID, includeSongs),
        {
            onSuccess: (album) => {
                album.tracks.map((song) => {
                    queryClient.setQueryData(['song', song.id], song);
                });
            },
            ...options
        }
    );
};

const flagAlbum = (albumID: string, favorite: boolean, authKey: AuthKey) => {
    // return flagItem('album', albumID, favorite, authKey);
};

const updateAlbumArt = (ID: string, overwrite: boolean) => {
    return updateArt('album', ID, overwrite);
};

export { getRandomAlbums, Album, getAlbumSongs, flagAlbum, updateAlbumArt };
