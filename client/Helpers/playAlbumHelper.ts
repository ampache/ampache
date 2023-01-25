import { toast } from 'react-toastify';
import { MusicContextInterface } from '~Contexts/MusicContext';
import { getAlbumSongs } from '~logic/Album';

export const playSongFromAlbum = (
    albumID: string,
    randomSong: boolean,
    musicContext: MusicContextInterface
) => {
    getAlbumSongs(albumID)
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
