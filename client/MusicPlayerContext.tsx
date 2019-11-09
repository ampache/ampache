import React from 'react';
import {PLAYERSTATUS} from "./enum/PlayerStatus";
import {Howl, Howler} from 'howler';

const MusicPlayerContext = React.createContext({});

interface MusicPlayerContextState {
    playing: PLAYERSTATUS,
    howlID: any
    howlObject: Howl
}

export class MusicPlayerContextProvider extends React.Component<any, MusicPlayerContextState> {
    state = {
        playing: PLAYERSTATUS.STOPPED,
        howlID: null,
        howlObject: null
    };

    playPause = () => {
        if(this.state.playing === PLAYERSTATUS.PLAYING) {
            this.state.howlObject.pause();
            this.setState({playing: PLAYERSTATUS.PAUSED})
        } else if(this.state.playing === PLAYERSTATUS.PAUSED) {
            this.state.howlObject.play();
            this.setState({playing: PLAYERSTATUS.PLAYING})
        }
    };

    startPlaying = (playURL: string) => {
        const howl = new Howl({
            src: [playURL],
            format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
            onload: () => {
                const howlID = howl.play();
                console.log("Loaded", howlID);
                this.setState({
                    playing: PLAYERSTATUS.PLAYING,
                    howlObject: howl,
                    howlID: howlID,
                });
            },
            onloaderror: (id, err) => {
                this.setState({
                });
                console.log("ERROR", err)
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
                    startPlaying: this.startPlaying,
                }}
            >
                {this.props.children}
            </MusicPlayerContext.Provider>
        )
    }
}

// create the consumer as higher order component
export const withMusicPlayerContext = ChildComponent => props => (
    <MusicPlayerContext.Consumer>
        {
            context => <ChildComponent {...props} global={context}/>
        }
    </MusicPlayerContext.Consumer>
);