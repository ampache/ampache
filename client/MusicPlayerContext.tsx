import React from 'react';
import { PLAYERSTATUS } from './enum/PlayerStatus';
import { Howl, Howler } from 'howler';

interface MusicPlayerContextState {
    howlID: any;
    howlObject: Howl;
    playerStatus: PLAYERSTATUS;
}

export interface MusicPlayerContextChildProps {
    //TODO: is there no better way?
    playerStatus: PLAYERSTATUS.STOPPED;
    howlID: null;
    howlObject: null;
    playPause: () => {};
    startPlaying: (url: string) => {};
}

const MusicPlayerContext = React.createContext({});

export class MusicPlayerContextProvider extends React.Component<
    any,
    MusicPlayerContextState
> {
    state = {
        playerStatus: PLAYERSTATUS.STOPPED,
        howlID: null,
        howlObject: null
    };

    playPause = () => {
        if (this.state.playerStatus === PLAYERSTATUS.PLAYING) {
            this.state.howlObject.pause();
            this.setState({ playerStatus: PLAYERSTATUS.PAUSED });
        } else if (this.state.playerStatus === PLAYERSTATUS.PAUSED) {
            this.state.howlObject.play();
            this.setState({ playerStatus: PLAYERSTATUS.PLAYING });
        }
    };

    startPlaying = (playURL: string) => {
        if (
            this.state.playerStatus === PLAYERSTATUS.PLAYING ||
            this.state.playerStatus === PLAYERSTATUS.PAUSED
        ) {
            this.state.howlObject.stop();
        }
        const howl = new Howl({
            src: [playURL],
            format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
            onload: () => {
                const howlID = howl.play();
                console.log('Loaded', howlID);
                this.setState({
                    playerStatus: PLAYERSTATUS.PLAYING,
                    howlObject: howl,
                    howlID: howlID
                });
            },
            onloaderror: (id, err) => {
                this.setState({});
                console.log('ERROR', err);
                Howler.unload();
            }
        });
    };

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
