import React, { useContext, useEffect, useState } from 'react';
import { Album, flagAlbum, getAlbum } from '~logic/Album';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import { Link } from 'react-router-dom';
import SongList from '~components/SongList';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';

interface AlbumViewProps {
    user: User;
    match: {
        params: {
            albumID: string;
        };
    };
}

const AlbumView: React.FC<AlbumViewProps> = (props: AlbumViewProps) => {
    const musicContext = useContext(MusicContext);

    const [album, setAlbum] = useState<Album>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.albumID != null) {
            getAlbum(props.match.params.albumID, props.user.authKey, true)
                .then((data) => {
                    setAlbum(data);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong getting the album.');
                    setError(error);
                });
        }
    }, [props.match.params.albumID, props.user.authKey]);

    const handleFlagAlbum = (albumID: string, favorite: boolean) => {
        flagAlbum(albumID, favorite, props.user.authKey)
            .then(() => {
                const newAlbum = { ...album };
                newAlbum.flag = favorite;
                setAlbum(newAlbum);
                if (favorite) {
                    return toast.success('Album added to favorites');
                }
                toast.success('Album removed from favorites');
            })
            .catch((err) => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding the album to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing the album from favorites.'
                    );
                }
                setError(err);
            });
    };
    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!album) {
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
                            album.id,
                            false,
                            props.user.authKey,
                            musicContext
                        )
                    }
                >
                    <img src={album.art} alt={'Album Cover'} />
                </div>
                <div className={style.details}>
                    <div className={style.rating}>
                        <SimpleRating
                            value={album.rating}
                            fav={album.flag}
                            itemID={album.id}
                            setFlag={handleFlagAlbum}
                        />
                    </div>
                    <div className={`card-title ${style.albumName}`}>
                        {album.name}
                    </div>
                    <div className={style.artistName}>
                        <Link
                            to={`/artist/${album.artist.id}`}
                            className={style.artistName}
                        >
                            {album.artist.name}
                        </Link>
                    </div>
                    <div className={style.albumMeta}>
                        {album.year} - {album.tracks.length} tracks
                    </div>
                </div>
            </div>
            <div className={style.songs}>
                <SongList
                    songData={album.tracks}
                    authKey={props.user.authKey}
                />
            </div>
        </div>
    );
};

export default AlbumView;
