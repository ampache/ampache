import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Song } from '../../logic/Song';
import { Link } from 'react-router-dom';

interface SongRowProps {
    song: Song;
    isCurrentlyPlaying: boolean;
    showArtist?: boolean;
    showAlbum?: boolean;
    removeFromPlaylist?: (trackID: number) => void;
    addToPlaylist?: (trackID: number) => void;
    addToQueue: (next: boolean) => void;
    startPlaying: () => void;
}

const SongRow: React.FC<SongRowProps> = (props: SongRowProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible, setCoords }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    const minutes: number = Math.round(props.song.time / 60);
    const seconds: string = (props.song.time % 60).toString();
    const paddedSeconds: string =
        seconds.length === 1 ? seconds + '0' : seconds;

    const showContextMenu = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setCoords(0, 0);
        setVisible(true);
    };

    return (
        <>
            <li
                className={
                    props.isCurrentlyPlaying ? 'playing songRow' : 'songRow'
                }
                {...bindTrigger}
                onClick={props.startPlaying}
            >
                <span
                    className='verticleMenu'
                    onClick={(e) => {
                        showContextMenu(e);
                    }}
                />
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

export default SongRow;
