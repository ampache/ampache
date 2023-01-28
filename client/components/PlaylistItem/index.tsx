import React from 'react';
import { useGetPlaylist } from '~logic/Playlist';
import { Link } from 'react-router-dom';
import SVG from 'react-inlinesvg';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';

interface PlaylistItemProps {
    playlistId: string;
    showContext?: (event: React.MouseEvent, playlistID: string) => void;
    startPlaying: (playlistID: string) => void;
}

const PlaylistItem: React.FC<PlaylistItemProps> = ({
    playlistId,
    showContext,
    startPlaying
}: PlaylistItemProps) => {
    const { data: playlist } = useGetPlaylist(playlistId);

    return (
        <li
            className={`card-clear ${style.playlistItem}`}
            onContextMenu={(e) => showContext(e, playlist.id)}
        >
            <div className={style.info}>
                <div className={`card-title ${style.name}`}>
                    <Link to={`/playlist/${playlist.id}`}>{playlist.name}</Link>
                </div>
                <div className={style.details}>
                    {playlist.id.includes('smart_') ? ( // indicate if smartlist, else show rating
                        <div className={style.smartlistTag}>Smartlist</div>
                    ) : (
                        <div className={style.rating}>
                            <SimpleRating
                                value={playlist.rating}
                                fav={playlist.flag}
                                itemId={playlist.id}
                                type='playlist'
                            />
                        </div>
                    )}
                </div>
                <div className={style.meta}>
                    {playlist.id.includes('smart_') && `Up to `}
                    <span className={style.itemCount}>{playlist.items}</span>
                    {` songs`}
                    <span className={style.owner}> by {playlist.owner}</span>
                </div>
            </div>
            <div className={style.actions}>
                <SVG
                    className='icon icon-button-small'
                    src={require('~images/icons/svg/play.svg')}
                    title='Play'
                    role='button'
                    onClick={() => startPlaying(playlist.id)}
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
                        showContext(e, '');
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
