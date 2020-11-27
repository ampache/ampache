import React, { useContext, useState } from 'react';
import SVG from 'react-inlinesvg';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { MusicContext } from '~Contexts/MusicContext';
import InputRange from 'react-input-range';

import CurrentPlaying from '~components/CurrentPlaying/';

interface MusicControlProps {
    toggleQueueBar: () => void;
}

const MusicControl: React.FC<MusicControlProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [isSeeking, setIsSeeking] = useState(false);
    const [seekPosition, setSeekPosition] = useState(-1);

    return (
        <div className='musicControl'>
            <CurrentPlaying />
            <div className='controls'>
                <InputRange
                    onChange={(value: number) => {
                        setSeekPosition(value);
                    }}
                    onChangeStart={() => {
                        setIsSeeking(true);
                    }}
                    onChangeComplete={(e: number) => {
                        setIsSeeking(false);
                        musicContext.seekSongTo(e);
                    }}
                    formatLabel={(s) => {
                        return (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s;
                        //https://stackoverflow.com/a/37770048
                    }}
                    disabled={musicContext.currentPlayingSong == undefined}
                    allowSameValues={true}
                    maxValue={musicContext.currentPlayingSong?.time ?? 0}
                    minValue={0}
                    value={isSeeking ? seekPosition : musicContext.songPosition}
                />
                <div className='buttons'>
                    <div className='shuffle'>
                        <SVG
                            src={require('~images/icons/svg/shuffle.svg')}
                            alt='Shuffle'
                            onClick={() => {
                                // TODO: shuffle;
                            }}
                            className={`
                                ${'icon-button'} 
                            `}
                        />
                    </div>
                    <div className='repeat'>
                        <SVG
                            src={require('~images/icons/svg/repeat.svg')}
                            alt='Repeat'
                            onClick={() => {
                                // TODO: repeat;
                            }}
                            className={`
                                ${'icon-button'} 
                            `}
                        />
                    </div>
                    <div className='previousSong'>
                        <SVG
                            src={require('~images/icons/svg/previous-track.svg')}
                            alt='Back'
                            onClick={() => {
                                musicContext.playPrevious();
                            }}
                            className={`
                                ${'icon-button'} 
                                ${musicContext.songQueueIndex <= 0
                                    ? 'disabled'
                                    : ''}
                            `}
                        />
                    </div>
                    <div className='playPause'>
                        {musicContext.playerStatus === PLAYERSTATUS.STOPPED ||
                        musicContext.playerStatus === PLAYERSTATUS.PAUSED ? (
                            <SVG
                                src={require('~images/icons/svg/play.svg')}
                                alt='Play'
                                onClick={musicContext.playPause}
                                className={`
                                    ${'icon-button'} 
                                    ${musicContext.songQueueIndex <= 0
                                        ? 'disabled'
                                        : ''}
                                `}
                            />
                        ) : (
                            <SVG
                                src={require('~images/icons/svg/pause.svg')}
                                alt='Pause'
                                onClick={musicContext.playPause}
                                className={`
                                    ${'icon-button'} 
                                    ${musicContext.songQueueIndex <= 0
                                        ? 'disabled'
                                        : ''}
                                `}
                            />
                        )}
                    </div>
                    <div className='nextSong'>
                        <SVG
                            src={require('~images/icons/svg/next-track.svg')}
                            alt='Next'
                            onClick={() => {
                                musicContext.playNext();
                            }}
                            className={`
                                ${'icon-button'} 
                                ${musicContext.songQueueIndex ==
                                musicContext.songQueue.length - 1
                                    ? 'disabled'
                                    : ''}
                            `}
                        />
                    </div>
                    <div className='rating'>
                        <SVG
                            src={require('~images/icons/svg/star-full.svg')}
                            alt='Show ratings'
                            onClick={() => {
                                // TODO: show rating;
                            }}
                            className={`
                                ${'icon-button'} 
                            `}
                        />
                    </div>
                    <div className='moreOptions'>
                        <SVG
                            src={require('~images/icons/svg/more-options.svg')}
                            alt='More options'
                            onClick={() => {
                                // TODO: open more options menu;
                            }}
                            className={`
                                ${'icon-button'} 
                            `}
                        />
                    </div>
                </div>
            </div>
            <div className='volumeSide'>
                <div className='queueIcon' onClick={props.toggleQueueBar}>
                    <SVG className='icon-button' src={require('~images/icons/svg/playlist.svg')} alt={'Show queue'} />
                </div>
                <InputRange
                    name='volume'
                    onChange={(value: number) => {
                        musicContext.setVolume(value);
                    }}
                    maxValue={100}
                    minValue={0}
                    value={musicContext.volume}
                />
            </div>
        </div>
    );
};

export default MusicControl;
