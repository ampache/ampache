import React from 'react';
import ReactDOM from 'react-dom';
import closeWindowIcon from '/images/icons/svg/close-window.svg';
import { EventEmitter } from 'events';

interface PlaylistSelectorParams {
    parent: HTMLElement;
    modalName: string;
    cancelList?: CancelList;
}

class CancelList extends EventEmitter {
    on(handler: () => void) {
        super.on('cleanup', handler);
    }
    use(dependencies: unknown[] = []) {
        React.useEffect(() => () => this.emit('cleanup'), dependencies);
    }
}

const InputModal = (props: PlaylistSelectorParams) => {
    function close() {
        document.removeEventListener('keydown', escFunction, false);
        ReactDOM.unmountComponentAtNode(props.parent);
    }

    function escFunction(event) {
        if (event.keyCode === 27) {
            close();
        }
    }

    document.addEventListener('keydown', escFunction, false);

    ReactDOM.render(
        //TODO: Make pretty and merge with PlaylistSelector
        <div className='inputModal' onClick={close}>
            Loading...
        </div>,
        props.parent
    );

    return new Promise((resolve: (value: string) => void) => {
        if (props.cancelList) {
            props.cancelList.on(() => {
                close();
            });
        }

        const returnData = (data: string) => {
            close();
            return resolve(data);
        };

        const selector = (
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
                            onClick={close}
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

        ReactDOM.render(selector, props.parent);
    });
};

export function useInputModal() {
    const { current: cancelList } = React.useRef(new CancelList());
    cancelList.use();
    return React.useCallback((opts: PlaylistSelectorParams) => {
        return InputModal({ ...opts, cancelList });
    }, []);
}
