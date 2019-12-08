import React, { useContext, useEffect, useRef, useState } from 'react';
import { MusicContext } from '../../Contexts/MusicContext';
import { Song } from '../../logic/Song';
import SongRow from './SongRow';
import {
    addToPlaylist,
    getPlaylistSongs,
    removeFromPlaylistWithSongID
} from '../../logic/Playlist';
import { AuthKey } from '../../logic/Auth';
import PlaylistSelector from './PlaylistSelector';
import AmpacheError from '../../logic/AmpacheError';
import { getAlbumSongs } from '../../logic/Album';

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

    const modalRootRef = useRef(null);

    useEffect(() => {
        if (props.inPlaylistID) {
            getPlaylistSongs(props.inPlaylistID, props.authKey)
                .then((data) => {
                    setSongs(data);
                })
                .catch((error) => {
                    setError(error);
                });
        } else if (props.inAlbumID) {
            getAlbumSongs(props.inAlbumID, props.authKey)
                .then((songs) => {
                    setSongs(songs);
                })
                .catch((error) => {
                    setError(error);
                });
        } else {
            throw new Error('Missing inPlaylistID and inAlbumID.');
        }
    }, []);

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
        PlaylistSelector({
            //TODO: this might be an awful idea, possibly needing to be refactored. But it's cool I got it to work!
            parent: modalRootRef,
            authKey: props.authKey
        })
            .then((playlistID) => {
                addToPlaylist(playlistID, songID, props.authKey).catch(
                    (err) => {
                        //TODO
                        console.log(err);
                    }
                );
            })
            .catch((err) => {
                console.log(err);
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
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='songList'>
            <div className='modalRoot' ref={modalRootRef} />
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
                            key={song.id}
                        />
                    );
                })}
            </ul>
        </div>
    );
};

export default SongList;
