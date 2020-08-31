import React, { useContext, useEffect, useState } from 'react';
import { Artist, getArtist } from '~logic/Artist';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import AlbumDisplay from '~components/AlbumDisplay/';
import { playSongFromAlbum } from '~Helpers/playAlbumHelper';
import { MusicContext } from '~Contexts/MusicContext';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { generateSongsFromArtist } from '~logic/Playlist_Generate';
import { updateArtistArt } from '~logic/Art';
import Button, { ButtonColors, ButtonSize } from '~components/Button/Button';

import style from './index.module.styl';

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
                songs.sort(() => Math.random() - 0.5);

                musicContext.startPlayingWithNewQueue(songs[0], songs);
                //TODO: When working
            })
            .catch((error) => {
                toast.error(
                    '😞 Something went wrong generating songs from artist.'
                );
                setError(error);
            });
    };

    const handleArtistArtUpdate = () => {
        updateArtistArt(artist.id, true, props.user.authKey)
            .then(() => {
                toast.success('Art Updated Successfully');
            })
            .catch((error) => {
                toast.error(
                    `😞 Something went wrong updating artist art. ${error}`
                );
            });
    };

    if (error) {
        return (
            <div className={style.artistPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    return (
        <div className={style.artistPage}>
            {!artist && <ReactLoading color='#FF9D00' type={'bubbles'} />}
            {artist && (
                <div className={style.artistInfo}>
                    <div className={style.imageContainer}>
                        <img
                            src={artist.art}
                            alt={`Photo of ${artist.name}`}
                            onClick={handleArtistArtUpdate}
                        />
                    </div>
                    <div className={style.details}>
                        <div className={style.name}>{artist.name}</div>
                        <div className={style.summary}>{artist.summary}</div>
                        <div className={style.playRandom}>
                            <Button
                                onClick={playRandomArtistSongs}
                                size={ButtonSize.large}
                                color={ButtonColors.green}
                                text='Play'
                            />
                        </div>
                    </div>
                </div>
            )}
            <h1>Albums</h1>
            <div className={style.albums}>
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
                                className={style.albumDisplayContainer}
                            />
                        );
                    })}
            </div>
        </div>
    );
};

export default ArtistView;
