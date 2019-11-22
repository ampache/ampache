import React, { useEffect, useState } from 'react';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { getPlaylists, Playlist } from '../../logic/Playlist';
import PlaylistRow from '../components/PlaylistRow';

interface PlaylistsViewProps {
    user: User;
}

const PlaylistsView: React.FC<PlaylistsViewProps> = (props) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getPlaylists(props.user.authKey, 'http://localhost:8080')
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                setError(error);
            });
    }, []);

    if (error) {
        return (
            <div className='playlistsPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!playlists) {
        return (
            <div className='playlistsPage'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='playlistsPage'>
            <h1>Playlists</h1>
            <div className='playlists'>
                {playlists.map((playlist) => {
                    return (
                        <PlaylistRow playlist={playlist} key={playlist.id} />
                    );
                })}
            </div>
        </div>
    );
};

export default PlaylistsView;
