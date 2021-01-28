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
                        ? `nowPlaying ${style.songRow} card-clear ${props.className}`
                        : `${style.songRow} card-clear ${props.className} `
                }
                {...bindTrigger}
                onClick={props.startPlaying}
                tabIndex={1}
            >
                <div className={style.songDetails}>
                    <div className={style.trackNumber}>
                        {props.song.track}
                    </div>
                    <div className={style.title}>{props.song.title}</div>
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
                          showContextMenu(e);
                      }}
                    >
                        <SVG
                          className='icon icon-button-smallest'
                          src={require("~images/icons/svg/more-options-hori.svg")}
                          title='More options'
                          role='button'
                        />
                    </div>

                    <div className={style.rating}>
                        <Rating value={props.song.rating} fav={props.song.flag} />
                    </div>

                    <div className={style.remove}>
                        <SVG
                          className='icon icon-button-smallest'
                          src={require("~images/icons/svg/cross.svg")}
                          title='Delete'
                          description='Delete this song'
                          role='button'
                        />
                    </div>
                </div>
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
