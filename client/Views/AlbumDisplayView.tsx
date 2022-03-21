import React, { useContext } from 'react';
import { AuthKey } from '~logic/Auth';
import ReactLoading from 'react-loading';
import { Album } from '~logic/Album';
import style from '~Pages/Search/index.styl';
import AlbumDisplay from '~components/AlbumDisplay';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';

interface AlbumDisplayViewProps {
    albums: Album[];
    authKey: AuthKey;
}

const AlbumDisplayView = (props: AlbumDisplayViewProps) => {
    const musicContext = useContext(MusicContext);

    const albums = props.albums;

    if (albums === null) {
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
            {albums.length === 0 && 'No results :('}

            {albums.map((album) => {
                return (
                    <AlbumDisplay
                        album={album}
                        className={style.album}
                        playSongFromAlbum={handlePlaySong}
                        key={album.id}
                    />
                );
            })}
        </>
    );
};

export default AlbumDisplayView;
