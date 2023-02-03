import React from 'react';
import SongList from '~components/SongList';
import { useGetPlaylistSongs } from '~logic/Playlist';
import Loading from 'react-loading';
import { useParams } from 'react-router-dom';

const PlaylistPage = () => {
    const { playlistID } = useParams();

    const { data, isLoading } = useGetPlaylistSongs(playlistID);

    if (isLoading) {
        return <Loading />;
    }
    const songIds = data.map((song) => song.id);

    return (
        <div className='paddedPage'>
            <h1>Playlist - {playlistID}</h1>
            <SongList
                showArtist={true}
                showAlbum={true}
                songIds={songIds}
                inPlaylistID={playlistID}
            />
        </div>
    );
};

export default PlaylistPage;
