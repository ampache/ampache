import React from 'react';
import {
    MusicPlayerContextChildProps,
    withMusicPlayerContext
} from '../MusicPlayerContext';
import { PLAYERSTATUS } from '../enum/PlayerStatus';

interface MusicPlayerProps {
    global: MusicPlayerContextChildProps;
}

class MusicPlayer extends React.PureComponent<MusicPlayerProps> {
    render() {
        return (
            <>
                {this.props.children}
                <div className='musicPlayer'>
                    {this.props.global.playerStatus === PLAYERSTATUS.STOPPED ||
                    this.props.global.playerStatus === PLAYERSTATUS.PAUSED ? (
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/play.png'
                            alt='Play'
                            onClick={this.props.global.playPause}
                        />
                    ) : (
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/pause--v2.png'
                            alt='Pause'
                            onClick={this.props.global.playPause}
                        />
                    )}
                </div>
            </>
        );
    }
}

export default withMusicPlayerContext(MusicPlayer);
