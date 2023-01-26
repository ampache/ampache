import create from '~node_modules/zustand';
import { PLAYERSTATUS } from '~enum/PlayerStatus';

type ZustandStore = {
    playerStatus: PLAYERSTATUS;
    songPosition: number;
    songQueue: string[];
    songQueueIndex: number;
    userQCount: number;
    startPlayingWithNewQueue: (newQ: string[], startPosition: number) => void;
    setSongQueueIndex: (newIndex: number) => void;
    setUserQCount: (newCount: number) => void;
    setPlayerStatus: (newStatus: PLAYERSTATUS) => void;
    setSongQueue: (newQ: string[]) => void;
    addToQueue: (songID: string, next: boolean) => void;
    removeFromQueue: (queueIndex: number) => void;
    setSongPosition: (newPos: number) => void;
};

export const useMusicStore = create<ZustandStore>((set, get) => ({
    playerStatus: PLAYERSTATUS.STOPPED,
    songPosition: 0,
    songQueue: [],
    songQueueIndex: 0,
    userQCount: -1,
    startPlayingWithNewQueue: (newQ, startPosition = 0) => {
        set({ songQueue: newQ, songQueueIndex: startPosition });
    },
    setSongQueueIndex: (newIndex) => set({ songQueueIndex: newIndex }),
    setUserQCount: (newCount) => set({ userQCount: newCount }),
    setPlayerStatus: (newStatus) => set({ playerStatus: newStatus }),
    setSongQueue: (newQ) => set({ songQueue: newQ }),
    addToQueue: (songID, next) => {
        const { songQueue, songQueueIndex, userQCount } = get();

        const newQueue = [...songQueue];
        if (next) {
            //splice starts at 1, so we don't need +2 //TODO make this comment more clear!
            newQueue.splice(songQueueIndex + 1 + userQCount, 0, songID);
            set({ userQCount: userQCount + 1, songQueue: newQueue });

            return;
        }

        newQueue.push(songID);
        set({ songQueue: newQueue });
    },
    removeFromQueue: (queueIndex) => {
        const { songQueue, songQueueIndex } = get();

        const newQueue = [...songQueue];

        newQueue.splice(queueIndex, 1);
        set({ songQueue: newQueue });

        //If we remove something from the queue that's behind the current playing song
        //the order will get messed up without this
        if (queueIndex < songQueueIndex) {
            set({ songQueueIndex: songQueueIndex - 1 });
        }
    },
    setSongPosition: (newPos) => set({ songPosition: newPos })
}));
