import React from 'react';
import { getAlbums } from '../../logic/Artist';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import { Album } from '../../logic/Album';
import { Link } from 'react-router-dom';

interface ArtistProps {
    user: User;
    match: {
        params: {
            artistID: number;
        };
    };
}

interface ArtistState {
    error: Error | AmpacheError;
    albums: Album[];
    albumLoading: boolean;
    songsLoading: boolean;
}

export default class ArtistView extends React.Component<
    ArtistProps,
    ArtistState
> {
    constructor(props) {
        super(props);

        this.state = {
            error: null,
            albums: null,
            albumLoading: true,
            songsLoading: true
        };
    }

    componentDidMount() {
        if (this.props.match.params.artistID != null) {
            getAlbums(
                this.props.match.params.artistID,
                this.props.user.authKey,
                'http://localhost:8080'
            )
                .then((data) => {
                    this.setState({ albums: data, albumLoading: false });
                })
                .catch((error) => {
                    this.setState({ albumLoading: false, error });
                });
        }
    }

    render() {
        console.log('RENDER');
        if (this.state.albumLoading) {
            return (
                <div className='artistPage'>
                    <span>Loading...</span>
                </div>
            );
        }
        if (this.state.error) {
            return (
                <div className='artistPage'>
                    <span>Error: {this.state.error.message}</span>
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
                    {this.state.albums.map((album) => {
                        return (
                            <Link
                                key={album.id}
                                to={`/album/${album.id}`}
                                className='album'
                            >
                                <div className='imageContainer'>
                                    <img src={album.art} />
                                </div>
                                <span>{album.name}</span>
                            </Link>
                        );
                    })}
                </div>
            </div>
        );
    }
}
