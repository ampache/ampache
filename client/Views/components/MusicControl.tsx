import React, { useContext, useState } from 'react';
import { PLAYERSTATUS } from '../../enum/PlayerStatus';
import { MusicContext } from '../../Contexts/MusicContext';
import InputRange from 'react-input-range';

import listIcon from '/images/icons/svg/list.svg';
import CurrentPlaying from './CurrentPlaying';

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
                <div className='buttons'>
                    <div className='previousSong'>
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/back--v2.png'
                            alt='Back'
                            onClick={() => {
                                musicContext.playPrevious();
                            }}
                            className={
                                musicContext.songQueueIndex <= 0
                                    ? 'disabled'
                                    : ''
                            }
                        />
                    </div>
                    <div className='playPause'>
                        {musicContext.playerStatus === PLAYERSTATUS.STOPPED ||
                        musicContext.playerStatus === PLAYERSTATUS.PAUSED ? (
                            <img
                                src='https://img.icons8.com/flat_round/50/000000/play.png'
                                alt='Play'
                                onClick={musicContext.playPause}
                            />
                        ) : (
                            <img
                                src='https://img.icons8.com/flat_round/50/000000/pause--v2.png'
                                alt='Pause'
                                onClick={musicContext.playPause}
                            />
                        )}
                    </div>
                    <div className='nextSong'>
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/circled-chevron-right.png'
                            alt='Next'
                            onClick={() => {
                                musicContext.playNext();
                            }}
                            className={
                                musicContext.songQueueIndex ==
                                musicContext.songQueue.length - 1
                                    ? 'disabled'
                                    : ''
                            }
                        />
                    </div>
                </div>
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
                    disabled={musicContext.currentPlayingSong == undefined}
                    allowSameValues={true}
                    maxValue={musicContext.currentPlayingSong?.time ?? 0}
                    minValue={0}
                    value={isSeeking ? seekPosition : musicContext.songPosition}
                />
            </div>
            <div className='queueIcon' onClick={props.toggleQueueBar}>
                <img src={listIcon} alt={'Show Queue'} />
            </div>
        </div>
    );
};

export default MusicControl;
