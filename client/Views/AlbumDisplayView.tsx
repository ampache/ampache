import React, { useCallback, useContext, useEffect, useState } from 'react';
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
                        playSongFromAlbum={handlePlaySong}
                        authKey={props.authKey}
                        key={album.id}
                    />
                );
            })}
        </>
    );
};

export default AlbumDisplayView;
