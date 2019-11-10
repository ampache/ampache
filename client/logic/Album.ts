import axios from "axios";
import {Song} from "./Song";

type Album = {
    id: number,
    name: string,
    artist: {
        id: number,
        name: string
    },
    year: number,
    tracks: number,
    disk: number,
    tags: {
        id: number,
        count: number,
        name: string
    },
    art: string,
    preciserating: number,
    rating: number,
    averagerating: number,
    mbid: string
}

const getRandomAlbums = async (username: string, count: number, authCode: string, server: string) => {
    return axios.get(`${server}/server/json.server.php?action=stats&username=${username}&limit=${count}&auth=${authCode}&version=400001`).then(response => {

        let JSONData = response.data;
        if (JSONData.error) {
            throw (JSONData.error);
        }
        if (JSONData) {
            const albums: Album[] = JSONData;
            return albums;
        }
        throw ("Something wrong with JSON");
    }).catch(error => {
        throw (error);
    });
};

const getAlbumSongs = async (albumID: number, authCode: string, server: string) => {
    return axios.get(`${server}/server/json.server.php?action=album_songs&filter=${albumID}&auth=${authCode}&version=400001`).then(response => {
        let JSONData = response.data;
        if (JSONData.error) {
            throw (JSONData.error);
        }
        if (JSONData) {
            let songs: Song[] = JSONData;
            return songs;
        }
        throw ("Something wrong with JSON");
    }).catch(error => {
        throw (error);
    });
};

const getAlbum = async (albumID: number, authCode: string, server: string) => {
    return axios.get(`${server}/server/json.server.php?action=album&filter=${albumID}&auth=${authCode}&version=400001`).then(response => {
        let JSONData = response.data;
        if (JSONData.error) {
            throw(JSONData.error);
        }
        if (JSONData) {
            const album: Album = JSONData[0];
            return album;
        }
        throw("Something wrong with JSON");
    }).catch(error => {
        throw (error);
    });
};


export {getRandomAlbums, Album, getAlbum, getAlbumSongs};

