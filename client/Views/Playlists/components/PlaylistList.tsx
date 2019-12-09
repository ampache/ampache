import React, { useEffect, useState } from 'react';
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
import { ModalType, useModal } from '../../../Modal/Modal';
import ReactLoading from 'react-loading';

interface PlaylistListProps {
    authKey?: AuthKey;
}

const PlaylistList: React.FC<PlaylistListProps> = (props) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const Modal = useModal();

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
        Modal({
            parent: document.getElementById('modalView'),
            modalName: 'New Playlist',
            modalType: ModalType.InputModal
        })
            .then((playlistName: string) =>
                createPlaylist(playlistName, props.authKey)
            )
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
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className='playlistList'>
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
