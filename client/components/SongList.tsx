import React, { useContext, useEffect, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { flagSong, Song } from '~logic/Song';
import SongRow from './SongRow';
import {
    addToPlaylist,
    getPlaylistSongs,
    removeFromPlaylistWithSongID
} from '~logic/Playlist';
import { AuthKey } from '~logic/Auth';
import AmpacheError from '../logic/AmpacheError';
import { getAlbumSongs } from '~logic/Album';
import { ModalType, useModal } from '~Modal/Modal';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

interface SongListProps {
    showArtist?: boolean;
    showAlbum?: boolean;
    inPlaylistID?: number;
    inAlbumID?: number;
    authKey?: AuthKey;
}

const SongList: React.FC<SongListProps> = (props) => {
    const musicContext = useContext(MusicContext);
    const [songs, setSongs] = useState<Song[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const Modal = useModal();

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
        } else {
            throw new Error('Missing inPlaylistID and inAlbumID.');
        }
    }, [props.authKey, props.inAlbumID, props.inPlaylistID]);

    const handleRemoveFromPlaylist = (songID: number) => {
        removeFromPlaylistWithSongID(
            props.inPlaylistID,
            songID,
            props.authKey
        ).then(() => {
            let newSongs = [...songs];
            console.log(newSongs);
            newSongs = newSongs.filter((song) => song.id != songID);
            setSongs(newSongs);
        });
    };
    const handleAddToPlaylist = (songID: number) => {
        console.log(songID);
        Modal({
            parent: document.getElementById('modalView'),
            modalName: 'Add To Playlist',
            modalType: ModalType.PlaylistSelectorModal,
            authKey: props.authKey
        })
            .then((playlistID: number) => {
                addToPlaylist(playlistID, songID, props.authKey).then(() =>
                    toast.success('Added song to playlist')
                );
            })
            .catch((err) => {
                toast.error('😞 Something went wrong adding to playlist.');
                setError(err);
            });
    };

    const handleFlagSong = (songID: number, favorite: boolean) => {
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
            <div className='songList'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!songs) {
        return (
            <div className='songList'>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className='songList'>
            <ul>
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
                                musicContext.startPlayingWithNewQueue(
                                    song,
                                    songs
                                )
                            }
                            flagSong={handleFlagSong}
                            key={song.playlisttrack}
                        />
                    );
                })}
            </ul>
        </div>
    );
};

export default SongList;
