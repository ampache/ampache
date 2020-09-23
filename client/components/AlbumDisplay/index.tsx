import React from 'react';
import { Link } from 'react-router-dom';
import { Album } from '~logic/Album';
import useContextMenu from 'react-use-context-menu';

import style from './index.module.styl';

interface AlbumDisplayProps {
    album: Album;
    showGoToAlbum?: boolean;
    playSongFromAlbum?: (albumID: number, random: boolean) => void;
    className?: string;
}

const AlbumDisplay: React.FC<AlbumDisplayProps> = (props: AlbumDisplayProps) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    return (
        <>
            <Link
                to={`/album/${props.album.id}`}
                className={`${style.albumDisplayContainer} ${props.className}`}
            >
                <div {...bindTrigger} className={style.albumDisplay}>
                    <div className={style.imageContainer}>
                        <img
                            src={props.album.art + '&thumb=true'}
                            alt='Album Cover'
                        />
                    </div>
                    <span>{props.album.name}</span>
                </div>
            </Link>

            <div {...bindMenu} className='contextMenu'>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.playSongFromAlbum(props.album.id, false);
                    }}
                >
                    Play First Song
                </div>
                {props.album.tracks.length > 1 && (
                    <div
                        {...bindMenuItems}
                        onClick={() => {
                            setVisible(false);
                            props.playSongFromAlbum(props.album.id, true);
                        }}
                    >
                        Play Random Song
                    </div>
                )}
                {(props.showGoToAlbum ?? true) && (
                    <Link
                        {...bindMenuItems}
                        to={`/artist/${props.album.artist.id}`}
                    >
                        Go To Artist
                    </Link>
                )}
            </div>
        </>
    );
};

export default AlbumDisplay;
