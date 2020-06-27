import React, { useEffect, useState } from 'react';
import { getPlaylists, Playlist } from '~logic/Playlist';
import { AuthKey } from '~logic/Auth';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

interface PlaylistSelectorProps {
    authKey: AuthKey;
    ok?: any; //TODO
    cancel?: any;
}

const PlaylistSelector = (props: PlaylistSelectorProps) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);

    useEffect(() => {
        getPlaylists(props.authKey)
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting playlists.');
                console.error(error);
                props.cancel();
            });
    }, [props.authKey, props.cancel]);

    if (!playlists) {
        return (
            <div className='playlistSelector'>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    return (
        <div className='playlistSelector'>
            <div
                className='content'
                onClick={(e) => {
                    e.stopPropagation();
                }}
            >
                <ul className='playlists'>
                    {playlists.map((playlist) => {
                        return (
                            <li
                                key={playlist.id}
                                className='playlist'
                                onClick={() => props.ok(playlist.id)}
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
