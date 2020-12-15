import React from 'react';
import { Link } from 'react-router-dom';
import { Artist } from '~logic/Artist';
import Rating from '~components/Rating/';

import style from './index.module.styl';

interface ArtistDisplayProps {
    artist: Artist;
    playSongFromAlbum?: (albumID: number) => void;
    className?: string;
}

const ArtistDisplay: React.FC<ArtistDisplayProps> = (props: ArtistDisplayProps) => {
    return (
        <>
            <Link
                to={`/artist/${props.artist.id}`}
                className={`${style.artistDisplayContainer} ${props.className}`}
            >
                <div className={style.artistDisplay}>
                    <div className={style.imageContainer}>
                        <img
                            src={props.artist.art}
                            alt={`Photo of ${props.artist.name}`}
                        />
                    </div>
                    <div className={style.rating}>
                        <Rating />
                    </div>
                    <span className={style.artistName}>{props.artist.name}</span>
                </div>
            </Link>
        </>
    );
};

export default ArtistDisplay;
