import React from 'react';
import { User } from '~logic/User';
import SongList from '~components/SongList';

interface PlaylistPageProps {
    user: User;
    match: {
        params: {
            playlistID: string;
        };
    };
}

const PlaylistPage: React.FC<PlaylistPageProps> = (props) => {
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

export default PlaylistPage;
