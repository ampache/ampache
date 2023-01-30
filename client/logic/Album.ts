import { Song } from './Song';
import AmpacheError from './AmpacheError';
import updateArt from '~logic/Methods/Update_Art';
import {
    useInfiniteQuery,
    useQuery,
    useQueryClient
} from '@tanstack/react-query';
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

const getRandomAlbums = (count: number) => {
    return ampacheClient
        .get('', {
            params: {
                action: 'stats',
                filter: 'random',
                type: 'album',
                limit: count,
                version: '500001'
            }
        })
        .then((response) => response.data.album as Album[]);
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

export const useInfiniteAlbums = (limit = 25) => {
    const queryClient = useQueryClient();
    return useInfiniteQuery<Album[], Error | AmpacheError>({
        queryKey: ['albums', false],
        queryFn: ({ pageParam }) => {
            return ampacheClient
                .get('', {
                    params: {
                        action: 'albums',
                        limit,
                        offset: pageParam,
                        version: '6.0.0'
                    }
                })
                .then((response) => {
                    response.data.album.map((album) => {
                        queryClient.setQueriesData(['album', album.id], album);
                    });
                    return response.data.album;
                });
        },
        getNextPageParam: (lastPage, pages) =>
            lastPage.length === limit ? pages.length * limit : undefined
    });
};

export const useGetAlbums = (input: AlbumsInput = {}) => {
    const { includeSongs = false, options } = input;
    const queryClient = useQueryClient();

    return useQuery<Album[], Error | AmpacheError>(
        ['albums', includeSongs],
        () =>
            ampacheClient
                .get('', {
                    params: {
                        action: 'albums',
                        version: '6.0.0',
                        include: [includeSongs ? 'songs' : '']
                    }
                })
                .then((response) => {
                    response.data.album.map((album) => {
                        queryClient.setQueriesData(['album', album.id], album);
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

const updateAlbumArt = (ID: string, overwrite: boolean) => {
    return updateArt('album', ID, overwrite);
};

export { getRandomAlbums, Album, getAlbumSongs, updateAlbumArt };
