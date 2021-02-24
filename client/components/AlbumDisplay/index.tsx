import React, { useCallback, useState } from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { Album, flagAlbum } from '~logic/Album';
import SimpleRating from '~components/SimpleRating';
import { toast } from '~node_modules/react-toastify';
import { AuthKey } from '~logic/Auth';

import style from './index.styl';

interface AlbumDisplayProps {
    album: Album;
    playSongFromAlbum: (albumID: string, random: boolean) => void;
    authKey: AuthKey;
    className?: string;
}

const AlbumDisplay: React.FC<AlbumDisplayProps> = (
    props: AlbumDisplayProps
) => {
    const [album, setAlbum] = useState(props.album);

    const handleFlagAlbum = useCallback(
        (albumID: string, favorite: boolean) => {
            flagAlbum(albumID, favorite, props.authKey)
                .then(() => {
                    const newAlbum = { ...album, flag: favorite };
                    setAlbum(newAlbum);
                    if (favorite) {
                        return toast.success('Album added to favorites');
                    }
                    toast.success('Album removed from favorites');
                })
                .catch(() => {
                    if (favorite) {
                        toast.error(
                            '😞 Something went wrong adding album to favorites.'
                        );
                    } else {
                        toast.error(
                            '😞 Something went wrong removing album from favorites.'
                        );
                    }
                });
        },
        [album, props.authKey]
    );

    return (
        <div className={`card ${style.albumDisplay} ${props.className}`}>
            <div className={style.imageContainer}>
                <img src={album.art} alt='Album cover' />
                <div
                    className={`${style.albumActions}`}
                    onClick={(e) => e.preventDefault()}
                >
                    <Link
                        to={`/album/${album.id}`}
                        className={`${style.action} ${style.viewAlbum}`}
                    >
                        View album
                    </Link>
                    <span
                        onClick={() => {
                            props.playSongFromAlbum(album.id, false);
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
                    value={album.rating}
                    fav={album.flag}
                    itemID={album.id}
                    setFlag={handleFlagAlbum}
                />
            </div>
            <div className={style.details}>
                <div className={style.albumInfo}>
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
                    <div className={style.albumMeta}>
                        {album.year} - {album.tracks} tracks
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AlbumDisplay;
