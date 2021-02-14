import React, { useEffect, useState } from 'react';
import { getPlaylists, Playlist } from '~logic/Playlist';
import { AuthKey } from '~logic/Auth';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

interface PlaylistSelectorProps {
    authKey: AuthKey;
    ok?: (resolve) => Promise<unknown>; //TODO: confirm that these are correct type definitions.
    cancel?: () => Promise<unknown>;
}

import style from './index.styl';

const PlaylistSelector = (props: PlaylistSelectorProps) => {
    const [playlists, setPlaylists] = useState<Playlist[]>(null);

    const { authKey, cancel, ok } = { ...props };

    useEffect(() => {
        getPlaylists(authKey)
            .then((data) => {
                setPlaylists(data);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting playlists.');
                console.error(error);
                cancel();
            });
    }, [authKey, cancel]);

    if (!playlists) {
        return (
            <div className={style.playlistSelector}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    return (
        <div className={style.playlistSelector}>
            <ul className={style.playlists}>
                {playlists.map((playlist) => {
                    return (
                        <li
                            key={playlist.id}
                            className={style.playlist}
                            onClick={() => ok(playlist.id)}
                        >
                            {playlist.name}
                        </li>
                    );
                })}
                <li className={`${style.playlist} ${style.newPlaylist}`}>
                    Create New Playlist(TODO)
                </li>
            </ul>
        </div>
    );
};

export default PlaylistSelector;
