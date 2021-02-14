import React from 'react';
import { User } from '~logic/User';
import SongList from '~components/SongList';

interface PlaylistViewProps {
    user: User;
    match: {
        params: {
            playlistID: string;
        };
    };
}

const PlaylistView: React.FC<PlaylistViewProps> = (props) => {
    return (
        <div className='playlistPage'>
            <h1>Playlist - {props.match.params.playlistID}</h1>
            <SongList
                showArtist={true}
                showAlbum={true}
                inPlaylistID={props.match.params.playlistID}
                authKey={props.user.authKey}
            />
        </div>
    );
};

export default PlaylistView;
