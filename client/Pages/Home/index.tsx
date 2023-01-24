import React, { useEffect, useState } from 'react';
import { Album, getRandomAlbums } from '~logic/Album';
import { User } from '~logic/User';
import AmpacheError from '../../logic/AmpacheError';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import style from './index.styl';
import AlbumDisplay from '~components/AlbumDisplay';

interface HomePageProps {
    user: User;
}

const HomePage: React.FC<HomePageProps> = (props) => {
    const [randomAlbums, setRandomAlbums] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getRandomAlbums(props.user.username, 6, props.user.authKey)
            .then((albums: Album[]) => {
                setRandomAlbums(albums);
                setLoading(false);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting random albums.');
                setError(error);
            });
    }, [props.user.authKey, props.user.username]);

    if (error) {
        return (
            <div className={style.homePage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (loading) {
        return <ReactLoading color='#FF9D00' type={'bubbles'} />;
    }
    return (
        <div className={style.homePage}>
            <section>
                <h1>Random Albums</h1>
                <div className={`album-grid ${style.randomAlbums}`}>
                    {!randomAlbums && (
                        <ReactLoading color='#FF9D00' type={'bubbles'} />
                    )}
                    {randomAlbums.map((album) => (
                        <AlbumDisplay albumId={album.id} key={album.id} />
                    ))}
                </div>
            </section>
        </div>
    );
};

export default HomePage;
