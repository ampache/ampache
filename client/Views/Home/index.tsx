import React from 'react';
import { getRandomAlbums, Album } from '../../logic/Album';
import AlbumDisplay from '../components/AlbumDisplay';
import { User } from '../../logic/User';

interface HomeProps {
    user: User;
}

interface HomeState {
    randomAlbums: Album[];
}

export default class HomeView extends React.PureComponent<
    HomeProps,
    HomeState
> {
    constructor(props) {
        super(props);

        this.state = {
            randomAlbums: []
        };
    }

    componentDidMount() {
        getRandomAlbums(
            this.props.user.username,
            6,
            this.props.user.authKey,
            'http://localhost:8080'
        )
            .then((albums: Album[]) => {
                this.setState({ randomAlbums: albums });
            })
            .catch((e) => {
                //TODO
            });
    }

    render() {
        console.log('HOME');
        return (
            <div className='homePage'>
                <section>
                    <h1>Random Albums</h1>
                    <div className='randomAlbums'>
                        {this.state.randomAlbums.map((album) => {
                            return (
                                <AlbumDisplay album={album} key={album.id} />
                            );
                        })}
                    </div>
                </section>
            </div>
        );
    }
}
