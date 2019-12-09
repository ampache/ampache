import React, { useState } from 'react';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import PlaylistList from './components/PlaylistList';

interface PlaylistsViewProps {
    user: User;
}

const PlaylistsView: React.FC<PlaylistsViewProps> = (props) => {
    const [error, setError] = useState<Error | AmpacheError>(null);

    if (error) {
        return (
            <div className='playlistsPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }

    return (
        <div className='playlistsPage'>
            <h1>Playlists</h1>
            <PlaylistList authKey={props.user.authKey} />
        </div>
    );
};

export default PlaylistsView;
