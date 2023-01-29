import React, { useState } from 'react';
import { useGetAlbums } from '~logic/Album';
import ReactLoading from 'react-loading';

import Button, { ButtonColors, ButtonSize } from '~components/Button';
import AlbumDisplay from '~components/AlbumDisplay';

const AlbumsPage = () => {
    const [offset, setOffset] = useState(0);
    const { data: albums, error, isLoading } = useGetAlbums({
        limit: 15,
        offset
    });

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (isLoading) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    if (!albums) {
        return <div>No Albums</div>;
    }

    return (
        <div>
            <h1>Albums</h1>
            <Button
                size={ButtonSize.medium}
                color={ButtonColors.green}
                text='Back'
                onClick={() => {
                    setOffset(offset - 10);
                }}
            />
            <Button
                size={ButtonSize.medium}
                color={ButtonColors.green}
                text='Next'
                onClick={() => {
                    setOffset(offset + 10);
                }}
            />
            <div className='album-grid'>
                {albums.map((album) => (
                    <AlbumDisplay albumId={album.id} key={album.id} />
                ))}
            </div>
        </div>
    );
};

export default AlbumsPage;
