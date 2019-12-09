import React from 'react';
import ReactDOM from 'react-dom';
import { EventEmitter } from 'events';
import InputModal from './types/InputModal';
import PlaylistSelector from './types/PlaylistSelector';
import { AuthKey } from '../logic/Auth';

export enum ModalType {
    InputModal,
    PlaylistSelectorModal
}

interface ModalProps {
    parent: HTMLElement;
    modalName: string;
    modalType: ModalType;
    authKey?: AuthKey;
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

const Modal = (props: ModalProps) => {
    if (
        props.modalType === ModalType.PlaylistSelectorModal &&
        props.authKey == undefined
    ) {
        throw new Error('Type PlaylistSelectorModal requires AuthKey');
    }

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
        <div className='imperativeModal' onClick={close}>
            Loading...
        </div>,
        props.parent
    );

    return new Promise((resolve: (value: string | number) => void) => {
        if (props.cancelList) {
            props.cancelList.on(() => {
                close();
            });
        }

        const returnData = (data: string | number) => {
            close();
            return resolve(data);
        };

        if (props.modalType === ModalType.InputModal) {
            ReactDOM.render(
                <InputModal
                    modalName={props.modalName}
                    returnData={returnData}
                />,
                props.parent
            );
        } else if (props.modalType === ModalType.PlaylistSelectorModal) {
            ReactDOM.render(
                <PlaylistSelector
                    returnData={returnData}
                    authKey={props.authKey}
                />,
                props.parent
            );
        }
    });
};

export function useModal() {
    const { current: cancelList } = React.useRef(new CancelList());
    cancelList.use();
    return React.useCallback((opts: ModalProps) => {
        return Modal({ ...opts, cancelList });
    }, []);
}
