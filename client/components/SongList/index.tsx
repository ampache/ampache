import React, { useContext, useEffect, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { flagSong, Song } from '~logic/Song';
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
import { useHistory } from 'react-router-dom';
import HistoryShell from '~Modal/HistoryShell';

import style from './index.styl';

interface SongListProps {
    showArtist?: boolean;
    showAlbum?: boolean;
    inPlaylistID?: string;
    inAlbumID?: string;
    songData?: Song[];
    authKey?: AuthKey;
}
//TODO: This is doing way to much, clean me
const SongList: React.FC<SongListProps> = (props) => {
    const musicContext = useContext(MusicContext);
    const [songs, setSongs] = useState<Song[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);
    const history = useHistory();

    useEffect(() => {
        if (props.songData) {
            setSongs(props.songData);
        }
    }, []);

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

    const handleAddToPlaylist = async (songID: string) => {
        const { show } = await Modal.new({
            title: 'Add To Playlist',
            content: (
                <HistoryShell history={history}>
                    <PlaylistSelector authKey={props.authKey} />
                </HistoryShell>
            ),
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
    };

    const handleFlagSong = (songID: string, favorite: boolean) => {
        flagSong(songID, favorite, props.authKey)
            .then(() => {
                const newSongs = songs.map((song) => {
                    if (song.id === songID) {
                        song.flag = !song.flag;
                    }
                    return song;
                });
                setSongs(newSongs);
                if (favorite) {
                    return toast.success('Song added to favorites');
                }
                toast.success('Song removed from favorites');
            })
            .catch((err) => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding song to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing song from favorites.'
                    );
                }
                setError(err);
            });
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
        <ul className={'striped-list'}>
            {songs.map((song: Song) => {
                return (
                    <SongRow
                        song={song}
                        showArtist={props.showArtist}
                        showAlbum={props.showAlbum}
                        {...(props.inPlaylistID && {
                            removeFromPlaylist: handleRemoveFromPlaylist
                        })}
                        {...(!props.inPlaylistID && {
                            addToPlaylist: handleAddToPlaylist
                        })}
                        isCurrentlyPlaying={
                            musicContext.currentPlayingSong?.id === song.id
                        }
                        addToQueue={(next) =>
                            musicContext.addToQueue(song, next)
                        }
                        startPlaying={() =>
                            musicContext.startPlayingWithNewQueue(song, songs)
                        }
                        flagSong={handleFlagSong}
                        key={song.playlisttrack}
                        className={style.songRow}
                    />
                );
            })}
        </ul>
    );
};

export default SongList;
