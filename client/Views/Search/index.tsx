import React, { useContext, useEffect, useState } from 'react';
import { User } from '~logic/User';
import { searchAlbums, searchArtists, searchSongs } from '~logic/Search';
import { Song } from '~logic/Song';
import { MusicContext } from '~Contexts/MusicContext';
import SongBlock from '~components/SongBlock';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';

import style from './index.styl';
import { Album, flagAlbum } from '~logic/Album';
import AlbumDisplay from '~components/AlbumDisplay';
import { Artist, flagArtist } from '~logic/Artist';
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

    const [songResults, setSongResults] = useState<Song[]>([]);
    const [albumResults, setAlbumResults] = useState<Album[]>([]);
    const [artistResults, setArtistResults] = useState<Artist[]>([]);

    const searchQuery = props.match.params.searchQuery;

    useEffect(() => {
        if (searchQuery != null) {
            searchSongs(searchQuery, props.user.authKey, 10)
                .then((Songs) => {
                    setSongResults(Songs);
                })
                .catch(() => {
                    toast.error(
                        '😞 Something went wrong during the song search.'
                    );
                });
            searchAlbums(searchQuery, props.user.authKey, 6)
                .then((Albums) => {
                    setAlbumResults(Albums);
                })
                .catch(() => {
                    toast.error(
                        '😞 Something went wrong during the album search.'
                    );
                });
            searchArtists(searchQuery, props.user.authKey, 6)
                .then((Artists) => {
                    setArtistResults(Artists);
                })
                .catch(() => {
                    toast.error(
                        '😞 Something went wrong during the artist search.'
                    );
                });
        }
    }, [searchQuery, props.user.authKey]);

    const playSong = (song: Song) => {
        const songIndex = songResults.findIndex((o) => o.id === song.id);
        musicContext.startPlayingWithNewQueue(songResults, songIndex);
    };

    const handleFlagArtist = (artistID: string, favorite: boolean) => {
        flagArtist(artistID, favorite, props.user.authKey)
            .then(() => {
                const newArtists = artistResults.map((artist) => {
                    if (artist.id === artistID) {
                        artist.flag = favorite;
                    }
                    return artist;
                });
                setArtistResults(newArtists);
                if (favorite) {
                    return toast.success('Artist added to favorites');
                }
                toast.success('Artist removed from favorites');
            })
            .catch(() => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding artist to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing artist from favorites.'
                    );
                }
            });
    };

    const handleFlagAlbum = (albumID: string, favorite: boolean) => {
        flagAlbum(albumID, favorite, props.user.authKey)
            .then(() => {
                const newAlbums = albumResults.map((album) => {
                    if (album.id === albumID) {
                        album.flag = favorite;
                    }
                    return album;
                });
                setAlbumResults(newAlbums);
                if (favorite) {
                    return toast.success('Album added to favorites');
                }
                toast.success('Album removed from favorites');
            })
            .catch(() => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding album to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing album from favorites.'
                    );
                }
            });
    };

    if (!searchQuery) {
        return (
            <div className={style.searchPage}>
                <div>Search for something!</div>
            </div>
        );
    }

    return (
        <div className={style.searchPage}>
            <div className={style.query}>
                Search:
                <span className={style.queryText}>{`"${searchQuery}"`}</span>
            </div>
            <section className={style.resultSection}>
                <h2>Songs</h2>
                {!songResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`song-list ${style.resultList}`}>
                    {songResults.length === 0 && 'No results :('}

                    {songResults.map((song) => {
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
            </section>
            <section className={style.resultSection}>
                <h2>Albums</h2>
                {!albumResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`album-grid ${style.resultList}`}>
                    {albumResults.length === 0 && 'No results :('}

                    {albumResults.map((album) => {
                        return (
                            <AlbumDisplay
                                album={album}
                                className={style.album}
                                flagAlbum={handleFlagAlbum}
                                key={album.id}
                            />
                        );
                    })}
                </div>
            </section>
            <section className={style.resultSection}>
                <h2>Artists</h2>
                {!artistResults && (
                    <ReactLoading color='#FF9D00' type={'bubbles'} />
                )}
                <div className={`artist-grid ${style.resultList}`}>
                    {artistResults.length === 0 && 'No results :('}

                    {artistResults.map((artist) => {
                        return (
                            <ArtistDisplay
                                artist={artist}
                                className={style.artist}
                                flagArtist={handleFlagArtist}
                                key={artist.id}
                            />
                        );
                    })}
                </div>
            </section>
        </div>
    );
};

export default SearchView;
