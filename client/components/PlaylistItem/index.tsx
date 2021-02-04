import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Playlist } from '~logic/Playlist';
import { Link, useHistory } from 'react-router-dom';
import { Modal } from '~node_modules/react-async-popup';
import HistoryShell from '~Modal/HistoryShell';
import SVG from 'react-inlinesvg';
import Rating from '~components/Rating/';

import style from './index.styl';

interface PlaylistItemProps {
    playlist: Playlist;
    deletePlaylist?: (playlistID: number) => void;
    editPlaylist?: (playlistID: number, playlistCurrentName: string) => void;
}

const PlaylistItem: React.FC<PlaylistItemProps> = (
    props: PlaylistItemProps
) => {
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
            <li className={`card-clear ${style.playlistItem}`} {...bindTrigger}>
                <div className={style.info}>
                    <div className={`card-title ${style.name}`}>
                        <Link to={`/playlist/${props.playlist.id}`}>
                            {props.playlist.name}
                        </Link>
                    </div>
                    <div className={style.details}>
                        {props.playlist.id.includes("smart_") // indicate if smartlist, else show rating
                        ?
                        <div className={style.smartlistTag}>
                            Smartlist
                        </div>
                        :
                        <div className={style.rating}>
                            <Rating
                                value={""}
                                fav={""}
                            />
                        </div>
                        }
                    </div>
                    <div className={style.meta}>
                        {props.playlist.id.includes("smart_") && `Up to `}
                        <span className={style.itemCount}>
                            {props.playlist.items}
                        </span>
                        {` songs`}
                        <span className={style.owner}>
                            {' '}
                            by {props.playlist.owner}
                        </span>
                    </div>
                </div>

                <div className={style.actions}>
                    <SVG
                        className='icon icon-button-small'
                        src={require('~images/icons/svg/play.svg')}
                        title='Play'
                        role='button'
                    />
                    <SVG
                        className='icon icon-button-small'
                        src={require('~images/icons/svg/play-next.svg')}
                        title='Play next'
                        role='button'
                    />
                    <SVG
                        className='icon icon-button-small'
                        src={require('~images/icons/svg/play-last.svg')}
                        title='Play last'
                        role='button'
                    />
                    <SVG
                        onClick={(e) => {
                            showContextMenu(e);
                        }}
                        className='icon icon-button-small'
                        src={require('~images/icons/svg/more-options-hori.svg')}
                        title='More options'
                        role='button'
                    />
                </div>
            </li>

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
