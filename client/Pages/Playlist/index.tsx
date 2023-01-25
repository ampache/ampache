import React from 'react';
import { User } from '~logic/User';
import SongList from '~components/SongList';
import { useGetPlaylistSongs } from '~logic/Playlist';
import Loading from 'react-loading';

interface PlaylistPageProps {
    user: User;
    match: {
        params: {
            playlistID: string;
        };
    };
}

const PlaylistPage: React.FC<PlaylistPageProps> = (props) => {
    const playlistID = props.match.params.playlistID;
    const { data, isLoading } = useGetPlaylistSongs(playlistID);

    if (isLoading) {
        return <Loading />;
    }
    const songIds = data.map((song) => song.id);

    return (
        <div className='playlistPage'>
            <h1>Playlist - {props.match.params.playlistID}</h1>
            <SongList
                showArtist={true}
                showAlbum={true}
                songIds={songIds}
                inPlaylistID={props.match.params.playlistID}
            />
        </div>
    );
};

export default PlaylistPage;
