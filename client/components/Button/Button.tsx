import React from 'react';

import style from './index.module.styl';

export enum ButtonColors {
    gray = 'gray',
    green = 'green',
    blue = 'blue',
    red = 'red',
    yellow = 'yellow'
}

export enum ButtonSize {
    large = 'large',
    medium = 'medium',
    small = 'small'
}

interface ButtonProps {
    color: ButtonColors;
    size?: ButtonSize;
    text: string;
    onClick: () => void;
}

const Button = (props: ButtonProps) => {
    return (
        <button
            onClick={props.onClick}
            className={`${style.button} ${style[props.color]} ${
                style[props.size] ?? style.medium
            }`}
        >
            {props.text}
        </button>
    );
};

export default Button;
