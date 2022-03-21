import React, { useContext } from 'react';
import { useGetAlbum } from '~logic/Album';
import { User } from '~logic/User';
import { Link } from 'react-router-dom';
import SongList from '~components/SongList';
import ReactLoading from 'react-loading';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';
import SimpleRating from '~components/SimpleRating';

import style from './index.styl';

interface AlbumPageProps {
    user: User;
    match: {
        params: {
            albumID: string;
        };
    };
}

const AlbumPage: React.FC<AlbumPageProps> = (props: AlbumPageProps) => {
    const musicContext = useContext(MusicContext);
    const albumID = props.match.params.albumID;

    const { data: album, error } = useGetAlbum({ albumID, includeSongs: true });

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
                            type='album'
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

export default AlbumPage;
