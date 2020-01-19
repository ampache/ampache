import React from 'react';
import { Song } from '../../../../logic/Song';

interface QueueSongProps {
    song: Song;
    currentlyPlaying: boolean;
    onClick: any;
}

const QueueSong: React.FC<QueueSongProps> = (props) => {
    return (
        <li
            className={props.currentlyPlaying ? 'song playing' : 'song'}
            onClick={props.onClick}
        >
            <div className='imageWrapper'>
                <img src={props.song.art} />
            </div>
            <div className='songName'>{props.song.title}</div>
        </li>
    );
};

export default QueueSong;
