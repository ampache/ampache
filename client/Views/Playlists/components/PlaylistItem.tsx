import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Playlist } from '~logic/Playlist';
import { Link, useHistory } from 'react-router-dom';
import { Modal } from '~node_modules/react-async-popup';
import HistoryShell from '~Modal/HistoryShell';
import SVG from 'react-inlinesvg';
import Rating from '~components/Rating/';

import style from '/stylus/components/PlaylistItem.styl';

interface PlaylistItemProps {
    playlist: Playlist;
    deletePlaylist?: (playlistID: number) => void;
    editPlaylist?: (playlistID: number, playlistCurrentName: string) => void;
}

const PlaylistItem: React.FC<PlaylistItemProps> = (props: PlaylistItemProps) => {
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

    //TODO: React version of this for card link: https://codepen.io/vikas-parashar/pen/qBOwMWj

    return (
        <>
            <div
                className={style.playlistItem}
                {...bindTrigger}
            >
                <div className={style.details}>
                    {props.playlist.id === 0 &&
                        <div>SMARTLIST</div>
                    }
                    <div className={style.name}>
                        <Link 
                            to={`/playlist/${props.playlist.id}`}
                            className={style.cardLink}>
                                {props.playlist.name}
                        </Link>
                    </div>
                    {props.playlist.id > 0 &&
                        <div className={style.rating}>
                            <Rating value={props.playlist.rating} fav={props.playlist.flag}/>
                        </div>
                    }
                </div>
                <div className={style.meta}>
                    <span className={style.itemCount}>
                        {props.playlist.id === 0 &&
                            `Up to `
                        }
                        {`${props.playlist.items} songs`}
                    </span>
                    <span className={style.owner}> by {props.playlist.owner}</span>
                </div>
                
                <div className={style.actions}>
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play.svg')} alt="Play" />
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play-next.svg')} alt="Play next" />
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play-last.svg')} alt="Play last" />
                    <SVG 
                        onClick={(e) => {
                            showContextMenu(e);
                        }}
                        className='icon-button-small' src={require('~images/icons/svg/more-options-hori.svg')} alt="More options" 
                    />
                </div>
            </div>

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

export default PlaylistItem;
