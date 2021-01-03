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
        <div className={`${style.albumDisplay} ${props.className}`}>
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
                        className={`${style.action} ${style.viewAlbum}`}
                    >
                        View album
                    </Link>
                    <span
                        onClick={() => {
                            props.playSongFromAlbum(props.album.id, false); //TODO: Make playSongFromAlbum actually optional without errors. Also investigate if this click is being registered twice
                        }}
                        className={style.action}
                    >
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play.svg')}
                        />
                        Play
                    </span>
                    <span className={style.action}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play-next.svg')}
                        />
                        Play next
                    </span>
                    <span className={style.action}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/play-last.svg')}
                        />
                        Add to queue
                    </span>
                    <span {...bindTrigger} className={style.action}>
                        <SVG
                            className='icon-inline'
                            src={require('~images/icons/svg/more-options-hori.svg')}
                        />
                        More options
                    </span>
                </div>
            </div>
            <div className={style.rating} onClick={(e) => e.preventDefault()}>
                <Rating value={props.album.rating} fav={props.album.flag} />
            </div>
            <Link to={`/album/${props.album.id}`} className={style.details}>
                <div className={style.albumInfo}>
                    <div className={style.albumName}>{props.album.name}</div>
                    <div className={style.albumArtist}>
                        {/*HTML does not allow nested links, this hack makes it shutup,
                        TODO: See if this can be refactored without this*/}
                        <object type='owo/uwu'>
                            <Link
                                to={`/artist/${props.album.artist.id}`}
                                className={style.cardLink}
                            >
                                {props.album.artist.name}
                            </Link>
                        </object>
                    </div>
                    <div className={style.albumMeta}>
                        {props.album.year} - {props.album.tracks} tracks
                    </div>
                </div>
            </Link>
        </div>
    );
};

export default AlbumDisplay;
