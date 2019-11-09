import React from "react";
import {withMusicPlayerContext} from "../MusicPlayerContext";
import {PLAYERSTATUS} from "../enum/PlayerStatus";


class MusicPlayer extends React.Component<any, any> {
    constructor(props) {
        super(props);

        this.state = {

        };

    }



    render() {
        return (
            <>
                {this.props.children}
                <div className='musicPlayer'>
                    {(this.props.global.playing === PLAYERSTATUS.STOPPED || this.props.global.playing === PLAYERSTATUS.PAUSED) ?
                        <img src='https://img.icons8.com/flat_round/50/000000/play.png' alt='Play' onClick={this.props.global.playPause}/>
                        : <img src='https://img.icons8.com/flat_round/50/000000/pause--v2.png' alt='Pause'
                               onClick={this.props.global.playPause}/>
                    }
                </div>
            </>
        );
    }
}

export default withMusicPlayerContext(MusicPlayer);