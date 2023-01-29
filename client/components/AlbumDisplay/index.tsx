import React, { useContext, useState } from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { useGetAlbum } from '~logic/Album';
import SimpleRating from '~components/SimpleRating';
import { MusicContext } from '~Contexts/MusicContext';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import playIcon from '~images/icons/svg/play.svg';
import playNextIcon from '~images/icons/svg/play-next.svg';
import playLastIcon from '~images/icons/svg/play-last.svg';
import moreOptionsIcon from '~images/icons/svg/more-options-hori.svg';

import * as style from './index.styl';

interface AlbumDisplayProps {
    albumId: string;
    className?: string;
}

const AlbumDisplay = (props: AlbumDisplayProps) => {
    const musicContext = useContext(MusicContext);

    const [optionsVisible, setOptionsVisible] = useState(false);
    const { data: album } = useGetAlbum({
        albumID: props.albumId
    });

    if (!album) return null;
    return (
        <div
            className={`card ${style.albumDisplay} ${props.className}`}
            onMouseOver={() => setOptionsVisible(true)}
            onMouseLeave={() => setOptionsVisible(false)}
        >
            <div className={style.imageContainer}>
                <img src={album.art} alt='Album cover' />
                <div
                    className={`${style.albumActions}`}
                    onClick={(e) => e.preventDefault()}
                >
                    {optionsVisible && (
                        <>
                            <Link
                                to={`/album/${album.id}`}
                                className={`${style.action}`}
                            >
                                View album
                            </Link>
                            <span
                                onClick={() => {
                                    playSongFromAlbum(
                                        album.id,
                                        false,
                                        musicContext
                                    );
                                }}
                                className={style.action}
                            >
                                <SVG
                                    className='icon icon-inline'
                                    src={playIcon}
                                />
                                Play
                            </span>
                            <span className={style.action}>
                                <SVG
                                    className='icon icon-inline'
                                    src={playNextIcon}
                                />
                                Play next
                            </span>
                            <span className={style.action}>
                                <SVG
                                    className='icon icon-inline'
                                    src={playLastIcon}
                                />
                                Add to queue
                            </span>
                            <span className={style.action}>
                                <SVG
                                    className='icon icon-inline'
                                    src={moreOptionsIcon}
                                />
                                More options
                            </span>
                        </>
                    )}
                </div>
            </div>
            <div className={style.rating} onClick={(e) => e.preventDefault()}>
                <SimpleRating
                    value={album.rating}
                    fav={album.flag}
                    itemId={album.id}
                    type='album'
                />
            </div>
            <div className={style.details}>
                <div>
                    <Link
                        to={`/album/${album.id}`}
                        className={`card-title ${style.albumName}`}
                    >
                        {album.name}
                    </Link>
                    <Link
                        to={`/artist/${album.artist.id}`}
                        className={style.albumArtist}
                    >
                        {album.artist.name}
                    </Link>
                    <div>
                        {album.year} - {album.songcount} tracks
                    </div>
                </div>
            </div>
        </div>
    );
};
export default AlbumDisplay;
