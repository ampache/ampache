import React, { useEffect, useState } from 'react';
import { Album, getRandomAlbums } from '~logic/Album';
import AmpacheError from '../../logic/AmpacheError';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import AlbumDisplay from '~components/AlbumDisplay';

const HomePage = () => {
    const [randomAlbums, setRandomAlbums] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getRandomAlbums(6)
            .then((albums: Album[]) => {
                setRandomAlbums(albums);
                setLoading(false);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting random albums.');
                setError(error);
            });
    }, []);

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (loading) {
        return <ReactLoading color='#FF9D00' type={'bubbles'} />;
    }
    return (
        <div className={'paddedPage'}>
            <section>
                <h1>Random Albums</h1>
                <div className={`album-grid`}>
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
