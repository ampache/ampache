import React from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { Album } from '~logic/Album';
import Rating from '~components/Rating/';
import useContextMenu from 'react-use-context-menu';

import style from './index.styl';

interface AlbumDisplayProps {
    album: Album;
    showGoToAlbum?: boolean;
    playSongFromAlbum?: (albumID: number, random: boolean) => void;
    className?: string;
}

const AlbumDisplay: React.FC<AlbumDisplayProps> = (
    props: AlbumDisplayProps
) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger({
        mouseButton: 0 // left click
    });

    return (
        <Link
            to={`/album/${props.album.id}`}
            className={`${style.albumDisplay} ${props.className}`}
        >
            <div {...bindMenu} className='contextMenu'>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.playSongFromAlbum(props.album.id, false);
                    }}
                >
                    Play first song
                </div>
                {props.album.tracks.length > 1 && (
                    <div
                        {...bindMenuItems}
                        onClick={() => {
                            setVisible(false);
                            props.playSongFromAlbum(props.album.id, true);
                        }}
                    >
                        Play random song
                    </div>
                )}
                <Link
                    {...bindMenuItems}
                    to={`/artist/${props.album.artist.id}`}
                >
                    Go to artist
                </Link>
            </div>

            <div className={style.imageContainer}>
                <img src={props.album.art} alt='Album cover' />
                <div
                    className={`${style.albumActions}`}
                    onClick={(e) => e.preventDefault()}
                >
                    <Link
                        to={`/album/${props.album.id}`}
                        className={style.viewAlbum}
                    >
                        View album
                    </Link>
                    <Link to={'#'}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play.svg')}
                        />
                        Play
                    </Link>
                    <Link to={'#'}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play-next.svg')}
                        />
                        Play next
                    </Link>
                    <Link to={'#'}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play-last.svg')}
                        />
                        Add to queue
                    </Link>
                    <Link {...bindTrigger} to={'#'}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/more-options-hori.svg')}
                        />
                        More options
                    </Link>
                </div>
            </div>
            <div className={style.rating} onClick={(e) => e.preventDefault()}>
                <Rating value={props.album.rating} fav={props.album.flag} />
            </div>
            <div className={style.details}>
                <div className={style.albumInfo}>
                    <div className={style.albumName}>
                        <Link
                            to={`/album/${props.album.id}`}
                            className={style.cardLink}
                        >
                            {props.album.name}
                        </Link>
                    </div>
                    <div className={style.albumArtist}>
                        <Link
                            to={`/artist/${props.album.artist.id}`}
                            className={style.cardLink}
                        >
                            {props.album.artist.name}
                        </Link>
                    </div>
                    <div className={style.albumMeta}>
                        {props.album.year} - {props.album.tracks} tracks
                    </div>
                </div>
            </div>
        </Link>
    );
};

export default AlbumDisplay;
