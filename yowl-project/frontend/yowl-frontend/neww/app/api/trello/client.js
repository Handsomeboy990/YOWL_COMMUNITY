import axios from "axios";
import { setupInterceptors } from "./interceptors";

const API_KEY = process.env.EXPO_PUBLIC_TRELLO_API_KEY;
const TOKEN = process.env.EXPO_PUBLIC_TRELLO_TOKEN;

console.log("TRELLO KEY:", API_KEY);
console.log("TRELLO TOKEN:", TOKEN);


export const trelloClient = axios.create({
    baseURL: "https://api.trello.com/1",
    params: {
        key: API_KEY,
        token: TOKEN,
    },
});

setupInterceptors();