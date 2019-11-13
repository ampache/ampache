import React, { useContext, useEffect, useState } from 'react';
import { Album, getAlbum, getAlbumSongs } from '../../logic/Album';
import { User } from '../../logic/User';
import { Song } from '../../logic/Song';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';
import { MusicContext } from '../../MusicContext';

interface AlbumViewProps {
    user: User;
    match: {
        params: {
            albumID: number;
        };
    };
}

const AlbumView: React.FC<AlbumViewProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [theAlbum, setTheAlbum] = useState<Album>(null);
    const [songs, setSongs] = useState<Song[]>([]);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.albumID != null) {
            getAlbum(
                props.match.params.albumID,
                props.user.authKey,
                'http://localhost:8080'
            )
                .then((data) => {
                    setTheAlbum(data);
                })
                .catch((error) => {
                    setError(error);
                });

            getAlbumSongs(
                props.match.params.albumID,
                props.user.authKey,
                'http://localhost:8080'
            )
                .then((songs) => {
                    setSongs(songs);
                })
                .catch((error) => {
                    setError(error);
                });
        }
    }, []);

    if (error) {
        return (
            <div className='albumPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!theAlbum) {
        return (
            <div className='albumPage'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='albumPage'>
            <div className='album'>
                <div className='imageContainer'>
                    <img src={theAlbum.art} alt={'Album Cover'} />
                </div>
                <div className='details'>
                    <div className='albumName'>{theAlbum.name}</div>
                    <Link
                        to={`/artist/${theAlbum.artist.id}`}
                        className='artistName'
                    >
                        {theAlbum.artist.name}
                    </Link>
                </div>
            </div>
            <div className='songs'>
                <ul>
                    {!songs && <li>'Loading Songs...'</li>}
                    {songs &&
                        songs.map((song: Song) => {
                            const minutes: number = Math.round(song.time / 60);
                            const seconds: string = (song.time % 60).toString();
                            const paddedSeconds: string =
                                seconds.length === 1 ? seconds + '0' : seconds;

                            return (
                                <li
                                    onClick={() =>
                                        musicContext.startPlaying(
                                            song.url,
                                            song.id,
                                            song.art
                                        )
                                    }
                                    className={
                                        musicContext.playingSongID === song.id
                                            ? 'playing'
                                            : ''
                                    }
                                    key={song.id}
                                >
                                    <span className='title'>{song.title}</span>
                                    <span className='time'>
                                        {minutes}:{paddedSeconds}
                                    </span>
                                </li>
                            );
                        })}
                </ul>
            </div>
        </div>
    );
};

export default AlbumView;
