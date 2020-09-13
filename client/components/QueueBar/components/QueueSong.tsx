import React from 'react';
import { Song } from '~logic/Song';
import { Link } from 'react-router-dom';

interface QueueSongProps {
    song: Song;
    currentlyPlaying: boolean;
    onClick: any;
}

import style from './index.module.styl';

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
                <img src={props.song.art} alt='Album Cover' />
            </div>
            <div className={style.details}>
                <div className={style.songName}>{props.song.title}</div>
                <div className={style.albumArtist}>
                    <Link
                        to={`/artist/${props.song.artist.id}`}
                        onClick={(e) => {
                            e.stopPropagation();
                        }}
                    >
                        {props.song.artist.name}
                    </Link>
                    <span className={style.gap}>-</span>
                    <Link
                        to={`/album/${props.song.album.id}`}
                        onClick={(e) => {
                            e.stopPropagation();
                        }}
                    >
                        {props.song.album.name}
                    </Link>
                </div>
            </div>
        </li>
    );
};

export default QueueSong;
