import AmpacheError from './AmpacheError';
import { Song } from './Song';
import { ampacheClient } from '~main';
import { OptionType } from '~types';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

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

export const useGetPlaylists = (options?: OptionType<Playlist[]>) => {
    const queryClient = useQueryClient();

    return useQuery<Playlist[], Error | AmpacheError>(
        ['playlists'],
        () => getPlaylists(),
        {
            onSuccess: (playlists) => {
                playlists.map((playlist) => {
                    queryClient.setQueryData(
                        ['playlist', playlist.id],
                        playlist
                    );
                });
            },
            ...options
        }
    );
};

const getPlaylist = (playlistId: string) => {
    return ampacheClient
        .get<Playlist>('', {
            params: {
                action: 'playlist',
                filter: playlistId,
                version: '6.0.0'
            }
        })
        .then((res) => res.data);
};

export const useGetPlaylist = (
    playlistId: string,
    options?: OptionType<Playlist>
) => {
    return useQuery<Playlist, Error | AmpacheError>(
        ['playlist', playlistId],
        () => getPlaylist(playlistId),
        options
    );
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

const createPlaylistFn = ({
    name,
    type = 'public'
}: {
    name: string;
    type?: string;
}) =>
    ampacheClient
        .get<Playlist>('', {
            params: {
                action: 'playlist_create',
                name,
                type,
                version: '6.0.0'
            }
        })
        .then((res) => res.data);

export const useCreatePlaylist = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: createPlaylistFn,
        onSuccess: (newPlaylist) => {
            queryClient.setQueryData(
                ['playlists'],
                (oldPlaylists: Playlist[]) => [newPlaylist, ...oldPlaylists]
            );
        }
    });
};

const renamePlaylistFn = ({
    newName,
    playlistID
}: {
    newName: string;
    playlistID: string;
}) =>
    ampacheClient
        .get('', {
            params: {
                action: 'playlist_edit',
                name: newName,
                filter: playlistID,
                version: '6.0.0'
            }
        })
        .then((res) => res.data);

export const useRenamePlaylist = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: renamePlaylistFn,
        onSuccess: (_, { newName, playlistID }) => {
            queryClient.setQueryData(
                ['playlists'],
                (oldPlaylists: Playlist[]) =>
                    oldPlaylists.map((p) =>
                        p.id === playlistID ? { ...p, name: newName } : p
                    )
            );
        }
    });
};

const deletePlaylistFn = ({ playlistID }: { playlistID: string }) =>
    ampacheClient
        .get('', {
            params: {
                action: 'playlist_delete',
                filter: playlistID,
                version: '6.0.0'
            }
        })
        .then((res) => res.data);

export const useDeletePlaylist = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: deletePlaylistFn,
        onSuccess: (_, { playlistID }) => {
            queryClient.setQueryData(
                ['playlists'],
                (oldPlaylists: Playlist[]) =>
                    oldPlaylists.filter((p) => p.id !== playlistID)
            );
        }
    });
};
