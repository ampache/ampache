import React, { useEffect, useState } from 'react';
import { Album, getAlbum, getAlbumSongs } from '../../logic/Album';
import { User } from '../../logic/User';
import { Song } from '../../logic/Song';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';
import SongList from '../components/SongList';

interface AlbumViewProps {
    user: User;
    match: {
        params: {
            albumID: number;
        };
    };
}

const AlbumView: React.FC<AlbumViewProps> = (props) => {
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
                    {songs && <SongList songs={songs} />}
                </ul>
            </div>
        </div>
    );
};

export default AlbumView;
