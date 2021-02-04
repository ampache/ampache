import React, { useState } from 'react';

import style from './index.styl';

interface InputModalParams {
    submitButtonText: string;
    inputPlaceholder?: string;
    inputInitialValue?: string;
    inputLabel: string;
    ok?: any; //TODO
    cancel?: any;
}

const InputModal = (props: InputModalParams) => {
    const {
        submitButtonText,
        inputPlaceholder,
        inputInitialValue,
        inputLabel,
        ok
    } = { ...props };

    const [inputValue, setInputValue] = useState(inputInitialValue ?? '');

    const handleSubmit = (e) => {
        e.preventDefault();
        const inputValue = (document.getElementById(
            'inputModal-inputField'
        ) as HTMLInputElement).value;
        if (inputValue != (inputInitialValue ?? '')) {
            ok(inputValue);
        }
    };

    return (
        <div className={style.inputModal}>
            <form onSubmit={handleSubmit}>
                <label htmlFor='inputModal-inputField'>{inputLabel}</label>
                <input
                    id='inputModal-inputField'
                    type='text'
                    required
                    placeholder={inputPlaceholder}
                    value={inputValue}
                    onChange={(e) => setInputValue(e.target.value)}
                />
                <input
                    type='submit'
                    className={style.confirmButton}
                    value={submitButtonText}
                />
            </form>
        </div>
    );
};

export default InputModal;