declare module '*.png';
declare module '*.svg';

declare namespace NodeJS {
    interface ProcessEnv {
        ServerURL: string;
    }
}
