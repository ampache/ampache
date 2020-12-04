import React from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { Album } from '~logic/Album';
import Rating from '~components/Rating/';
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
            <div {...bindTrigger} className={`${style.albumDisplay} ${props.className}`}>
                <div className={style.imageContainer}>
                    <img
                        src={props.album.art + '&thumb=true'}
                        alt='Album cover'
                    />
                </div>
                <div className={style.rating}>
                    <Rating />
                </div>
                <div className={style.details}>
                    <div className={style.albumInfo}>
                        <div className={style.albumName}>
                            {props.album.name}
                            <Link to={`/album/${props.album.id}`} className={style.cardLink}></Link>
                        </div>
                        <div className={style.albumArtist}>{props.album.artist.name}</div>
                        <div className={style.albumMeta}>{props.album.year} - {props.album.tracks} tracks</div>
                        
                    </div>
                </div>
                <div className={style.albumActions}>
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play.svg')} alt="Play" />
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play-next.svg')} alt="Play next" />
                    <SVG className='icon-button-small' src={require('~images/icons/svg/play-last.svg')} alt="Play last" />
                    <SVG className='icon-button-small' src={require('~images/icons/svg/more-options-hori.svg')} alt="More options" />
                </div>
            </div>

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
                {(props.showGoToAlbum ?? true) && (
                    <Link
                        {...bindMenuItems}
                        to={`/artist/${props.album.artist.id}`}
                    >
                        Go to artist
                    </Link>
                )}
            </div>
        </>
    );
};

export default AlbumDisplay;
