import React from 'react';
import PlaylistList from '~components/PlaylistList';

const PlaylistsPage = () => {
    return (
        <div className={'paddedPage'}>
            <h1>Playlists</h1>
            <PlaylistList />
        </div>
    );
};

export default PlaylistsPage;
