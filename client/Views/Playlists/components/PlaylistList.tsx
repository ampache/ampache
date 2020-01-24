import React, { useEffect, useState } from 'react';
import {
    createPlaylist,
    deletePlaylist,
    getPlaylists,
    Playlist,
    renamePlaylist
} from '../../../logic/Playlist';
import PlaylistRow from './PlaylistRow';
import { AuthKey } from '../../../logic/Auth';
import AmpacheError from '../../../logic/AmpacheError';
import Plus from '/images/icons/svg/plus.svg';
import { ModalType, useModal } from '../../../Modal/Modal';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

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
                toast.error('😞 Something went getting playlists.');
                setError(error);
            });
    }, [props.authKey]);

    const handleDeletePlaylist = (playlistID: number) => {
        deletePlaylist(playlistID, props.authKey)
            .then(() => {
                let newPlaylists = [...playlists];
                newPlaylists = newPlaylists.filter(
                    (playlist) => playlist.id != playlistID
                );
                setPlaylists(newPlaylists);
                toast.success('Deleted Playlist.');
            })
            .catch((err) => {
                toast.error(
                    '😞 Something went wrong trying to delete playlist.'
                );
                console.error(err);
            });
    };

    const handleNewPlaylist = () => {
        Modal({
            parent: document.getElementById('modalView'),
            modalName: 'New Playlist',
            modalType: ModalType.InputModal,
            submitButtonText: 'Create Playlist'
        })
            .then((playlistName: string) =>
                createPlaylist(playlistName, props.authKey)
            )
            .then((newPlaylist) => {
                console.log(newPlaylist);
                const newPlaylists = [...playlists];
                newPlaylists.unshift(newPlaylist);
                setPlaylists(newPlaylists);
                toast.success('Created Playlist.');
            })
            .catch((err) => {
                toast.error('😞 Something went wrong creating new playlist.');
                console.error(err);
            });
    };

    const handleEditPlaylist = (
        playlistID: number,
        playlistCurrentName: string
    ) => {
        Modal({
            parent: document.getElementById('modalView'),
            modalName: 'Edit Playlist',
            modalType: ModalType.InputModal,
            inputInitialValue: playlistCurrentName
        })
            .then(async (newName: string) => {
                await renamePlaylist(playlistID, newName, props.authKey);

                const newPlaylists = playlists.map((playlist) => {
                    if (playlist.id === playlistID) {
                        playlist.name = newName;
                        return playlist;
                    }
                    return playlist;
                });

                setPlaylists(newPlaylists);
                toast.success('Renamed Playlist.');
            })
            .catch((err) => {
                toast.error('😞 Something went wrong editing playlist.');
                console.error(err);
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
                            editPlaylist={handleEditPlaylist}
                            key={playlist.id}
                        />
                    );
                })}
            </ul>
        </div>
    );
};

export default PlaylistList;
