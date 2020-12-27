import React, { useEffect, useState } from 'react';
import SVG from 'react-inlinesvg';
import {
    createPlaylist,
    deletePlaylist,
    getPlaylists,
    Playlist,
    renamePlaylist
} from '~logic/Playlist';
import PlaylistItem from './PlaylistItem';
import { AuthKey } from '~logic/Auth';
import AmpacheError from '~logic/AmpacheError';
import { Modal } from 'react-async-popup';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import InputModal from '~Modal/types/InputModal';
import { useHistory } from 'react-router-dom';
import HistoryShell from '~Modal/HistoryShell';

import style from '/stylus/components/PlaylistList.styl';

interface PlaylistListProps {
    authKey?: AuthKey;
}

const PlaylistList: React.FC<PlaylistListProps> = (props) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);
    const history = useHistory();

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

    const handleNewPlaylist = async () => {
        const { show } = await Modal.new({
            title: 'Create new playlist',
            content: (
                <HistoryShell history={history}>
                    <InputModal
                        inputLabel='Name'
                        inputPlaceholder='Rock & Roll...'
                        submitButtonText='Create'
                    />
                </HistoryShell>
            ),
            footer: null
        });
        const result = await show();

        if (result) {
            createPlaylist(result, props.authKey)
                .then((newPlaylist) => {
                    console.log(newPlaylist);
                    const newPlaylists = [...playlists];
                    newPlaylists.unshift(newPlaylist);
                    setPlaylists(newPlaylists);
                    toast.success('Created Playlist.');
                })
                .catch((err) => {
                    toast.error(
                        '😞 Something went wrong creating new playlist.'
                    );
                    console.error(err);
                });
        }
    };

    const handleEditPlaylist = async (
        playlistID: number,
        playlistCurrentName: string
    ) => {
        const { show } = await Modal.new({
            title: `Editing ${playlistCurrentName}`,
            content: (
                <HistoryShell history={history}>
                    <InputModal
                        inputLabel='New Playlist Name'
                        inputInitialValue={playlistCurrentName}
                        inputPlaceholder={playlistCurrentName}
                        submitButtonText='Save'
                    />
                </HistoryShell>
            ),
            footer: null
        });
        const newName = await show();
        if (newName) {
            try {
                await renamePlaylist(playlistID, newName, props.authKey);

                const newPlaylists = playlists.map((playlist) => {
                    if (playlist.id === playlistID) {
                        playlist.name = newName;
                        return playlist;
                    }
                    return playlist;
                });

                setPlaylists(newPlaylists);
                toast.success('Edited Playlist.');
            } catch (e) {
                toast.error('😞 Something went wrong editing the playlist.');
            }
        }
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
            <SVG
                className='icon-button'
                src={require('~images/icons/svg/plus.svg')}
                title='Add to playlist'
                role='button'
                onClick={handleNewPlaylist}
            />
            <div className={style.playlistListContainer}>
                {playlists.map((playlist: Playlist) => {
                    return (
                        <PlaylistItem
                            playlist={playlist}
                            deletePlaylist={handleDeletePlaylist}
                            editPlaylist={handleEditPlaylist}
                            key={playlist.id}
                        />
                    );
                })}
            </div>
        </div>
    );
};

export default PlaylistList;
