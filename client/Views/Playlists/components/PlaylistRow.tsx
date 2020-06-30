import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Playlist } from '~logic/Playlist';
import { Link, useHistory } from 'react-router-dom';
import { Modal } from '~node_modules/react-async-popup';
import HistoryShell from '~Modal/HistoryShell';

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
    const history = useHistory();

    const showContextMenu = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setCoords(0, 0);
        setVisible(true);
    };

    const handleDeleteRequest = async () => {
        setVisible(false);
        const { show } = await Modal.new({
            title: `Are you sure you want to delete ${props.playlist.name}?`,
            content: <HistoryShell history={history} />
        });
        const result = await show();
        if (result) {
            props.deletePlaylist(props.playlist.id);
        }
    };

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
                    <div {...bindMenuItems} onClick={handleDeleteRequest}>
                        Delete Playlist
                    </div>
                )}
            </div>
        </>
    );
};

export default PlaylistRow;
