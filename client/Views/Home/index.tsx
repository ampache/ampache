import React, { useContext, useEffect, useState } from 'react';
import { Album, getRandomAlbums } from '../../logic/Album';
import AlbumDisplay from '../components/AlbumDisplay';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { MusicContext } from '../../Contexts/MusicContext';
import { playSongFromAlbum } from '../../Helpers/playAlbumHelper';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

interface HomeViewProps {
    user: User;
}

const HomeView: React.FC<HomeViewProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [randomAlbums, setRandomAlbums] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getRandomAlbums(props.user.username, 6, props.user.authKey)
            .then((albums: Album[]) => {
                setRandomAlbums(albums);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting random albums.');
                setError(error);
            });
    }, [props.user.authKey, props.user.username]);

    if (error) {
        return (
            <div className='albumPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    return (
        <div className='homePage'>
            <section>
                <h1>Random Albums</h1>
                <div className='randomAlbums'>
                    {!randomAlbums && (
                        <ReactLoading color='#FF9D00' type={'bubbles'} />
                    )}
                    {randomAlbums?.map((theAlbum) => {
                        return (
                            <AlbumDisplay
                                album={theAlbum}
                                playSongFromAlbum={(albumID, random) => {
                                    playSongFromAlbum(
                                        albumID,
                                        random,
                                        props.user.authKey,
                                        musicContext
                                    );
                                }}
                                key={theAlbum.id}
                            />
                        );
                    })}
                </div>
            </section>
        </div>
    );
};

export default HomeView;
