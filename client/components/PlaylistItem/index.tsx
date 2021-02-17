import React from 'react';
import { Playlist } from '~logic/Playlist';
import { Link } from 'react-router-dom';
import SVG from 'react-inlinesvg';
import Rating from '~components/Rating/';

import style from './index.styl';

interface PlaylistItemProps {
    playlist: Playlist;
    showContext?: (event: React.MouseEvent, playlistID: string) => void;
    startPlaying: (playlistID: string) => void;
}

const PlaylistItem: React.FC<PlaylistItemProps> = (
    props: PlaylistItemProps
) => {
    return (
        <li
            className={`card-clear ${style.playlistItem}`}
            onContextMenu={(e) => props.showContext(e, props.playlist.id)}
        >
            <div className={style.info}>
                <div className={`card-title ${style.name}`}>
                    <Link to={`/playlist/${props.playlist.id}`}>
                        {props.playlist.name}
                    </Link>
                </div>
                <div className={style.details}>
                    {props.playlist.id.includes('smart_') ? ( // indicate if smartlist, else show rating
                        <div className={style.smartlistTag}>Smartlist</div>
                    ) : (
                        <div className={style.rating}>
                            <Rating value={''} fav={''} />
                        </div>
                    )}
                </div>
                <div className={style.meta}>
                    {props.playlist.id.includes('smart_') && `Up to `}
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
                    onClick={() => props.startPlaying(props.playlist.id)}
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
                        props.showContext(e, '');
                    }}
                    className='icon icon-button-small'
                    src={require('~images/icons/svg/more-options-hori.svg')}
                    title='More options'
                    role='button'
                />
            </div>
        </li>
    );
};

export default PlaylistItem;
