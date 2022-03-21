import React from 'react';
import { Link } from 'react-router-dom';
import { Artist } from '~logic/Artist';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';

interface ArtistDisplayProps {
    artist: Artist;
    className?: string;
}

const ArtistDisplay: React.FC<ArtistDisplayProps> = (
    props: ArtistDisplayProps
) => {
    return (
        <>
            <Link
                to={`/artist/${props.artist.id}`}
                className={`card ${style.artistDisplayContainer} ${props.className}`}
            >
                <div className={style.artistDisplay}>
                    <div className={style.imageContainer}>
                        <img
                            src={props.artist.art}
                            alt={`Photo of ${props.artist.name}`}
                        />
                    </div>
                    <div className={style.rating}>
                        <SimpleRating
                            value={props.artist.rating}
                            fav={props.artist.flag}
                            itemID={props.artist.id}
                            type='artist'
                        />
                    </div>
                    <span className={`card-title ${style.artistName}`}>
                        {props.artist.name}
                    </span>
                </div>
            </Link>
        </>
    );
};

export default ArtistDisplay;
