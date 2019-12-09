import React, { useEffect, useRef, useState } from 'react';
import {
    createPlaylist,
    deletePlaylist,
    getPlaylists,
    Playlist
} from '../../../logic/Playlist';
import PlaylistRow from './PlaylistRow';
import { AuthKey } from '../../../logic/Auth';
import AmpacheError from '../../../logic/AmpacheError';
import Plus from '/images/icons/svg/plus.svg';
import { useInputModal } from '../../components/InputModal';

interface PlaylistListProps {
    authKey?: AuthKey;
}

const PlaylistList: React.FC<PlaylistListProps> = (props) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const modalRootRef = useRef(null);
    const InputModal = useInputModal();

    useEffect(() => {
        getPlaylists(props.authKey)
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                setError(error);
            });
    }, []);

    const handleDeletePlaylist = (playlistID: number) => {
        deletePlaylist(playlistID, props.authKey)
            .then(() => {
                let newPlaylists = [...playlists];
                newPlaylists = newPlaylists.filter(
                    (playlist) => playlist.id != playlistID
                );
                setPlaylists(newPlaylists);
            })
            .catch((error) => {
                setError(error);
            });
    };

    const handleNewPlaylist = () => {
        InputModal({
            modalName: 'New Playlist',
            parent: document.getElementById('modalView')
        })
            .then((playlistName) => createPlaylist(playlistName, props.authKey))
            .then((newPlaylist) => {
                console.log(newPlaylist);
                const newPlaylists = [...playlists];
                newPlaylists.push(newPlaylist);
                setPlaylists(newPlaylists);
            })
            .catch((err) => {
                setError(err);
            });
    };

    if (error) {
        return (
            <div className='playlistList'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!playlists) {
        return (
            <div className='playlistList'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='playlistList'>
            <div className='modalRoot' ref={modalRootRef} />
            <img src={Plus} alt='Add Playlist' onClick={handleNewPlaylist} />
            <ul>
                {playlists.map((playlist: Playlist) => {
                    return (
                        <PlaylistRow
                            playlist={playlist}
                            deletePlaylist={handleDeletePlaylist}
                            key={playlist.id}
                        />
                    );
                })}
            </ul>
        </div>
    );
};

export default PlaylistList;
