import React, { memo, useContext, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { Song } from '~logic/Song';
import { SongRow } from '~components/SongRow';

import style from './index.styl';
import { useStore } from '~store';
import AmpacheError from '~logic/AmpacheError';

interface SongListProps {
    songIds: string[];
    showArtist?: boolean;
    showAlbum?: boolean;
    inPlaylistID?: string;
    inAlbumID?: string;
}

const setSong = useStore.getState().startPlayingSong;

// eslint-disable-next-line react/display-name
const SongList: React.FC<SongListProps> = memo(
    ({ inPlaylistID, showAlbum, showArtist, songIds }) => {
        const musicContext = useContext(MusicContext);

        const [error, setError] = useState<Error | AmpacheError>(null);

        const handleStartPlaying = (song: Song) => {
            // const queueIndex = songs.findIndex((o) => o.id === song.id);
            // musicContext.startPlayingWithNewQueue(songs, queueIndex);
            // setSong(song);
        };

        if (error) {
            return (
                <div>
                    <span>Error: {error.message}</span>
                </div>
            );
        }

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
