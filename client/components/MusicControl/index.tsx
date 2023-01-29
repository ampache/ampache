import React, { useContext, useState } from 'react';
import SVG from 'react-inlinesvg';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { MusicContext } from '~Contexts/MusicContext';
import { Slider } from '@mui/material';
import CurrentPlaying from '~components/CurrentPlaying';
import CurrentPlayingArt from '~components/CurrentPlayingArt';
import SimpleRating from '~components/SimpleRating';
import SliderControl from '~components/MusicControl/components/SliderControl';
import Cookies from 'js-cookie';

import * as style from './index.styl';
import { useMusicStore } from '~store';
import { useGetSong } from '~logic/Song';
import shallow from 'zustand/shallow';
import { SongTime } from '~components/MusicControl/components/SongTime';

import previousTrackIcon from '~images/icons/svg/previous-track.svg';
import playIcon from '~images/icons/svg/play.svg';
import pauseIcon from '~images/icons/svg/pause.svg';
import nextTrackIcon from '~images/icons/svg/next-track.svg';
import shuffleIcon from '~images/icons/svg/shuffle.svg';
import repeatIcon from '~images/icons/svg/repeat.svg';
import moreOptionsIcon from '~images/icons/svg/more-options-hori.svg';
import volumeUpIcon from '~images/icons/svg/volume-up.svg';

const MusicControl = () => {
    const musicContext = useContext(MusicContext);

    const { songQueue, songQueueIndex, playerStatus } = useMusicStore(
        (state) => ({
            songQueue: state.songQueue,
            songQueueIndex: state.songQueueIndex,
            playerStatus: state.playerStatus
        }),
        shallow
    );

    const currentPlayingSongId = songQueue[songQueueIndex];

    const [oldVolume, setOldVolume] = useState(musicContext.volume);

    const { data: currentPlayingSong } = useGetSong(currentPlayingSongId, {
        enabled: false
    });
    return (
        <div className={`${style.musicControl}`}>
            <CurrentPlayingArt />

            <CurrentPlaying />
            <div className={style.ratingBarContainer}>
                <div className={style.ratingBar}>
                    <SimpleRating
                        value={currentPlayingSong?.rating ?? 0}
                        fav={currentPlayingSong?.flag}
                        itemId={currentPlayingSong?.id}
                        type='song'
                    />
                </div>
            </div>

            <SliderControl />
            <SongTime endTime={currentPlayingSong?.time} />

            <div className={style.controls}>
                <div>
                    <SVG
                        src={previousTrackIcon}
                        title='Previous'
                        description='Play previous song'
                        role='button'
                        aria-disabled={songQueueIndex <= 0}
                        onClick={() => {
                            musicContext.playPrevious();
                        }}
                        className={`
                            icon icon-button 
                            ${songQueueIndex <= 0 ? style.disabled : ''}
                        `}
                    />
                </div>
                <div className={style.playPause}>
                    {playerStatus === PLAYERSTATUS.STOPPED ||
                    playerStatus === PLAYERSTATUS.PAUSED ? (
                        <SVG
                            src={playIcon}
                            title='Play'
                            description='Resume music'
                            role='button'
                            aria-disabled={currentPlayingSong == undefined}
                            onClick={musicContext.playPause}
                            className={`
                                icon icon-button 
                                ${
                                    currentPlayingSong == undefined
                                        ? style.disabled
                                        : ''
                                }
                            `}
                        />
                    ) : (
                        <SVG
                            src={pauseIcon}
                            title='Pause'
                            description='Pause music'
                            role='button'
                            onClick={musicContext.playPause}
                            aria-disabled={currentPlayingSong == undefined}
                            className={`
                                icon icon-button 
                                ${
                                    currentPlayingSong == undefined
                                        ? style.disabled
                                        : ''
                                }
                            `}
                        />
                    )}
                </div>
                <div>
                    <SVG
                        src={nextTrackIcon}
                        title='Next'
                        description='Play next song'
                        role='button'
                        aria-disabled={songQueueIndex == songQueue.length - 1}
                        onClick={() => {
                            musicContext.playNext();
                        }}
                        className={`
                            icon icon-button 
                            ${
                                songQueueIndex == songQueue.length - 1
                                    ? style.disabled
                                    : ''
                            }
                        `}
                    />
                </div>
            </div>

            <div className={style.secondaryControls}>
                <div>
                    <SVG
                        src={shuffleIcon}
                        title='Shuffle'
                        description='Shuffle queued songs'
                        role='button'
                        onClick={() => {
                            // TODO: shuffle;
                        }}
                        className='icon icon-button'
                    />
                </div>

                <div>
                    <SVG
                        src={repeatIcon}
                        title='Repeat'
                        description='Repeat the current song'
                        onClick={() => {
                            // TODO: repeat;
                        }}
                        className='icon icon-button'
                    />
                </div>

                <div>
                    <SVG
                        src={moreOptionsIcon}
                        title='More options'
                        role='button'
                        onClick={() => {
                            // TODO: open more options menu;
                        }}
                        className='icon icon-button'
                    />
                </div>
            </div>

            <div className={style.volumeSlide}>
                <SVG
                    src={volumeUpIcon}
                    title='Mute'
                    description='Mute the music'
                    role='button'
                    onClick={() => {
                        if (musicContext.volume > 0) {
                            musicContext.setVolume(0);
                            Cookies.set('volume', 0);
                            return;
                        }
                        musicContext.setVolume(oldVolume);
                        Cookies.set('volume', oldVolume);
                    }}
                    className='icon icon-button'
                />
                <Slider
                    name='volume'
                    onChange={(_, value: number) => {
                        musicContext.setVolume(value);
                    }}
                    onChangeCommitted={(_, value: number) => {
                        setOldVolume(value);
                        Cookies.set('volume', value);
                    }}
                    max={100}
                    min={0}
                    value={musicContext.volume}
                />
            </div>
        </div>
    );
};

export default MusicControl;
