import React, { useContext, useEffect, useState } from 'react';
import { User } from '~logic/User';
import { searchAlbums, searchSongs } from '~logic/Search';
import { Song } from '~logic/Song';
import AmpacheError from '~logic/AmpacheError';
import { MusicContext } from '~Contexts/MusicContext';
import SongBlock from '~components/SongBlock';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import style from './index.styl';
import { Album } from '~logic/Album';
import AlbumDisplay from '~components/AlbumDisplay';

interface SearchProps {
    user: User;
    match: {
        params: {
            searchQuery: string;
        };
    };
}

const SearchView: React.FC<SearchProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [songResults, setSongResults] = useState<Song[]>(null);
    const [albumResults, setAlbumResults] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.searchQuery != null) {
            searchSongs(props.match.params.searchQuery, props.user.authKey, 10)
                .then((Songs) => {
                    setSongResults(Songs);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
            searchAlbums(props.match.params.searchQuery, props.user.authKey, 6)
                .then((Albums) => {
                    setAlbumResults(Albums);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
        }
    }, [props.match.params.searchQuery, props.user.authKey]);

    const playSong = (song: Song) => {
        musicContext.startPlayingWithNewQueue(song, songResults);
    };

    if (error) {
        return (
            <div className={style.searchPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    return (
        <div className={style.searchPage}>
            Search: {props.match.params.searchQuery}
            <div className={style.songs}>
                <h1>Songs</h1>
                {!songResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={style.songsList}>
                    {songResults?.map((song) => {
                        return (
                            <SongBlock
                                song={song}
                                currentlyPlaying={
                                    musicContext.currentPlayingSong?.id ===
                                    song.id
                                }
                                playSong={playSong}
                                key={song.id}
                                className={style.songBlock}
                            />
                        );
                    })}
                </div>
            </div>
            <div className={style.albums}>
                <h1>Albums</h1>
                {!albumResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={style.albumsList}>
                    {albumResults?.map((album) => {
                        return (
                            <AlbumDisplay
                                album={album}
                                className={style.album}
                            />
                        );
                    })}
                </div>
            </div>
        </div>
    );
};

export default SearchView;
