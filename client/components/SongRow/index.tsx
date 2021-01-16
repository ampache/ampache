import React from 'react';
import SVG from 'react-inlinesvg';
import useContextMenu from 'react-use-context-menu';
import { Song } from '~logic/Song';
import { Link } from 'react-router-dom';
import Rating from '~components/Rating/';

interface SongRowProps {
    song: Song;
    isCurrentlyPlaying: boolean;
    showArtist?: boolean;
    showAlbum?: boolean;
    removeFromPlaylist?: (trackID: number) => void;
    addToPlaylist?: (trackID: number) => void;
    addToQueue: (next: boolean) => void;
    startPlaying: () => void;
    flagSong: (songID: number, favorite: boolean) => void;
    className?: string;
}
import style from './index.styl';

const SongRow: React.FC<SongRowProps> = (props: SongRowProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible, setCoords }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    const formatLabel = (s) => [
        (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s
        //https://stackoverflow.com/a/37770048
    ];

    const showContextMenu = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setCoords([e.clientX, e.clientY]);
        setVisible(true);
    };

    return (
        <>
            <li
                className={
                    props.isCurrentlyPlaying
                        ? `${style.playing} ${style.songRow} ${props.className}`
                        : `${style.songRow} ${props.className} `
                }
                {...bindTrigger}
                onClick={props.startPlaying}
                tabIndex={1}
            >
                <span
                    className={style.actions}
                    onClick={(e) => {
                        showContextMenu(e);
                    }}
                >
                    <SVG
                        className='icon-button'
                        src={require('~images/icons/svg/more-options-hori.svg')}
                        title='More options'
                        role='button'
                    />
                </span>
                <span
                    className={
                        props.song.flag
                            ? `${style.heartIconWrapper} ${style.fav}`
                            : style.heartIconWrapper
                    }
                    onClick={(e) => {
                        e.stopPropagation();
                        props.flagSong(props.song.id, !props.song.flag);
                    }}
                >
                    <SVG
                        className='icon-button'
                        src={require('~images/icons/svg/heart-full.svg')}
                        title='Favorite this song'
                        role='button'
                    />
                </span>

                <span className={style.songDetails}>
                    <span className={style.title}>{props.song.title}</span>
                    {props.showArtist && (
                        <span
                            className={style.artistContainer}
                            onClick={(e) => e.stopPropagation()}
                        >
                            <Link
                                className={style.artist}
                                to={`/artist/${props.song.artist.id}`}
                            >
                                {props.song.artist.name}
                            </Link>
                        </span>
                    )}
                    {props.showAlbum && (
                        <span
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
                        </span>
                    )}
                </span>

                <span className={style.rating}>
                    <Rating
                        value={props.song.rating}
                        fav={props.song.flag}
                        song={props.song}
                        flagSong={props.flagSong}
                    />
                </span>

                <span className={style.time}>
                    {formatLabel(props.song.time)}
                </span>

                <span className={style.remove}>
                    <SVG
                        className='icon-button'
                        src={require('~images/icons/svg/cross.svg')}
                        title='Delete'
                        description='Delete this song'
                        role='button'
                    />
                </span>
            </li>
            <div {...bindMenu} className='contextMenu'>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.addToQueue(true);
                    }}
                >
                    Play Next
                </div>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.addToQueue(false);
                    }}
                >
                    Add to Queue
                </div>
                {props.showArtist && (
                    <Link
                        {...bindMenuItems}
                        to={`/artist/${props.song.artist.id}`}
                    >
                        Go To Artist
                    </Link>
                )}
                {props.showAlbum && (
                    <Link
                        {...bindMenuItems}
                        to={`/album/${props.song.album.id}`}
                    >
                        Go To Album
                    </Link>
                )}
                {props.removeFromPlaylist && (
                    <div
                        {...bindMenuItems}
                        onClick={() => {
                            setVisible(false);
                            props.removeFromPlaylist(props.song.id);
                        }}
                    >
                        Remove From Playlist
                    </div>
                )}
                {props.addToPlaylist && (
                    <div
                        {...bindMenuItems}
                        onClick={() => {
                            setVisible(false);
                            props.addToPlaylist(props.song.id);
                        }}
                    >
                        Add to Playlist
                    </div>
                )}
            </div>
        </>
    );
};

export default SongRow;
