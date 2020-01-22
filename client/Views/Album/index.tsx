import React, { useContext, useEffect, useState } from 'react';
import { Album, getAlbum } from '../../logic/Album';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';
import SongList from '../components/SongList';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { playSongFromAlbum } from '../../Helpers/playAlbumHelper';
import { MusicContext } from '../../Contexts/MusicContext';

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
            getAlbum(props.match.params.albumID, props.user.authKey)
                .then((data) => {
                    setTheAlbum(data);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong getting the album.');
                    setError(error);
                });
        }
    }, []);

    if (error) {
        return (
            <div className='albumPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!theAlbum) {
        return (
            <div className='albumPage'>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className='albumPage'>
            <div className='album'>
                <div
                    className='imageContainer'
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
                <div className='details'>
                    <div className='albumName'>{theAlbum.name}</div>
                    <Link
                        to={`/artist/${theAlbum.artist.id}`}
                        className='artistName'
                    >
                        {theAlbum.artist.name}
                    </Link>
                </div>
            </div>
            <div className='songs'>
                <SongList
                    inAlbumID={props.match.params.albumID}
                    authKey={props.user.authKey}
                />
            </div>
        </div>
    );
};

export default AlbumView;
