import React from 'react';
import { User } from '../../logic/User';
import PlaylistList from './components/PlaylistList';

interface PlaylistsViewProps {
    user: User;
}

const PlaylistsView: React.FC<PlaylistsViewProps> = (props) => {
    return (
        <div className='playlistsPage'>
            <h1>Playlists</h1>
            <PlaylistList authKey={props.user.authKey} />
        </div>
    );
};

export default PlaylistsView;
