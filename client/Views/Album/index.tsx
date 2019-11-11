import React from 'react';
import { Album, getAlbum, getAlbumSongs } from '../../logic/Album';
import { User } from '../../logic/User';
import { Song } from '../../logic/Song';
import {
    MusicPlayerContextChildProps,
    withMusicPlayerContext
} from '../../MusicPlayerContext';
import AmpacheError from '../../logic/AmpacheError';

interface AlbumProps {
    user: User;
    match: {
        params: {
            albumID: number;
        };
    };
    global: MusicPlayerContextChildProps;
}

interface AlbumState {
    theAlbum: Album;
    songs: Song[];
    error: Error | AmpacheError;
    albumLoading: boolean;
    songsLoading: boolean;
}

class AlbumView extends React.Component<AlbumProps, AlbumState> {
    constructor(props) {
        super(props);

        this.state = {
            theAlbum: null,
            songs: [],
            error: null,
            albumLoading: true,
            songsLoading: true
        };
    }

    componentDidMount() {
        if (this.props.match.params.albumID != null) {
            getAlbum(
                this.props.match.params.albumID,
                this.props.user.authKey,
                'http://localhost:8080'
            )
                .then((data) => {
                    this.setState({ theAlbum: data, albumLoading: false });
                })
                .catch((error) => {
                    this.setState({ albumLoading: false, error });
                });

            getAlbumSongs(
                this.props.match.params.albumID,
                this.props.user.authKey,
                'http://localhost:8080'
            )
                .then((songs) => {
                    this.setState({ songs, songsLoading: false });
                })
                .catch((error) => {
                    this.setState({ songsLoading: false, error });
                });
        }
    }

    render() {
        if (this.state.albumLoading) {
            return (
                <div className='albumPage'>
                    <span>Loading...</span>
                </div>
            );
        }
        if (this.state.error) {
            return (
                <div className='albumPage'>
                    <span>Error: {this.state.error.message}</span>
                </div>
            );
        }
        return (
            <div className='albumPage'>
                <div className='details'>
                    <div className='imageContainer'>
                        <img
                            src={this.state.theAlbum.art}
                            alt={'Album Cover'}
                        />
                    </div>
                    Name: {this.state.theAlbum.name}
                </div>
                <div className='songs'>
                    {this.state.songsLoading && 'Loading Songs...'}
                    {!this.state.songsLoading &&
                        this.state.songs.map((song: Song) => {
                            return (
                                <div
                                    onClick={() =>
                                        this.props.global.startPlaying(song.url)
                                    }
                                    key={song.id}
                                >
                                    {song.title}
                                </div>
                            );
                        })}
                </div>
            </div>
        );
    }
}

export default withMusicPlayerContext(AlbumView);
