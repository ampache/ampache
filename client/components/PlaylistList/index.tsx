import React, { useContext } from 'react';
import SVG from 'react-inlinesvg';
import {
    createPlaylist,
    deletePlaylist,
    getPlaylistSongs,
    Playlist,
    renamePlaylist,
    useGetPlaylists
} from '~logic/Playlist';
import PlaylistItem from '~components/PlaylistItem';
import { AuthKey } from '~logic/Auth';
import { Modal } from 'react-async-popup';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import InputModal from '~Modal/types/InputModal';
import { Menu, MenuItem } from '@mui/material';
import { MusicContext } from '~Contexts/MusicContext';

import style from './index.styl';
import { useQueryClient } from 'react-query';

interface PlaylistListProps {
    authKey?: AuthKey;
}

const contextMenuDefaultState = {
    mouseX: null,
    mouseY: null,
    playlistID: null
};

const PlaylistList: React.FC<PlaylistListProps> = (props) => {
    const musicContext = useContext(MusicContext);
    const queryClient = useQueryClient();

    const [contextMenuState, setContextMenuState] = React.useState(
        contextMenuDefaultState
    );

    const { data: playlists, error } = useGetPlaylists();

    const handleDeletePlaylist = (playlistID: string) => {
        deletePlaylist(playlistID)
            .then(() => {
                toast.success('Deleted Playlist.');
                queryClient.setQueryData(
                    'playlists',
                    (oldPlaylists: Playlist[]) =>
                        oldPlaylists.filter((p) => p.id !== playlistID)
                );
            })
            .catch((err) => {
                toast.error(
                    '😞 Something went wrong trying to delete playlist.'
                );
                console.error(err);
            });
    };

    const handleNewPlaylist = async () => {
        const { show } = await Modal.new({
            title: 'Create new playlist',
            content: (
                <InputModal
                    inputLabel='Name'
                    inputPlaceholder='Rock & Roll...'
                    submitButtonText='Create'
                />
            ),
            footer: null
        });
        const result = await show();

        if (result) {
            createPlaylist(result, props.authKey)
                .then((newPlaylist) => {
                    toast.success('Created Playlist.');
                    queryClient.setQueryData(
                        'playlists',
                        (oldPlaylists: Playlist[]) => [
                            newPlaylist,
                            ...oldPlaylists
                        ]
                    );
                })
                .catch((err) => {
                    toast.error(
                        '😞 Something went wrong creating new playlist.'
                    );
                    console.error(err);
                });
        }
    };

    const handleEditPlaylist = async (
        playlistID: string,
        playlistCurrentName: string
    ) => {
        const { show } = await Modal.new({
            title: `Editing ${playlistCurrentName}`,
            content: (
                <InputModal
                    inputLabel='New Playlist Name'
                    inputInitialValue={playlistCurrentName}
                    inputPlaceholder={playlistCurrentName}
                    submitButtonText='Save'
                />
            ),
            footer: null
        });
        const newName = await show();
        if (newName) {
            try {
                await renamePlaylist(playlistID, newName, props.authKey);

                queryClient.setQueryData(
                    'playlists',
                    (oldPlaylists: Playlist[]) =>
                        oldPlaylists.map((p) =>
                            p.id === playlistID ? { ...p, name: newName } : p
                        )
                );

                toast.success('Edited Playlist.');
            } catch (e) {
                toast.error('😞 Something went wrong editing the playlist.');
            }
        }
    };

    const handleContextClose = () => {
        setContextMenuState(contextMenuDefaultState);
    };
    const handleContext = (event: React.MouseEvent, playlistID: string) => {
        event.preventDefault();
        setContextMenuState({
            mouseX: event.clientX - 2,
            mouseY: event.clientY - 4,
            playlistID
        });
    };

    const startPlayingPlaylist = async (playlistID: string) => {
        const songs = await getPlaylistSongs(playlistID);
        const songIds = songs.map((song) => song.id);
        musicContext.startPlayingWithNewQueue(songIds);
    };

    if (error) {
        return (
            <div className='playlistList'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!playlists) {
        return (
            <div className='playlistList'>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    //There is an issue where if you right click an item the context menu blocks everything behind it
    //This seems to go away if I have make 'playlistList' div be the one listening for onContextMenu
    //But then that holds the issue of I can't tell what item we are dealing with. For now let's leave this be.
    return (
        <div className='playlistList'>
            <SVG
                className='icon icon-button'
                src={require('~images/icons/svg/plus.svg')}
                title='Add to playlist'
                role='button'
                onClick={handleNewPlaylist}
            />
            <ul className={`striped-list ${style.playlistListContainer}`}>
                {playlists.map((playlist: Playlist) => {
                    return (
                        <PlaylistItem
                            playlist={playlist}
                            startPlaying={startPlayingPlaylist}
                            showContext={handleContext}
                            key={playlist.id}
                        />
                    );
                })}
            </ul>
            <Menu
                open={contextMenuState.mouseY !== null}
                onClose={handleContextClose}
                anchorReference='anchorPosition'
                anchorPosition={
                    contextMenuState.mouseY !== null &&
                    contextMenuState.mouseX !== null
                        ? {
                              top: contextMenuState.mouseY,
                              left: contextMenuState.mouseX
                          }
                        : undefined
                }
            >
                <MenuItem
                    onClick={() => {
                        handleContextClose();
                        startPlayingPlaylist(contextMenuState.playlistID);
                    }}
                >
                    Play Playlist
                </MenuItem>
                <MenuItem
                    onClick={() => {
                        handleContextClose();
                        handleEditPlaylist(
                            contextMenuState.playlistID,
                            playlists.filter(
                                (p) => p.id === contextMenuState.playlistID
                            )[0].name
                        );
                    }}
                >
                    Edit Playlist
                </MenuItem>
                <MenuItem
                    onClick={() => {
                        handleContextClose();
                        handleDeletePlaylist(contextMenuState.playlistID);
                    }}
                >
                    Delete Playlist
                </MenuItem>
            </Menu>
        </div>
    );
};

export default PlaylistList;
