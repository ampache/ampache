import React, { useState } from 'react';
import closeWindowIcon from '/images/icons/svg/close-window.svg';

interface PlaylistSelectorParams {
    returnData: (data: string) => void;
    modalName: string;
    submitButtonText: string;
    inputInitialValue: string;
    close: () => void;
}

const InputModal = (props: PlaylistSelectorParams) => {
    const [inputValue, setInputValue] = useState(props.inputInitialValue);

    const handleSubmit = () => {
        props.returnData(
            (document.getElementById(
                'inputModal-inputField'
            ) as HTMLInputElement).value
        );
    };

    return (
        <div className='inputModal' onClick={close}>
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
                        onClick={props.close}
                    />
                </div>
                <form onSubmit={handleSubmit}>
                    <input
                        id='inputModal-inputField'
                        placeholder={props.modalName}
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                    />
                    <input type='submit' value={props.submitButtonText} />
                </form>
            </div>
        </div>
    );
};

export default InputModal;
