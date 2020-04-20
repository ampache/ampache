import React, { useContext, useEffect, useState } from 'react';
import { Artist, getArtist } from '~logic/Artist';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import { Album } from '~logic/Album';
import AlbumDisplay from '~components/AlbumDisplay';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { generateSongsFromArtist } from '~logic/Playlist_Generate';

interface ArtistViewProps {
    user: User;
    match: {
        params: {
            artistID: number;
        };
    };
}

const ArtistView: React.FC<ArtistViewProps> = (props: ArtistViewProps) => {
    const musicContext = useContext(MusicContext);

    const [artist, setArtist] = useState<Artist>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.artistID != null) {
            getArtist(props.match.params.artistID, props.user.authKey, true)
                .then((data) => {
                    setArtist(data);
                })
                .catch((error) => {
                    toast.error(
                        '😞 Something went wrong getting information about the artist.'
                    );
                    setError(error);
                });
        }
    }, [props.match.params.artistID, props.user.authKey]);

    const playRandomArtistSongs = () => {
        generateSongsFromArtist(artist.id, props.user.authKey)
            .then((songs) => {
                console.log(songs);

                //TODO: When working
            })
            .catch((error) => {
                toast.error(
                    '😞 Something went wrong generating songs from artist.'
                );
                setError(error);
            });
    };

    if (error) {
        return (
            <div className='artistPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    return (
        <div className='artistPage'>
            {!artist && <ReactLoading color='#FF9D00' type={'bubbles'} />}
            {artist && (
                <div className='artistInfo'>
                    <div className='imageContainer'>
                        <img src={artist.art} alt={`Photo of ${artist.name}`} />
                    </div>
                    <div className='details'>
                        <div className='name'>{artist.name}</div>
                        <div className='summary'>{artist.summary}</div>
                        <div className='playRandom'>
                            <button onClick={playRandomArtistSongs}>
                                Play
                            </button>
                        </div>
                    </div>
                </div>
            )}
            <h1>Albums</h1>
            <div className='albums'>
                {!artist && <ReactLoading color='#FF9D00' type={'bubbles'} />}
                {artist &&
                    artist.albums.map((theAlbum) => {
                        return (
                            <AlbumDisplay
                                album={theAlbum}
                                playSongFromAlbum={(albumID, random) => {
                                    playSongFromAlbum(
                                        theAlbum.id,
                                        random,
                                        props.user.authKey,
                                        musicContext
                                    );
                                }}
                                showGoToAlbum={false}
                                key={theAlbum.id}
                            />
                        );
                    })}
            </div>
        </div>
    );
};

export default ArtistView;
