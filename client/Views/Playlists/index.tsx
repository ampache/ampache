import React, { useEffect, useRef, useState } from 'react';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import {
    addToPlaylist,
    createPlaylist,
    getPlaylists,
    Playlist
} from '../../logic/Playlist';
import PlaylistRow from '../components/PlaylistRow';
import Plus from '/images/icons/svg/plus.svg';
import { useInputModal } from '../components/InputModal';

interface PlaylistsViewProps {
    user: User;
}

const PlaylistsView: React.FC<PlaylistsViewProps> = (props) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const modalRootRef = useRef(null);
    const InputModal = useInputModal();

    useEffect(() => {
        getPlaylists(props.user.authKey)
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                setError(error);
            });
    }, []);

    const handleNewPlaylist = () => {
        InputModal({
            modalName: 'New Playlist',
            parent: modalRootRef
        })
            .then((playlistName) => {
                console.log(playlistName);
                createPlaylist(playlistName, props.user.authKey).catch(
                    (err) => {
                        //TODO
                        console.log(err);
                    }
                );
            })
            .catch((err) => {
                console.log(err);
            });
    };

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
            <div className='modalRoot' ref={modalRootRef} />
            <h1>Playlists</h1>
            <img src={Plus} alt='Add Playlist' onClick={handleNewPlaylist} />
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
