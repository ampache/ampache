import React, { useContext, useState } from 'react';
import { updateArtistInfo, updateArtistArt, useGetArtist } from '~logic/Artist';
import AmpacheError from '~logic/AmpacheError';
import { MusicContext } from '~Contexts/MusicContext';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import { generateSongsFromArtist } from '~logic/Playlist_Generate';
import Button, { ButtonColors, ButtonSize } from '~components/Button';
import SimpleRating from '~components/SimpleRating';

import * as style from './index.styl';
import AlbumDisplay from '~components/AlbumDisplay';
import { useParams } from 'react-router-dom';

const ArtistPage = () => {
    const musicContext = useContext(MusicContext);
    const { artistID } = useParams();

    const [error, setError] = useState<Error | AmpacheError>(null);

    const { data: artist } = useGetArtist({
        artistID,
        includeAlbums: true,
        options: {
            onError: (err) => {
                setError(err);
            }
        }
    });

    const playRandomArtistSongs = () => {
        generateSongsFromArtist(artist.id)
            .then((songs) => {
                const songIds = songs.map((song) => song.id);
                songIds.sort(() => Math.random() - 0.5);

                if (songIds.length === 0) throw new Error('No songs?');
                musicContext.startPlayingWithNewQueue(songIds);
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
        updateArtistArt(artist.id, true)
            .then(() => {
                toast.success('Art Updated Successfully');
            })
            .catch((error) => {
                toast.error(
                    `😞 Something went wrong updating artist art. ${error}`
                );
            });
        updateArtistInfo(artist.id)
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
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    return (
        <div className={'paddedPage'}>
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
                                itemId={artist.id}
                                type='artist'
                            />
                        </div>
                        <div className={`card-title ${style.name}`}>
                            {artist.name}
                        </div>
                        <div className={style.summary}>{artist.summary}</div>
                        <div>
                            <Button
                                onClick={playRandomArtistSongs}
                                size={ButtonSize.medium}
                                color={ButtonColors.green}
                                text='Shuffle'
                            />
                        </div>
                    </div>
                </div>
            )}
            <div className={`album-grid`}>
                {!artist && <ReactLoading color='#FF9D00' type={'bubbles'} />}
                {artist &&
                    artist.albums.map((album) => (
                        <AlbumDisplay albumId={album.id} key={album.id} />
                    ))}
            </div>
        </div>
    );
};

export default ArtistPage;
