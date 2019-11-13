import React, { useContext, useEffect, useState } from 'react';
import { getAlbums } from '../../logic/Artist';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { Album } from '../../logic/Album';
import { Link } from 'react-router-dom';
import AlbumDisplay from '../components/AlbumDisplay';
import { playSongFromAlbum } from '../Helpers/playAlbumHelper';
import { MusicContext } from '../../MusicContext';

interface ArtistViewProps {
    user: User;
    match: {
        params: {
            artistID: number;
        };
    };
}

const ArtistView: React.FC<ArtistViewProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [albums, setAlbums] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        if (props.match.params.artistID != null) {
            getAlbums(
                props.match.params.artistID,
                props.user.authKey,
                'http://localhost:8080'
            )
                .then((data) => {
                    setAlbums(data);
                })
                .catch((error) => {
                    setError(error);
                });
        }
    }, []);

    if (error) {
        return (
            <div className='artistPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!albums) {
        return (
            <div className='artistPage'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='artistPage'>
            <div className='details'>
                {/*<div className='imageContainer'>*/}
                {/*    <img*/}
                {/*        src={this.state.theArtist.art}*/}
                {/*        alt={'Album Cover'}*/}
                {/*    />*/}
                {/*</div>*/}
                {/*Name: {this.state.theArtist.name}*/}
            </div>
            <h1>Albums</h1>
            <div className='albums'>
                {albums.map((theAlbum) => {
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
                            key={theAlbum.id}
                        />
                    );
                })}
            </div>
        </div>
    );
};

export default ArtistView;
