import React, { useState } from 'react';
import SVG from 'react-inlinesvg';
import style from './index.styl';

function PlayModeSelector() {

    const playModes = {
        web: {
            name: 'Web Player',
            description: 'Play media directly in the browser',
            enabled: true
        },
        democratic: {
            name: 'Democratic Playlist',
            description: 'Stream a base playlist while allowing users to add and vote for songs',
            enabled: false
        },
        localplay: {
            name: 'Localplay',
            description: 'Control a remote player instance',
            enabled: false
          },
        stream: {
            name: 'Stream',
            description: 'Generate a playlist file for use in another player',
            enabled: false
        },
    };

    const [currentPlayMode, setCurrentPlayMode] = useState('web');
    const [currentPlayModeName, setCurrentPlayModeName] = useState('Web Player');
    const [dropdownVisible, setDropdownVisible] = useState(false);

    const handleDropdown = () => {
        setDropdownVisible(!dropdownVisible);
    };

    const handleSelect = (selectedMode) => {
        setCurrentPlayMode(selectedMode);
        setCurrentPlayModeName(selectedMode.name);
        setDropdownVisible(false);
    };
    
    return(
        <div className={style.currentPlayModeContainer}>
            <div className={style.currentPlayMode} onClick={() => handleDropdown()}>
                <h4>{currentPlayModeName}</h4>
                {dropdownVisible ? (
                    <SVG src={require('~images/icons/svg/up.svg')} />
                ) : (
                    <SVG src={require('~images/icons/svg/down.svg')} />
                )
                }
            </div>
            <div className={`${style.playModeDropdown} ${dropdownVisible ? style.visible : null}`}>
                {Object.keys(playModes).map((playMode, i) => (
                    <div className={`${style.playModeOption} ${playModes[playMode].enabled ? null : style.disabled}`} onClick={() => handleSelect(playModes[playMode]) }>
                        <h4 className={style.playerType}>{playModes[playMode].name}</h4>
                        <div className={style.playerDescription}>{playModes[playMode].description}</div>
                    </div>
                ))}
            </div>
        </div>
    )
}

export default PlayModeSelector;