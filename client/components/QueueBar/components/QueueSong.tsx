import React from 'react';
import SVG from 'react-inlinesvg';
import { Song } from '~logic/Song';

interface QueueSongProps {
    song: Song;
    currentlyPlaying: boolean;
    onClick: () => void;
}

import style from './index.styl';

const QueueSong: React.FC<QueueSongProps> = (props) => {
    return (
        <li
            className={
                props.currentlyPlaying
                    ? `${style.song} ${style.playing}`
                    : `${style.song}`
            }
            onClick={props.onClick}
        >
            <div className={style.imageWrapper}>
                <img src={props.song.art} alt='Album cover' />
            </div>
            <div className={style.details}>
                <div className={style.songName}>{props.song.title}</div>
                <div className={style.albumArtist}>
                    {props.song.artist.name}
                </div>
            </div>
            <div className={style.actions}>
                <SVG
                    className='icon-button'
                    src={require('~images/icons/svg/cross.svg')}
                    title='Remove'
                    description='Remove song from Queue'
                    role='button'
                />
            </div>
        </li>
    );
};

export default QueueSong;
