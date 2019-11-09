import axios from "axios";
import React from "react";
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
    return new Promise((resolve, reject) => {
        axios.get(`${server}/server/json.server.php?action=stats&username=${username}&limit=${count}&auth=${authCode}&version=400001`).then(response => {

            let JSONData = response.data;
            if (JSONData.error) {
                reject(JSONData.error);
            } else if (JSONData) {
                const albums: Album[] = JSONData;
                resolve(albums);
            } else {
                reject("Something wrong with JSON");
            }
        }).catch(error => {
            reject(error);
        });
    });
};

const getAlbumSongs = async (albumID: number, authCode: string, server: string) => {
    return new Promise((resolve, reject) => {
        axios.get(`${server}/server/json.server.php?action=album_songs&filter=${albumID}&auth=${authCode}&version=400001`).then(response => {
            let JSONData = response.data;
            if (JSONData.error) {
                reject(JSONData.error);
            } else if (JSONData) {
                const songs: Song[] = JSONData;
                resolve(songs);
            } else {
                reject("Something wrong with JSON");
            }
        }).catch(error => {
            reject(error);
        });
    });
};

const getAlbum = async (albumID: number, authCode: string, server: string) => {
    return new Promise((resolve, reject) => {
        axios.get(`${server}/server/json.server.php?action=album&filter=${albumID}&auth=${authCode}&version=400001`).then(response => {
            let JSONData = response.data;
            console.log(response);
            if (JSONData.error) {
                reject(JSONData.error);
            } else if (JSONData) {
                const album: Album = JSONData[0];
                resolve(album);
            } else {
                reject("Something wrong with JSON");
            }
        }).catch(error => {
            reject(error);
        });
    });
};


export {getRandomAlbums, Album, getAlbum, getAlbumSongs};

