import React, { useContext, useEffect, useState } from 'react';
import { User } from '~logic/User';
import { searchAlbums, searchArtists, searchSongs } from '~logic/Search';
import { Song } from '~logic/Song';
import AmpacheError from '~logic/AmpacheError';
import { MusicContext } from '~Contexts/MusicContext';
import SongBlock from '~components/SongBlock';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import style from './index.styl';
import { Album } from '~logic/Album';
import AlbumDisplay from '~components/AlbumDisplay';
import { Artist } from '~logic/Artist';
import ArtistDisplay from '~components/ArtistDisplay';

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
    const [artistResults, setArtistResults] = useState<Artist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const searchQuery = props.match.params.searchQuery;

    useEffect(() => {
        if (searchQuery != null) {
            searchSongs(searchQuery, props.user.authKey, 10)
                .then((Songs) => {
                    setSongResults(Songs);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
            searchAlbums(searchQuery, props.user.authKey, 6)
                .then((Albums) => {
                    setAlbumResults(Albums);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
            searchArtists(searchQuery, props.user.authKey, 6)
                .then((Artists) => {
                    setArtistResults(Artists);
                })
                .catch((error) => {
                    toast.error('😞 Something went wrong during the search.');
                    setError(error);
                });
        }
    }, [searchQuery, props.user.authKey]);

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

    if (!searchQuery) {
        return (
            <div className={style.searchPage}>
                <div>Search for something!</div>
            </div>
        );
    }

    return (
        <div className={style.searchPage}>
            Search: {searchQuery}
            <div className={style.resultSection}>
                <h2>Songs</h2>
                {!songResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`song-list ${style.resultList}`}>
                    {songResults?.length === 0 && 'No results :('}

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
                                className={style.song}
                            />
                        );
                    })}
                </div>
            </div>
            <div className={style.resultSection}>
                <h2>Albums</h2>
                {!albumResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`album-grid ${style.resultList}`}>
                    {albumResults?.length === 0 && 'No results :('}

                    {albumResults?.map((album) => {
                        return (
                            <AlbumDisplay
                                album={album}
                                className={style.album}
                                key={album.id}
                            />
                        );
                    })}
                </div>
            </div>
            <div className={style.resultSection}>
                <h2>Artists</h2>
                {!artistResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`artist-grid ${style.resultList}`}>
                    {artistResults?.length === 0 && 'No results :('}

                    {artistResults?.map((artist) => {
                        return (
                            <ArtistDisplay
                                artist={artist}
                                className={style.artist}
                                key={artist.id}
                            />
                        );
                    })}
                </div>
            </div>
        </div>
    );
};

export default SearchView;
