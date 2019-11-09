import React from 'react'
import {getRandomAlbums, Album, getAlbum, getAlbumSongs} from '../../logic/Album';
import AlbumDisplay from '../components/AlbumDisplay';
import {User} from "../../logic/User";
import {Song} from "../../logic/Song";
import MusicPlayer from "../MusicPlayer";
import {MusicPlayerContextProvider, withMusicPlayerContext} from "../../MusicPlayerContext";

interface HomeProps {
    user: User;
    global: MusicPlayerContextProvider
}

interface HomeState {
    theAlbum: Album,
    songs: Song[],
    error: string,
    albumLoading: boolean
    songsLoading: boolean
}

class AlbumView extends React.Component<HomeProps, HomeState> {

    constructor(props) {
        super(props);

        this.state = {
            theAlbum: null,
            songs: [],
            error: null,
            albumLoading: true,
            songsLoading: true
        };

        if (props.match.params.albumID != null) {
            getAlbum(props.match.params.albumID, this.props.user.authCode, "http://localhost:8080").then((theAlbum: Album) => {
                this.setState({theAlbum, albumLoading: false});
            }).catch((error) => {
                this.setState({albumLoading: false, error})
            });

            getAlbumSongs(props.match.params.albumID, this.props.user.authCode, "http://localhost:8080").then((songs: Song[]) => {
                this.setState({songs, songsLoading: false});
            }).catch((error) => {
                this.setState({songsLoading: false, error})
            });
        }

        this.onSongClick = this.onSongClick.bind(this);
    }

    onSongClick(url: string) {
        this.props.global.startPlaying(url);
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
                    <span>Error: {this.state.error}</span>
                </div>)
        }
        console.log(this.state.theAlbum);
        return (
            <div className='albumPage'>
                <div className='details'>
                    <div className='imageContainer'>
                        <img src={this.state.theAlbum.art} />
                    </div>
                    Name: {this.state.theAlbum.name}
                </div>
                <div className='songs'>
                    {this.state.songsLoading && "Loading Songs..."}
                    {!this.state.songsLoading &&
                    this.state.songs.map((song: Song) => {
                        return (
                            <div onClick={() => this.onSongClick(song.url)} key={song.id}>
                                {song.title}
                            </div>)
                    })
                    }
                </div>
            </div>
        );
    }
}

export default withMusicPlayerContext(AlbumView);