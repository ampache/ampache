import React, { MutableRefObject } from 'react';
import ReactDOM from 'react-dom';
import { getPlaylists, Playlist } from '../../logic/Playlist';
import { AuthKey } from '../../logic/Auth';
import closeWindowIcon from '/images/icons/svg/close-window.svg';

interface PlaylistSelectorParams {
    parent: MutableRefObject<any>;
    authKey: AuthKey;
}

const PlaylistSelector = async (params: PlaylistSelectorParams) => {
    function escFunction(event) {
        if (event.keyCode === 27) {
            close();
        }
    }
    document.addEventListener('keydown', escFunction, false);

    function close() {
        console.log(params.parent);
        document.removeEventListener('keydown', escFunction, false);
        ReactDOM.unmountComponentAtNode(params.parent.current);
    }
    ReactDOM.render(
        //TODO: Make pretty
        <div
            className='playlistSelector'
            onClick={() => {
                close();
            }}
        >
            Loading...
        </div>,
        params.parent.current
    );

    return new Promise(async (resolve: (value?: number) => void, reject) => {
        let playlists: Playlist[];
        try {
            playlists = await getPlaylists(params.authKey);
        } catch (e) {
            return reject(e);
        }
        const selector = (
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
                                    onClick={() => select(playlist.id)}
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

        function select(id: number) {
            close();
            return resolve(id);
        }

        ReactDOM.render(selector, params.parent.current);
    });
};

export default PlaylistSelector;
