import React, { useEffect, useState } from 'react';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { getPlaylistSongs, getPlaylists, Playlist } from '../../logic/Playlist';
import { Song } from '../../logic/Song';
import SongList from '../components/SongList';

interface PlaylistViewProps {
    user: User;
    match: {
        params: {
            playlistID: number;
        };
    };
}

const PlaylistView: React.FC<PlaylistViewProps> = (props) => {
    const [songs, setSongs] = useState<Song[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getPlaylistSongs(
            props.match.params.playlistID,
            props.user.authKey,
            'http://localhost:8080'
        )
            .then((data) => {
                setSongs(data);
            })
            .catch((error) => {
                setError(error);
            });
    }, []);

    if (error) {
        return (
            <div className='playlistPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!songs) {
        return (
            <div className='playlistPage'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='playlistPage'>
            <h1>Playlist - {props.match.params.playlistID}</h1>
            <ul>
                {!songs && <li>'Loading Songs...'</li>}
                {songs && <SongList songs={songs} />}
            </ul>
        </div>
    );
};

export default PlaylistView;
