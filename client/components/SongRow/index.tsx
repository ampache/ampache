import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Song } from '~logic/Song';
import { Link } from 'react-router-dom';
import heartIcon from '/images/icons/svg/heart.svg';

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

const Index: React.FC<SongRowProps> = (props: SongRowProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible, setCoords }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    const minutes: number = Math.floor(props.song.time / 60);
    const seconds: string = (props.song.time % 60).toString();
    const paddedSeconds: string =
        seconds.length === 1 ? seconds + '0' : seconds;

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
                    className={style.verticalMenu}
                    onClick={(e) => {
                        showContextMenu(e);
                    }}
                />
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
                    <img src={heartIcon} alt='Favorite song' />
                </span>
                <span className='title'>{props.song.title}</span>
                {props.showArtist && (
                    <div onClick={(e) => e.stopPropagation()}>
                        <Link
                            className='artist'
                            to={`/artist/${props.song.artist.id}`}
                        >
                            {props.song.artist.name}
                        </Link>
                    </div>
                )}
                {props.showAlbum && (
                    <div onClick={(e) => e.stopPropagation()}>
                        <Link
                            className='album'
                            to={`/album/${props.song.album.id}`}
                        >
                            {props.song.album.name}
                        </Link>
                    </div>
                )}

                <span className='time'>
                    {minutes}:{paddedSeconds}
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

export default Index;
