import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Playlist } from '~logic/Playlist';
import { Link } from 'react-router-dom';
import { useConfirm } from 'react-async-popup';

interface PlaylistRowProps {
    playlist: Playlist;
    deletePlaylist?: (playlistID: number) => void;
    editPlaylist?: (playlistID: number, playlistCurrentName: string) => void;
}

const PlaylistRow: React.FC<PlaylistRowProps> = (props: PlaylistRowProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible, setCoords }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    const showContextMenu = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setCoords(0, 0);
        setVisible(true);
    };

    const [showConfirm] = useConfirm({
        title: `Are you sure you want to delete ${props.playlist.name}?`
    });

    return (
        <>
            <Link
                className='playlistRow'
                to={`/playlist/${props.playlist.id}`}
                {...bindTrigger}
            >
                <span
                    className='verticalMenu'
                    onClick={(e) => {
                        showContextMenu(e);
                    }}
                />
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
                {props.editPlaylist && (
                    <div
                        {...bindMenuItems}
                        onClick={() => {
                            setVisible(false);
                            props.editPlaylist(
                                props.playlist.id,
                                props.playlist.name
                            );
                        }}
                    >
                        Edit Playlist
                    </div>
                )}
                {props.deletePlaylist && (
                    <div
                        {...bindMenuItems}
                        onClick={async () => {
                            setVisible(false);
                            const result = await showConfirm();
                            if (result) {
                                props.deletePlaylist(props.playlist.id);
                            }
                        }}
                    >
                        Delete Playlist
                    </div>
                )}
            </div>
        </>
    );
};

export default PlaylistRow;
