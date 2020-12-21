import React, {useState} from 'react';
import SVG from 'react-inlinesvg';
import { Link } from 'react-router-dom';
import { Album } from '~logic/Album';
import Rating from '~components/Rating/';
import ClickAwayListener from '@material-ui/core/ClickAwayListener';

import style from './index.module.styl';

interface AlbumDisplayProps {
    album: Album;
    showGoToAlbum?: boolean;
    playSongFromAlbum?: (albumID: number, random: boolean) => void;
    className?: string;
}

const AlbumDisplay: React.FC<AlbumDisplayProps> = (props: AlbumDisplayProps) => {
    const [active, setActive] = useState(false);
    
    const handleEnter = (e) => {
        setActive(true);       
    }

    const handleLeave = (e) => {
        setActive(false);
    }
    //TODO: React version of this for card link: https://codepen.io/vikas-parashar/pen/qBOwMWj

    return (
        <ClickAwayListener 
            onClickAway={(e) => handleLeave(e)}
            mouseEvent={false}
            disableReactTree={true}
        >
            <div 
                className={`${style.albumDisplay} ${props.className} ${active ? style.active : null}`}
                onClick={(e) => handleEnter(e)}
                onMouseEnter={(e) => handleEnter(e)}
                onMouseLeave={(e) => handleLeave(e)}
            >
                <div className={style.imageContainer}>
                    <img
                        src={props.album.art + '&thumb=true'}
                        alt='Album cover'
                    />
                    <div className={`${style.albumActions}`}>
                        <Link to={`/album/${props.album.id}`}>View album</Link>
                        <Link to={'#'}><SVG className='icon-inline' src={require('~images/icons/svg/play.svg')} alt="Play" /> Play</Link>
                        <Link to={'#'}><SVG className='icon-inline' src={require('~images/icons/svg/play-next.svg')} alt="Play next" /> Play next</Link>
                        <Link to={'#'}><SVG className='icon-inline' src={require('~images/icons/svg/play-last.svg')} alt="Play last" /> Add to queue</Link>
                        <Link to={'#'}><SVG className='icon-inline' src={require('~images/icons/svg/more-options-hori.svg')} alt="More options" /> More options</Link>
                    </div>
                </div>
                <div className={style.rating}>
                    <Rating />
                </div>
                <div className={style.details}>
                    <div className={style.albumInfo}>
                        <div className={style.albumName}>                            
                            <Link to={`/album/${props.album.id}`} className={style.cardLink}>{props.album.name}</Link>
                        </div>
                        <div className={style.albumArtist}>
                            <Link to={`/artist/${props.album.artist.id}`} className={style.cardLink}>{props.album.artist.name}</Link>
                        </div>
                        <div className={style.albumMeta}>{props.album.year} - {props.album.tracks} tracks</div>
                        
                    </div>
                </div>
            </div>
        </ClickAwayListener>
    );
};

export default AlbumDisplay;
