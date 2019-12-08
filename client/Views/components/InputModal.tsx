import React, { MutableRefObject } from 'react';
import ReactDOM from 'react-dom';
import closeWindowIcon from '/images/icons/svg/close-window.svg';

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
        //TODO: Make pretty and merge with PlaylistSelector
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
        const returnData = (data: string) => {
            close();
            return resolve(data);
        };

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
                        <input
                            id='inputModal-inputField'
                            placeholder={props.modalName}
                        />
                    </form>
                    <div>
                        <button
                            onClick={() =>
                                returnData(
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

        ReactDOM.render(selector, props.parent.current);
    });
};

export default InputModal;
