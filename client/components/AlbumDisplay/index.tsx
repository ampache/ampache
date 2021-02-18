import React from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { Album } from '~logic/Album';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';

interface AlbumDisplayProps {
    album: Album;
    playSongFromAlbum: (albumID: string, random: boolean) => void;
    flagAlbum: (artistID: string, favorite: boolean) => void;
    className?: string;
}

const AlbumDisplay: React.FC<AlbumDisplayProps> = (
    props: AlbumDisplayProps
) => {
    return (
        <div className={`card ${style.albumDisplay} ${props.className}`}>
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
                            props.playSongFromAlbum(props.album.id, false);
                        }}
                        className={style.action}
                    >
                        <SVG
                            className='icon icon-inline'
                            src={require('~images/icons/svg/play.svg')}
                        />
                        Play
                    </span>
                    <span className={style.action}>
                        <SVG
                            className='icon icon-inline'
                            src={require('~images/icons/svg/play-next.svg')}
                        />
                        Play next
                    </span>
                    <span className={style.action}>
                        <SVG
                            className='icon icon-inline'
                            src={require('~images/icons/svg/play-last.svg')}
                        />
                        Add to queue
                    </span>
                    <span className={style.action}>
                        <SVG
                            className='icon icon-inline'
                            src={require('~images/icons/svg/more-options-hori.svg')}
                        />
                        More options
                    </span>
                </div>
            </div>
            <div className={style.rating} onClick={(e) => e.preventDefault()}>
                <SimpleRating
                    value={props.album.rating}
                    fav={props.album.flag}
                    itemID={props.album.id}
                    setFlag={props.flagAlbum}
                />
            </div>
            <div className={style.details}>
                <div className={style.albumInfo}>
                    <Link
                        to={`/album/${props.album.id}`}
                        className={`card-title ${style.albumName}`}
                    >
                        {props.album.name}
                    </Link>
                    <Link
                        to={`/artist/${props.album.artist.id}`}
                        className={style.albumArtist}
                    >
                        {props.album.artist.name}
                    </Link>
                    <div className={style.albumMeta}>
                        {props.album.year} - {props.album.tracks} tracks
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AlbumDisplay;
