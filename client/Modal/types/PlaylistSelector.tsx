import React, { useEffect, useState } from 'react';
import { getPlaylists, Playlist } from '../../logic/Playlist';
import { AuthKey } from '../../logic/Auth';
import closeWindowIcon from '/images/icons/svg/close-window.svg';
import AmpacheError from '../../logic/AmpacheError';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

interface PlaylistSelectorProps {
    returnData: (data: number) => void;
    close: () => void;
    authKey: AuthKey;
}

const PlaylistSelector = (props: PlaylistSelectorProps) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getPlaylists(props.authKey)
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting playlists.');
                setError(error);
            });
    }, []);

    if (!playlists) {
        return (
            <div className='playlistSelector' onClick={props.close}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    return (
        <div className='playlistSelector' onClick={props.close}>
            <div
                className='content'
                onClick={(e) => {
                    e.stopPropagation();
                }}
            >
                <div className='header'>
                    <div className='title'>Add to Playlist</div>
                    <img
                        className='close'
                        src={closeWindowIcon}
                        alt='Close'
                        onClick={props.close}
                    />
                </div>
                <ul className='playlists'>
                    {playlists.map((playlist) => {
                        return (
                            <li
                                key={playlist.id}
                                className='playlist'
                                onClick={() => props.returnData(playlist.id)}
                            >
                                {playlist.name}
                            </li>
                        );
                    })}
                    <li className='playlist newPlaylist'>
                        Create New Playlist(TODO)
                    </li>
                </ul>
            </div>
        </div>
    );
};

export default PlaylistSelector;
