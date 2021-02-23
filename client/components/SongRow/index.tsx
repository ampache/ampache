import React, { useContext } from 'react';
import SVG from 'react-inlinesvg';
import { Song } from '~logic/Song';
import { Link } from 'react-router-dom';
import SimpleRating from '~components/SimpleRating';
import { MusicContext } from '~Contexts/MusicContext';

import style from './index.styl';

interface SongRowProps {
    song: Song;
    isCurrentlyPlaying: boolean;
    showArtist?: boolean;
    showAlbum?: boolean;
    startPlaying: (song: Song) => void;
    showContext: (event: React.MouseEvent, song: Song) => void;
    flagSong: (songID: string, favorite: boolean) => void;
    className?: string;
}

const SongRow: React.FC<SongRowProps> = (props: SongRowProps) => {
    const musicContext = useContext(MusicContext);

    const formatLabel = (s) => [
        (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s
        //https://stackoverflow.com/a/37770048
    ];

    return (
        <>
            <li
                className={
                    props.isCurrentlyPlaying
                        ? `nowPlaying ${style.songRow} card-clear ${props.className}`
                        : `${style.songRow} card-clear ${props.className} `
                }
                onContextMenu={(e) => props.showContext(e, props.song)}
                onClick={() => {
                    props.startPlaying(props.song);
                }}
                tabIndex={1}
            >
                <div className={style.songDetails}>
                    <div className={style.trackNumber}>{props.song.track}</div>
                    <div className={`card-title ${style.title}`}>
                        {props.song.title}
                    </div>
                    {props.showArtist && (
                        <div
                            className={style.artistContainer}
                            onClick={(e) => e.stopPropagation()}
                        >
                            <Link
                                className={style.artist}
                                to={`/artist/${props.song.artist.id}`}
                            >
                                {props.song.artist.name}
                            </Link>
                        </div>
                    )}
                    {props.showAlbum && (
                        <div
                            className={style.albumContainer}
                            onClick={(e) => e.stopPropagation()}
                        >
                            <Link
                                className={style.album}
                                to={`/album/${props.song.album.id}`}
                                onClick={(e) => e.stopPropagation()}
                            >
                                {props.song.album.name}
                            </Link>
                        </div>
                    )}
                    <div className={style.time}>
                        {formatLabel(props.song.time)}
                    </div>
                </div>

                <div className={style.songActions}>
                    <div
                        className={style.options}
                        onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            props.showContext(e, props.song);
                        }}
                    >
                        <SVG
                            className='icon icon-button-smallest'
                            src={require('~images/icons/svg/more-options-hori.svg')}
                            title='More options'
                            role='button'
                        />
                    </div>

                    <div className={style.rating}>
                        <SimpleRating
                            value={props.song.rating}
                            fav={
                                props.isCurrentlyPlaying
                                    ? musicContext.currentPlayingSong?.flag
                                    : props.song.flag
                            }
                            itemID={props.song.id}
                            setFlag={props.flagSong}
                        />
                    </div>

                    <div className={style.remove}>
                        <SVG
                            className='icon icon-button-smallest'
                            src={require('~images/icons/svg/cross.svg')}
                            title='Delete'
                            description='Delete this song'
                            role='button'
                        />
                    </div>
                </div>
            </li>
        </>
    );
};

export default SongRow;
