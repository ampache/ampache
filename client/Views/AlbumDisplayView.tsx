import React, { useContext, useEffect, useState } from 'react';
import { AuthKey } from '~logic/Auth';
import { toast } from 'react-toastify';
import ReactLoading from 'react-loading';
import { Album, flagAlbum } from '~logic/Album';
import style from '~Pages/Search/index.styl';
import AlbumDisplay from '~components/AlbumDisplay';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';

interface AlbumDisplayViewProps {
    albums: Album[];
    authKey: AuthKey;
}

const AlbumDisplayView: React.FC<AlbumDisplayViewProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [albumsState, setAlbumsState] = useState(null);

    useEffect(() => {
        setAlbumsState(props.albums);
    }, [props.albums]);

    const handleFlagAlbum = (albumID: string, favorite: boolean) => {
        flagAlbum(albumID, favorite, props.authKey)
            .then(() => {
                const newAlbums = albumsState.map((album) => {
                    if (album.id === albumID) {
                        album.flag = favorite;
                    }
                    return album;
                });
                setAlbumsState(newAlbums);
                if (favorite) {
                    return toast.success('Album added to favorites');
                }
                toast.success('Album removed from favorites');
            })
            .catch(() => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding album to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing album from favorites.'
                    );
                }
            });
    };
    if (albumsState === null) {
        return (
            <>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </>
        );
    }

    const handlePlaySong = (albumID: string) => {
        playSongFromAlbum(albumID, false, props.authKey, musicContext);
    };

    return (
        <>
            {albumsState.length === 0 && 'No results :('}

            {albumsState.map((album) => {
                return (
                    <AlbumDisplay
                        album={album}
                        className={style.album}
                        flagAlbum={handleFlagAlbum}
                        playSongFromAlbum={handlePlaySong}
                        key={album.id}
                    />
                );
            })}
        </>
    );
};

export default AlbumDisplayView;
