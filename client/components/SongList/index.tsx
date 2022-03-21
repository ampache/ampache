import React, { useContext, useEffect, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { Song } from '~logic/Song';
import SongRow from '~components/SongRow';
import {
    addToPlaylist,
    getPlaylistSongs,
    removeFromPlaylistWithSongID
} from '~logic/Playlist';
import { AuthKey } from '~logic/Auth';
import AmpacheError from '~logic/AmpacheError';
import { getAlbumSongs } from '~logic/Album';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { Modal } from 'react-async-popup';
import PlaylistSelector from '~Modal/types/PlaylistSelector';
import { Menu, MenuItem } from '@material-ui/core';
import { Link } from 'react-router-dom';

import style from './index.styl';
import { useStore } from '~store';

interface SongListProps {
    showArtist?: boolean;
    showAlbum?: boolean;
    inPlaylistID?: string;
    inAlbumID?: string;
    songData?: Song[];
    authKey?: AuthKey;
}

const contextMenuDefaultState = {
    mouseX: null,
    mouseY: null,
    song: null
};
const setSong = useStore.getState().startPlayingSong;

const SongList: React.FC<SongListProps> = (props) => {
    const musicContext = useContext(MusicContext);
    const currentPlayingSong = useStore().currentPlayingSong;

    const [songs, setSongs] = useState<Song[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const [contextMenuState, setContextMenuState] = React.useState(
        contextMenuDefaultState
    );

    useEffect(() => {
        if (props.songData) {
            setSongs(props.songData);
        }
    }, [props.songData]);

    useEffect(() => {
        if (props.inPlaylistID) {
            getPlaylistSongs(props.inPlaylistID, props.authKey)
                .then((data) => {
                    setSongs(data);
                })
                .catch((error) => {
                    toast.error(
                        '😞 Something went wrong getting playlist songs.'
                    );
                    setError(error);
                });
        } else if (props.inAlbumID) {
            getAlbumSongs(props.inAlbumID, props.authKey)
                .then((songs) => {
                    setSongs(songs);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong getting album songs.');
                    setError(error);
                });
        } else if (!props.songData) {
            throw new Error(
                'Supply either inPlaylistID, inAlbumID or songData.'
            );
        }
    }, [props.authKey, props.inAlbumID, props.inPlaylistID, props.songData]);

    const handleRemoveFromPlaylist = (songID: string) => {
        removeFromPlaylistWithSongID(
            props.inPlaylistID,
            songID,
            props.authKey
        ).then(() => {
            let newSongs = [...songs];
            newSongs = newSongs.filter((song) => song.id != songID);
            setSongs(newSongs);
        });
    };

    const handleAddToPlaylist = (songID: string) => {
        (async () => {
            const { show } = await Modal.new({
                title: 'Add To Playlist',
                content: <PlaylistSelector authKey={props.authKey} />,
                footer: null
            });
            const playlistID = await show();

            if (playlistID) {
                try {
                    await addToPlaylist(playlistID, songID, props.authKey);
                    toast.success('Added song to playlist');
                } catch (e) {
                    toast.error('😞 Something went wrong adding to playlist.');
                }
            }
        })();
    };

    const handleStartPlaying = (song: Song) => {
        const queueIndex = songs.findIndex((o) => o.id === song.id);
        musicContext.startPlayingWithNewQueue(songs, queueIndex);
        setSong(song);
    };

    const handleContext = (event: React.MouseEvent, song: Song) => {
        event.preventDefault();
        setContextMenuState({
            mouseX: event.clientX - 2,
            mouseY: event.clientY - 4,
            song
        });
    };

    const handleContextClose = () => {
        setContextMenuState(contextMenuDefaultState);
    };

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!songs) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <>
            <ul className={'striped-list'}>
                {songs.map((song: Song) => {
                    return (
                        <SongRow
                            song={song}
                            showArtist={props.showArtist}
                            showAlbum={props.showAlbum}
                            isCurrentlyPlaying={
                                currentPlayingSong?.id === song.id
                            }
                            startPlaying={handleStartPlaying}
                            showContext={handleContext}
                            key={song.playlisttrack}
                            className={style.songRow}
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
                        musicContext.addToQueue(contextMenuState.song, true);
                    }}
                >
                    Play Next
                </MenuItem>
                <MenuItem
                    onClick={() => {
                        handleContextClose();
                        musicContext.addToQueue(contextMenuState.song, false);
                    }}
                >
                    Add to Queue
                </MenuItem>
                <MenuItem onClick={handleContextClose}>
                    <Link to={`/artist/${contextMenuState.song?.artist.id}`}>
                        Go to Artist
                    </Link>
                </MenuItem>
                <MenuItem onClick={handleContextClose}>
                    <Link to={`/album/${contextMenuState.song?.album.id}`}>
                        Go to Album
                    </Link>
                </MenuItem>
                {props.inPlaylistID && (
                    <MenuItem
                        onClick={() => {
                            handleContextClose();
                            handleRemoveFromPlaylist(contextMenuState.song?.id);
                        }}
                    >
                        Remove From Playlist
                    </MenuItem>
                )}
                {!props.inPlaylistID && (
                    <MenuItem
                        onClick={() => {
                            handleContextClose();
                            handleAddToPlaylist(contextMenuState.song?.id);
                        }}
                    >
                        Add to Playlist
                    </MenuItem>
                )}
            </Menu>
        </>
    );
};

export default SongList;
