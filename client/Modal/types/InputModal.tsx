import React from 'react';
import closeWindowIcon from '/images/icons/svg/close-window.svg';

interface PlaylistSelectorParams {
    returnData: (data: string) => void;
    modalName: string;
    close: () => void;
}

const InputModal = (props: PlaylistSelectorParams) => {
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
                <form>
                    <input
                        id='inputModal-inputField'
                        placeholder={props.modalName}
                    />
                </form>
                <div>
                    <button
                        onClick={(): void =>
                            props.returnData(
                                (document.getElementById(
                                    'inputModal-inputField'
                                ) as HTMLInputElement).value
                            )
                        }
                    >
                        Submit
                    </button>
                </div>
            </div>
        </div>
    );
};

export default InputModal;
