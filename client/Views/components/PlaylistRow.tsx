import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Playlist } from '../../logic/Playlist';
import { Link } from 'react-router-dom';

interface PlaylistRowProps {
    playlist: Playlist;
}

const PlaylistRow: React.FC<PlaylistRowProps> = (props: PlaylistRowProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    return (
        <>
            <Link
                className='playlistRow'
                to={`/playlist/${props.playlist.id}`}
                {...bindTrigger}
            >
                <span className='name'>{props.playlist.name}</span>
                <span className='itemCount'>{props.playlist.items}</span>
                <span className='owner'>{props.playlist.owner}</span>
            </Link>
            <div {...bindMenu} className='contextMenu'>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                    }}
                >
                    Start
                </div>
            </div>
        </>
    );
};

export default PlaylistRow;
