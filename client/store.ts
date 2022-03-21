import create from '~node_modules/zustand';
import { Song } from '~logic/Song';

type ZustandStore = {
    currentPlayingSong: Song | null;
    startPlayingSong: (song: Song) => void;
    flagCurrentSong: (flag: boolean) => void;
};

export const useStore = create<ZustandStore>((set, get) => ({
    currentPlayingSong: null,
    startPlayingSong: (song: Song) => {
        set(() => ({ currentPlayingSong: song }));
    },
    flagCurrentSong: (flag: boolean) => {
        set((state) => ({
            currentPlayingSong: {
                ...state.currentPlayingSong,
                flag
            }
        }));
    }
}));
