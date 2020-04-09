import { Song } from '../logic/Song';
import { Link } from 'react-router-dom';
import React from 'react';

interface SongBlockProps {
    song: Song;
    currentlyPlaying: boolean;
    playSong: (song: Song) => void;
}

const SongBlock = (props: SongBlockProps) => {
    return (
        <div
            onClick={() => props.playSong(props.song)}
            className={(props.currentlyPlaying ? 'playing ' : '') + 'songBlock'}
        >
            <img src={props.song.art} alt='Album Cover' />
            <div className='details'>
                <div className='title'>{props.song.title}</div>
                <div className='bottom'>
                    <Link
                        to={`/album/${props.song.album.id}`}
                        onClick={(e) => {
                            e.stopPropagation();
                        }}
                    >
                        {props.song.album.name}
                    </Link>
                    <Link
                        to={`/artist/${props.song.artist.id}`}
                        onClick={(e) => {
                            e.stopPropagation();
                        }}
                    >
                        {props.song.artist.name}
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default SongBlock;
