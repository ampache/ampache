import React from 'react';
import { PLAYERSTATUS } from './enum/PlayerStatus';
import { Howl, Howler } from 'howler';

interface MusicPlayerContextState {
    playerStatus: PLAYERSTATUS;
}

export interface MusicPlayerContextChildProps {
    //TODO: is there no better way?
    playerStatus: PLAYERSTATUS.STOPPED;
    playPause: () => {};
    startPlaying: (url: string) => {};
}

const MusicPlayerContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED,
    playPause: () => {},
    startPlaying: (url: string) => {}
});

export class MusicPlayerContextProvider extends React.Component<
    { children: JSX.Element[] },
    MusicPlayerContextState
> {
    private readonly playPause: () => void;
    private readonly startPlaying: (playURL: string) => void;
    private howl: Howl;
    constructor(props) {
        super(props);
        this.state = {
            playerStatus: PLAYERSTATUS.STOPPED
        };
        this.howl = null;

        this.playPause = () => {
            if (this.state.playerStatus === PLAYERSTATUS.PLAYING) {
                this.howl.pause();
                this.setState({ playerStatus: PLAYERSTATUS.PAUSED });
            } else if (this.state.playerStatus === PLAYERSTATUS.PAUSED) {
                this.howl.play();
                this.setState({ playerStatus: PLAYERSTATUS.PLAYING });
            }
        };

        this.startPlaying = (playURL: string) => {
            if (
                this.state.playerStatus === PLAYERSTATUS.PLAYING ||
                this.state.playerStatus === PLAYERSTATUS.PAUSED
            ) {
                this.howl.stop();
            }
            this.howl = new Howl({
                src: [playURL],
                format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
                onload: () => {
                    this.howl.play();
                    this.setState({
                        playerStatus: PLAYERSTATUS.PLAYING
                    });
                },
                onloaderror: (id, err) => {
                    this.setState({});
                    console.log('ERROR', err);
                    Howler.unload();
                }
            });
        };
    }

    render() {
        return (
            <MusicPlayerContext.Provider
                value={{
                    ...this.state,
                    playPause: this.playPause,
                    startPlaying: this.startPlaying
                }}
            >
                {this.props.children}
            </MusicPlayerContext.Provider>
        );
    }
}

// create the consumer as higher order component
export const withMusicPlayerContext = (ChildComponent) => (props) => (
    <MusicPlayerContext.Consumer>
        {(context) => <ChildComponent {...props} global={context} />}
    </MusicPlayerContext.Consumer>
);
