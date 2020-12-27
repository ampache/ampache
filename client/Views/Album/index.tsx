import React, { useContext, useEffect, useState } from 'react';
import { Album, getAlbum } from '~logic/Album';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import { Link } from 'react-router-dom';
import SongList from '~components/SongList';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';
import Rating from '~components/Rating/';

import style from "./index.styl"

interface AlbumViewProps {
    user: User;
    match: {
        params: {
            albumID: number;
        };
    };
}

const AlbumView: React.FC<AlbumViewProps> = (props: AlbumViewProps) => {
    const musicContext = useContext(MusicContext);

    const [theAlbum, setTheAlbum] = useState<Album>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.albumID != null) {
            getAlbum(props.match.params.albumID, props.user.authKey, true)
                .then((data) => {
                    setTheAlbum(data);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong getting the album.');
                    setError(error);
                });
        }
    }, [props.match.params.albumID, props.user.authKey]);

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!theAlbum) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div>
            <div className={style.album}>
                <div
                    className={style.imageContainer}
                    onClick={() =>
                        playSongFromAlbum(
                            theAlbum.id,
                            false,
                            props.user.authKey,
                            musicContext
                        )
                    }
                >
                    <img src={theAlbum.art} alt={'Album Cover'} />
                </div>
                <div className={style.details}>
                    <div className={style.rating}><Rating value={theAlbum.rating} fav={theAlbum.flag}/></div>
                    <div className={style.albumName}>{theAlbum.name}</div>
                    <div className={style.artistName}>
                        <Link
                            to={`/artist/${theAlbum.artist.id}`}
                            className={style.artistName}
                        >
                            {theAlbum.artist.name}
                        </Link>
                    </div>
                    <div className={style.albumMeta}>{theAlbum.year} - {theAlbum.tracks.length} tracks</div>
                </div>
            </div>
            <div className={style.songs}>
                <SongList
                    songData={theAlbum.tracks}
                    authKey={props.user.authKey}
                />
            </div>
        </div>
    );
};

export default AlbumView;
