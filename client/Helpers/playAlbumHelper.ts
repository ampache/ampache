import { getAlbumSongs } from '~logic/Album';
import { AuthKey } from '~logic/Auth';

export const playSongFromAlbum = (
    albumID: string,
    randomSong: boolean,
    authKey: AuthKey,
    musicContext
) => {
    //TODO: MusicContext type
    getAlbumSongs(albumID, authKey)
        .then((songs) => {
            let songIndex = 0;
            if (randomSong) {
                songIndex = Math.floor(Math.random() * songs.length) + 1;
            }
            const song = songs[songIndex];
            musicContext.startPlayingWithNewQueue(song, songs);
        })
        .catch((error) => {
            //TODO
        });
};
