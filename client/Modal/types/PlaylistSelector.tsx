import React, { useEffect, useState } from 'react';
import { getPlaylists, Playlist } from '../../logic/Playlist';
import { AuthKey } from '../../logic/Auth';
import closeWindowIcon from '/images/icons/svg/close-window.svg';
import AmpacheError from '../../logic/AmpacheError';

interface PlaylistSelectorProps {
    returnData: (data: number) => void;
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
                setError(error);
            });
    }, []);

    if (!playlists) {
        return (
            <div
                className='playlistSelector'
                onClick={() => {
                    close();
                }}
            >
                Loading...
            </div>
        );
    }

    return (
        <div
            className='playlistSelector'
            onClick={() => {
                close();
            }}
        >
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
                        onClick={() => close()}
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
