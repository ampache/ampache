declare module '*.png';
declare module '*.svg';
declare module '*.styl';

declare namespace NodeJS {
    interface ProcessEnv {
        ServerURL: string;
    }
}
