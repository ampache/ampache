import React from 'react';
import { Link } from 'react-router-dom';
import useContextMenu from 'react-use-context-menu';
import { Artist } from '../../logic/Artist';

interface ArtistDisplayProps {
    artist: Artist;
    playSongFromAlbum?: (albumID: number) => void;
}

const ArtistDisplay: React.FC<ArtistDisplayProps> = (props) => {
    return (
        <>
            <Link
                to={`/artist/${props.artist.id}`}
                className='artistDisplayContainer'
            >
                <div className='artistDisplay'>
                    <div className='imageContainer'>
                        <img
                            src={props.artist.art}
                            alt={`Photo of ${props.artist.name}`}
                        />
                    </div>
                    <span>{props.artist.name}</span>
                </div>
            </Link>
        </>
    );
};

export default ArtistDisplay;
