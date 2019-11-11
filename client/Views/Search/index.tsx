import React from 'react';
import { User } from '../../logic/User';
import {
    MusicPlayerContextChildProps,
    withMusicPlayerContext
} from '../../MusicPlayerContext';
import { searchSongs } from '../../logic/Search';
import { Song } from '../../logic/Song';
import AmpacheError from '../../logic/AmpacheError';
import { Link } from 'react-router-dom';

interface SearchProps {
    user: User;
    match: {
        params: {
            searchQuery: string;
        };
    };
    global: MusicPlayerContextChildProps;
}

interface SearchHome {
    error: Error | AmpacheError;
    searchLoading: boolean;
    searchResults: Song[];
}

class SearchView extends React.PureComponent<SearchProps, SearchHome> {
    constructor(props) {
        super(props);

        this.state = {
            error: null,
            searchLoading: true,
            searchResults: null
        };

        this.onSongClick = this.onSongClick.bind(this);
    }

    componentDidMount() {
        if (this.props.match.params.searchQuery != null) {
            searchSongs(
                this.props.match.params.searchQuery,
                this.props.user.authKey,
                'http://localhost:8080'
            )
                .then((Songs) => {
                    this.setState({
                        searchLoading: false,
                        searchResults: Songs
                    });
                })
                .catch((error) => {
                    this.setState({ searchLoading: false, error });
                });
        }
    }

    componentDidUpdate(prevProps: Readonly<SearchProps>) {
        if (
            this.props.match.params.searchQuery !=
            prevProps.match.params.searchQuery
        ) {
            this.setState({ searchLoading: true });
            searchSongs(
                this.props.match.params.searchQuery,
                this.props.user.authKey,
                'http://localhost:8080'
            )
                .then((Songs) => {
                    this.setState({
                        searchLoading: false,
                        searchResults: Songs
                    });
                })
                .catch((error) => {
                    this.setState({ searchLoading: false, error });
                });
        }
    }

    onSongClick(url: string) {
        this.props.global.startPlaying(url);
    }

    render() {
        if (this.state.searchLoading) {
            return (
                <div className='searchPage'>
                    <span>Searching...</span>
                </div>
            );
        }
        if (this.state.error) {
            return (
                <div className='searchPage'>
                    <span>Error: {this.state.error.message}</span>
                </div>
            );
        }
        return (
            <div className='searchPage'>
                Search: {this.props.match.params.searchQuery}
                <div className='songs'>
                    {this.state.searchResults.map((song: Song) => {
                        return (
                            <div
                                onClick={() =>
                                    this.props.global.startPlaying(song.url)
                                }
                                key={song.id}
                                className='songBlock'
                            >
                                <img src={song.art} alt='Album Cover' />
                                <div className='details'>
                                    <div className='title'>{song.title}</div>
                                    <div className='bottom'>
                                        <Link
                                            to={`/album/${song.album.id}`}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                            }}
                                        >
                                            <span className='album'>
                                                {song.album.name}
                                            </span>
                                        </Link>
                                        <Link
                                            to={`/artist/${song.artist.id}`}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                            }}
                                        >
                                            <span className='artist'>
                                                {song.artist.name}
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    }
}

export default withMusicPlayerContext(SearchView);
