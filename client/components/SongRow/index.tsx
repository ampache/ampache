import React, { memo } from 'react';
import SVG from 'react-inlinesvg';
import { useGetSong } from '~logic/Song';
import { Link } from 'react-router-dom';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';
import { useMusicStore } from '~store';
import { useQueryClient } from 'react-query';
import ReactLoading from 'react-loading';
import { Menu, MenuItem } from '@material-ui/core';
import { addToPlaylist, removeFromPlaylistWithSongID } from '~logic/Playlist';
import PlaylistSelector from '~Modal/types/PlaylistSelector';
import { Modal } from 'react-async-popup';
import { toast } from 'react-toastify';
import shallow from '~node_modules/zustand/shallow';

interface SongRowProps {
    songId: string;
    trackNumber?: string;
    inPlaylistID?: string;
    showArtist?: boolean;
    showAlbum?: boolean;
    isCurrentlyPlaying?: boolean;
    startPlaying: (songId: string) => void;
    className?: string;
}
const contextMenuDefaultState = {
    mouseX: null,
    mouseY: null
};

const handleRemoveFromPlaylist = async (playlistID: string, songID: string) => {
    try {
        await removeFromPlaylistWithSongID(playlistID, songID);
        toast.success('Removed song from playlist');
    } catch (e) {
        toast.error('😞 Something went wrong removing from playlist.');
    }
};

const handleAddToPlaylist = async (songID: string) => {
    const { show } = await Modal.new({
        title: 'Add To Playlist',
        content: <PlaylistSelector />,
        footer: null
    });
    const playlistID = await show();

    if (playlistID) {
        try {
            await addToPlaylist(playlistID, songID);
            toast.success('Added song to playlist');
        } catch (e) {
            toast.error('😞 Something went wrong adding to playlist.');
        }
    }
};

const SongRow: React.FC<SongRowProps> = memo(
    ({
        className,
        trackNumber,
        showAlbum,
        showArtist,
        songId,
        inPlaylistID,
        isCurrentlyPlaying,
        startPlaying
    }: SongRowProps) => {
        const queryClient = useQueryClient();
        const { addToQueue } = useMusicStore(
            (state) => ({
                addToQueue: state.addToQueue
            }),
            shallow
        );

        const { data: song, refetch: fetchSong, isFetching } = useGetSong(
            songId,
            {
                enabled: false
            }
        );

        const [contextMenuState, setContextMenuState] = React.useState(
            contextMenuDefaultState
        );

        const handleContext = (event: React.MouseEvent) => {
            event.preventDefault();
            setContextMenuState({
                mouseX: event.clientX - 2,
                mouseY: event.clientY - 4
            });
        };

        const handleContextClose = () => {
            setContextMenuState(contextMenuDefaultState);
        };

        const formatLabel = (s) => [
            (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s
            //https://stackoverflow.com/a/37770048
        ];

        if (!song) {
            if (!isFetching) fetchSong();
            return (
                <li
                    className={`${style.songRow} card-clear ${className} ${style.songLoading} `}
                >
                    <ReactLoading type={'bubbles'} />
                </li>
            );
        }
        return (
            <>
                <li
                    className={
                        isCurrentlyPlaying
                            ? `nowPlaying ${style.songRow} card-clear ${className}`
                            : `${style.songRow} card-clear ${className} `
                    }
                    onContextMenu={(e) => handleContext(e)}
                    onClick={() => {
                        console.log('START', song);
                        startPlaying(song.id);
                    }}
                    tabIndex={1}
                >
                    <div className={style.songDetails}>
                        <div className={style.trackNumber}>
                            {trackNumber ?? song.track}
                        </div>
                        <div className={`card-title ${style.title}`}>
                            {song.title}
                        </div>
                        {showArtist && (
                            <div
                                className={style.artistContainer}
                                onClick={(e) => e.stopPropagation()}
                            >
                                <Link
                                    className={style.artist}
                                    to={`/artist/${song.artist.id}`}
                                >
                                    {song.artist.name}
                                </Link>
                            </div>
                        )}
                        {showAlbum && (
                            <div
                                className={style.albumContainer}
                                onClick={(e) => e.stopPropagation()}
                            >
                                <Link
                                    className={style.album}
                                    to={`/album/${song.album.id}`}
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {song.album.name}
                                </Link>
                            </div>
                        )}
                        <div className={style.time}>
                            {formatLabel(song.time)}
                        </div>
                    </div>

                    <div className={style.songActions}>
                        <div
                            className={style.options}
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                handleContext(e);
                            }}
                        >
                            <SVG
                                className='icon icon-button-smallest'
                                src={require('~images/icons/svg/more-options-hori.svg')}
                                title='More options'
                                role='button'
                            />
                        </div>

                        <div className={style.rating}>
                            <SimpleRating
                                value={song.rating}
                                fav={song.flag}
                                itemID={song.id}
                                type='song'
                            />
                        </div>

                        <div className={style.remove}>
                            <SVG
                                className='icon icon-button-smallest'
                                src={require('~images/icons/svg/cross.svg')}
                                title='Delete'
                                description='Delete this song'
                                role='button'
                            />
                        </div>
                    </div>
                </li>
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
                            addToQueue(songId, true);
                        }}
                    >
                        Play Next
                    </MenuItem>
                    <MenuItem
                        onClick={() => {
                            handleContextClose();
                            addToQueue(songId, false);
                        }}
                    >
                        Add to Queue
                    </MenuItem>
                    <MenuItem onClick={handleContextClose}>
                        <Link to={`/artist/${song.artist.id}`}>
                            Go to Artist
                        </Link>
                    </MenuItem>
                    <MenuItem onClick={handleContextClose}>
                        <Link to={`/album/${song.album.id}`}>Go to Album</Link>
                    </MenuItem>
                    {inPlaylistID && (
                        <MenuItem
                            onClick={async () => {
                                handleContextClose();
                                await handleRemoveFromPlaylist(
                                    inPlaylistID,
                                    songId
                                );
                                queryClient.invalidateQueries([
                                    'playlistSongs',
                                    inPlaylistID
                                ]);
                            }}
                        >
                            Remove From Playlist
                        </MenuItem>
                    )}
                    {!inPlaylistID && (
                        <MenuItem
                            onClick={() => {
                                handleContextClose();
                                handleAddToPlaylist(songId);
                            }}
                        >
                            Add to Playlist
                        </MenuItem>
                    )}
                </Menu>
            </>
        );
    },
    (prevProps, nextProps) => {
        return prevProps.isCurrentlyPlaying === nextProps.isCurrentlyPlaying;
    }
);

export { SongRow };
