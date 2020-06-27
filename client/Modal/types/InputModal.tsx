import React, { useState } from 'react';
import closeWindowIcon from '/images/icons/svg/close-window.svg';

interface InputModalParams {
    submitButtonText: string;
    inputPlaceholder?: string;
    inputInitialValue?: string;
    inputLabel: string;
    ok?: any; //TODO
    cancel?: any;
}

const InputModal = (props: InputModalParams) => {
    const [inputValue, setInputValue] = useState(props.inputInitialValue ?? '');

    const handleSubmit = (e) => {
        e.preventDefault();
        const inputValue = (document.getElementById(
            'inputModal-inputField'
        ) as HTMLInputElement).value;
        if (inputValue != (props.inputInitialValue ?? '')) {
            props.ok(inputValue);
        }
    };
    console.log(props);

    return (
        <div className='inputModal' onClick={close}>
            <div
                className='content'
                onClick={(e) => {
                    e.stopPropagation();
                }}
            >
                <form onSubmit={handleSubmit}>
                    <label htmlFor='inputModal-inputField'>
                        {props.inputLabel}
                    </label>
                    <input
                        id='inputModal-inputField'
                        type='text'
                        required
                        placeholder={props.inputPlaceholder}
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                    />
                    <input
                        type='submit'
                        className='confirmButton'
                        value={props.submitButtonText}
                    />
                </form>
            </div>
        </div>
    );
};

export default InputModal;
