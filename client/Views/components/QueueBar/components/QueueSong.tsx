import React from 'react';
import { Song } from '../../../../logic/Song';
import { Link } from 'react-router-dom';

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
            <div className='details'>
                <div className='songName'>{props.song.title}</div>
                <div className='albumArtist'>
                    <Link
                        to={`/artist/${props.song.artist.id}`}
                        onClick={(e) => {
                            e.stopPropagation();
                        }}
                    >
                        {props.song.artist.name}
                    </Link>
                    <span className='gap'>-</span>
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
