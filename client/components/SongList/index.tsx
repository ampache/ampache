import React, { memo, useContext } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { SongRow } from '~components/SongRow';

import style from './index.styl';

interface SongListProps {
    songIds: string[];
    showArtist?: boolean;
    showAlbum?: boolean;
    inPlaylistID?: string;
    inAlbumID?: string;
}

// eslint-disable-next-line react/display-name
const SongList: React.FC<SongListProps> = memo(
    ({ inPlaylistID, showAlbum, showArtist, songIds }) => {
        const musicContext = useContext(MusicContext);

        const handleStartPlaying = (startSongId: string) => {
            const queueIndex = songIds.findIndex((o) => o === startSongId);
            console.log('asdf', queueIndex, startSongId, songIds);
            musicContext.startPlayingWithNewQueue(songIds, queueIndex);
        };

        return (
            <>
                <ul className={'striped-list'}>
                    {songIds.map((songId: string, i) => {
                        return (
                            <SongRow
                                trackNumber={(i + 1).toString()}
                                songId={songId}
                                showArtist={showArtist}
                                showAlbum={showAlbum}
                                inPlaylistID={inPlaylistID}
                                startPlaying={handleStartPlaying}
                                key={songId}
                                className={style.songRow}
                            />
                        );
                    })}
                </ul>
            </>
        );
    },
    (prevProps, nextProps) => {
        return (
            prevProps.songIds.length === nextProps.songIds.length &&
            prevProps.inPlaylistID === nextProps.inPlaylistID
        );
    }
);

export default SongList;
