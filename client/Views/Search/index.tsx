import React, { useContext, useEffect, useState } from 'react';
import { User } from '~logic/User';
import { searchSongs } from '~logic/Search';
import { Song } from '~logic/Song';
import AmpacheError from '~logic/AmpacheError';
import { MusicContext } from '~Contexts/MusicContext';
import SongBlock from '~components/SongBlock';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import style from './index.styl';

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

    const [searchResults, setSearchResults] = useState<Song[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.searchQuery != null) {
            searchSongs(props.match.params.searchQuery, props.user.authKey)
                .then((Songs) => {
                    setSearchResults(Songs);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
        }
    }, [props.match.params.searchQuery, props.user.authKey]);

    const playSong = (song: Song) => {
        musicContext.startPlayingWithNewQueue(song, searchResults);
    };

    if (error) {
        return (
            <div className={style.searchPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!searchResults) {
        return (
            <div className={style.searchPage}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className={style.searchPage}>
            Search: {props.match.params.searchQuery}
            <div className={style.songs}>
                {searchResults.map((song: Song) => {
                    return (
                        <SongBlock
                            song={song}
                            currentlyPlaying={
                                musicContext.currentPlayingSong?.id === song.id
                            }
                            playSong={playSong}
                            key={song.id}
                            className={style.songBlock}
                        />
                    );
                })}
            </div>
        </div>
    );
};

export default SearchView;
