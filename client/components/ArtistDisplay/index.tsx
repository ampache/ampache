import React from 'react';
import { Link } from 'react-router-dom';
import { useGetArtist } from '~logic/Artist';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';
import ReactLoading from 'react-loading';

const ArtistDisplay = ({ artistID }: { artistID: string }) => {
    const { data: artist } = useGetArtist({ artistID });

    if (!artist)
        return (
            <Link
                to={`/artist/${artistID}`}
                className={`card ${style.artistDisplayContainer}`}
            >
                <div className={style.artistDisplay}>
                    <ReactLoading />
                </div>
            </Link>
        );
    return (
        <>
            <Link
                to={`/artist/${artistID}`}
                className={`card ${style.artistDisplayContainer}`}
            >
                <div className={style.artistDisplay}>
                    <div className={style.imageContainer}>
                        <img src={artist.art} alt={`Photo of ${artist.name}`} />
                    </div>
                    <div className={style.rating}>
                        <SimpleRating
                            value={artist.rating}
                            fav={artist.flag}
                            itemId={artistID}
                            type='artist'
                        />
                    </div>
                    <span className={`card-title ${style.artistName}`}>
                        {artist.name}
                    </span>
                </div>
            </Link>
        </>
    );
};

export default ArtistDisplay;
