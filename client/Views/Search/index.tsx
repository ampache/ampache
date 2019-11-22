import React, { useContext, useEffect, useState } from 'react';
import { User } from '../../logic/User';
import { searchSongs } from '../../logic/Search';
import { Song } from '../../logic/Song';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';
import { MusicContext } from '../../Contexts/MusicContext';

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
            searchSongs(
                props.match.params.searchQuery,
                props.user.authKey,
                'http://localhost:8080'
            )
                .then((Songs) => {
                    setSearchResults(Songs);
                })
                .catch((error) => {
                    setError(error);
                });
        }
    }, [props.match.params.searchQuery]);

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
                        <div
                            onClick={() =>
                                musicContext.startPlayingWithNewQueue(
                                    song,
                                    searchResults
                                )
                            }
                            key={song.id}
                            className={
                                (musicContext.currentPlayingSong.id === song.id
                                    ? 'playing '
                                    : '') + 'songBlock'
                            }
                        >
                            <img src={song.art} alt='Album Cover' />
                            <div className='details'>
                                <div className='title'>{song.title}</div>
                                <div className='bottom'>
                                    <Link
                                        to={`/album/${song.album.id}`}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                        }}
                                    >
                                        {song.album.name}
                                    </Link>
                                    <Link
                                        to={`/artist/${song.artist.id}`}
                                        onClick={(e) => {
                                            e.stopPropagation();
                                        }}
                                    >
                                        {song.artist.name}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default SearchView;
