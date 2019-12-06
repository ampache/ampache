import React, { MutableRefObject, useEffect, useRef, useState } from 'react';
import ReactDOM from 'react-dom';
import closeWindowIcon from '/images/icons/svg/close-window.svg';
import { PLAYERSTATUS } from '../../enum/PlayerStatus';

interface PlaylistSelectorParams {
    parent: MutableRefObject<any>;
    modalName: string;
}

const InputModal = async (props: PlaylistSelectorParams) => {
    function escFunction(event) {
        if (event.keyCode === 27) {
            close();
        }
    }

    document.addEventListener('keydown', escFunction, false);

    function close() {
        document.removeEventListener('keydown', escFunction, false);
        ReactDOM.unmountComponentAtNode(props.parent.current);
    }

    ReactDOM.render(
        //TODO: Make pretty and merge with PlaylistSelector... this doesn't work right now
        <div
            className='inputModal'
            onClick={() => {
                close();
            }}
        >
            Loading...
        </div>,
        props.parent.current
    );

    return new Promise(async (resolve: (value?: string) => void) => {
        const selector = (
            <div
                className='inputModal'
                onClick={() => {
                    close();
                }}
            >
                <div
                    className='content'
                    onClick={(e) => {
                        e.stopPropagation();
                    }}
                >
                    <div className='header'>
                        <div className='title'>{props.modalName}</div>
                        <img
                            className='close'
                            src={closeWindowIcon}
                            alt='Close'
                            onClick={() => close()}
                        />
                    </div>
                    <form>
                        <input placeholder={props.modalName} />
                    </form>
                    <div>
                        <button onClick={select}>Submit</button>
                    </div>
                </div>
            </div>
        );

        function select() {
            close();
            return resolve('');
        }

        ReactDOM.render(selector, props.parent.current);
    });
};

export default InputModal;
