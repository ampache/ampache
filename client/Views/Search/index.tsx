import React, { useContext, useEffect, useState } from 'react';
import { User } from '../../logic/User';
import { searchSongs } from '../../logic/Search';
import { Song } from '../../logic/Song';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';
import { MusicContext } from '../../Contexts/MusicContext';
import SongBlock from '../components/SongBlock';

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
                    setError(error);
                });
        }
    }, [props.match.params.searchQuery]);

    const playSong = (song: Song) => {
        musicContext.startPlayingWithNewQueue(song, searchResults);
    };

    if (error) {
        return (
            <div className='searchPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!searchResults) {
        return (
            <div className='searchPage'>
                <span>Searching...</span>
            </div>
        );
    }
    return (
        <div className='searchPage'>
            Search: {props.match.params.searchQuery}
            <div className='songs'>
                {searchResults.map((song: Song) => {
                    return (
                        <SongBlock
                            song={song}
                            currentlyPlaying={
                                musicContext.currentPlayingSong?.id === song.id
                            }
                            playSong={playSong}
                            key={song.id}
                        />
                    );
                })}
            </div>
        </div>
    );
};

export default SearchView;
