import React from 'react';
import SVG from 'react-inlinesvg';
import { Song } from '~logic/Song';

interface QueueSongProps {
    song: Song;
    currentlyPlaying: boolean;
    queueIndex: number;
    playSong: (songID: string) => void;
    removeSong: (queueIndex: number) => void;
}

import style from './index.styl';

const QueueSong: React.FC<QueueSongProps> = (props) => {
    return (
        <li
            className={
                props.currentlyPlaying
                    ? `${style.song} nowPlaying card-clear`
                    : `${style.song} card-clear`
            }
            onClick={() => props.playSong(props.song.id)}
        >
            <div className={style.imageWrapper}>
                <img src={props.song.art} alt='Album cover' />
            </div>
            <div className={style.details}>
                <div className={`card-title ${style.songName}`}>
                    {props.song.title}
                </div>
                <div className={style.albumArtist}>
                    {props.song.artist.name}
                </div>
            </div>
            {!props.currentlyPlaying && (
                <div className={style.actions}>
                    <SVG
                        className='icon icon-button-smallest'
                        src={require('~images/icons/svg/cross.svg')}
                        title='Remove'
                        description='Remove song from Queue'
                        role='button'
                        onClick={(e) => {
                            e.stopPropagation();
                            console.log(props.queueIndex);
                            props.removeSong(props.queueIndex);
                        }}
                    />
                </div>
            )}
        </li>
    );
};

export default QueueSong;
