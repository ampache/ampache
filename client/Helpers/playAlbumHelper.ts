import { getAlbumSongs } from '~logic/Album';
import { AuthKey } from '~logic/Auth';
import { toast } from 'react-toastify';
import { MusicContextInterface } from '~Contexts/MusicContext';

export const playSongFromAlbum = (
    albumID: string,
    randomSong: boolean,
    authKey: AuthKey,
    musicContext: MusicContextInterface
) => {
    getAlbumSongs(albumID, authKey)
        .then((songs) => {
            let songIndex = 0;
            if (randomSong) {
                songIndex = Math.floor(Math.random() * songs.length) + 1;
            }
            musicContext.startPlayingWithNewQueue(songs, songIndex);
        })
        .catch((error) => {
            toast.error('😞 Something went playing song from album.');
            console.error(error);
        });
};
