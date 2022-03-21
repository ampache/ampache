import React, { useContext, useEffect, useState } from 'react';
import {
    Artist,
    getArtist,
    updateArtistInfo,
    updateArtistArt
} from '~logic/Artist';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import { MusicContext } from '~Contexts/MusicContext';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { generateSongsFromArtist } from '~logic/Playlist_Generate';
import Button, { ButtonColors, ButtonSize } from '~components/Button';
import SimpleRating from '~components/SimpleRating';
import AlbumDisplayView from '~Views/AlbumDisplayView';

import style from './index.styl';
import useTraceUpdate from '~Debug/useTraceUpdate';

interface ArtistPageProps {
    user: User;
    match: {
        params: {
            artistID: string;
        };
    };
}

const ArtistPage: React.FC<ArtistPageProps> = (props: ArtistPageProps) => {
    const musicContext = useContext(MusicContext);

    const [artist, setArtist] = useState<Artist>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);
    useTraceUpdate(props, 'Artist');
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
    console.log('ARTISTPAGE');
    const playRandomArtistSongs = () => {
        generateSongsFromArtist(artist.id, props.user.authKey)
            .then((songs) => {
                console.log(songs);
                songs.sort(() => Math.random() - 0.5);

                musicContext.startPlayingWithNewQueue(songs);
                //TODO: When working
            })
            .catch((error) => {
                toast.error(
                    '😞 Something went wrong generating songs from artist.'
                );
                setError(error);
            });
    };

    /*TODO: This is sort of a temp method to allow for easy updates, but in future the client should maybe check for missing data and handle it automatically*/
    const handleArtistUpdate = () => {
        updateArtistArt(artist.id, true, props.user.authKey)
            .then(() => {
                toast.success('Art Updated Successfully');
            })
            .catch((error) => {
                toast.error(
                    `😞 Something went wrong updating artist art. ${error}`
                );
            });
        updateArtistInfo(artist.id, props.user.authKey)
            .then(() => {
                toast.success('Info Updated Successfully');
            })
            .catch((error) => {
                toast.error(
                    `😞 Something went wrong updating artist info. ${error}`
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
                            onClick={handleArtistUpdate}
                        />
                    </div>
                    <div className={style.details}>
                        <div className={style.rating}>
                            <SimpleRating
                                value={artist.rating}
                                fav={artist.flag}
                                itemID={artist.id}
                                type='artist'
                            />
                        </div>
                        <div className={`card-title ${style.name}`}>
                            {artist.name}
                        </div>
                        <div className={style.summary}>{artist.summary}</div>
                        <div className={style.actions}>
                            <Button
                                onClick={playRandomArtistSongs}
                                size={ButtonSize.medium}
                                color={ButtonColors.green}
                                text='Shuffle'
                            />
                        </div>
                        <div className={style.summary}>{artist.summary}</div>
                    </div>
                </div>
            )}
            <div className={`album-grid ${style.albums}`}>
                {!artist && <ReactLoading color='#FF9D00' type={'bubbles'} />}
                {artist && (
                    <AlbumDisplayView
                        albums={artist.albums}
                        authKey={props.user.authKey}
                    />
                )}
            </div>
        </div>
    );
};

export default ArtistPage;
