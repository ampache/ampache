import React, { useContext } from 'react';
import SVG from 'react-inlinesvg';
import {
    getPlaylistSongs,
    useCreatePlaylist,
    useDeletePlaylist,
    useGetPlaylists,
    useRenamePlaylist
} from '~logic/Playlist';
import PlaylistItem from '~components/PlaylistItem';
import { Modal } from 'react-async-popup';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import InputModal from '~Modal/types/InputModal';
import { Menu, MenuItem } from '@mui/material';
import { MusicContext } from '~Contexts/MusicContext';

import * as style from './index.styl';

const contextMenuDefaultState = {
    mouseX: null,
    mouseY: null,
    playlistID: null
};

const PlaylistList = () => {
    const musicContext = useContext(MusicContext);
    const createPlaylist = useCreatePlaylist();
    const renamePlaylist = useRenamePlaylist();
    const deletePlaylist = useDeletePlaylist();

    const [contextMenuState, setContextMenuState] = React.useState(
        contextMenuDefaultState
    );

    const { data: playlists, error } = useGetPlaylists();

    const handleDeletePlaylist = (playlistID: string) => {
        deletePlaylist.mutate(
            { playlistID },
            {
                onSuccess: () => toast.success('Deleted Playlist.'),
                onError: () => {
                    toast.error(
                        '😞 Something went wrong trying to delete playlist.'
                    );
                }
            }
        );
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
        const result: string = await show();

        if (result) {
            createPlaylist.mutate(
                { name: result },
                {
                    onSuccess: () => toast.success('Created Playlist.'),
                    onError: () => {
                        toast.error(
                            '😞 Something went wrong creating new playlist.'
                        );
                    }
                }
            );
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
            renamePlaylist.mutate(
                { playlistID, newName },
                {
                    onSuccess: () => toast.success('Edited Playlist.'),
                    onError: () =>
                        toast.error(
                            '😞 Something went wrong editing the playlist.'
                        )
                }
            );
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
                {playlists.map(({ id }) => {
                    return (
                        <PlaylistItem
                            playlistId={id}
                            startPlaying={startPlayingPlaylist}
                            showContext={handleContext}
                            key={id}
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
