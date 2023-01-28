import React from 'react';
import PlaylistList from '~components/PlaylistList';

import style from './index.styl';

const PlaylistsPage = () => {
    return (
        <div className={style.playlistsPage}>
            <h1>Playlists</h1>
            <PlaylistList />
        </div>
    );
};

export default PlaylistsPage;
