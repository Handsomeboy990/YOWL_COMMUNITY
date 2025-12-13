import axios from "axios";
import { useUserStore } from "@/stores/user";

/**
 *  **Axios instance for using api endpoints**
 */

const api = axios.create({
    baseURL: "http://localhost:8000/api", 
    timeout: 15000, // 15s delay max
    headers: {
        "Content-Type": "application/json",
        // "Authorization": `Bearer ${token}`
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Add the token dynamicly if available
api.interceptors.request.use((config) => {
    const store = useUserStore()
    const token = localStorage.getItem("token")? localStorage.getItem("token") : store.token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;

/**
 *  **Sends a get request to server and returns a list of data or false based on response status**
 * @param {string} endPoint
 * @returns
 */

export async function fetchAllFromApi(endPoint) {
    const result = await api.get(endPoint);

    if (result.status === 200) {
        const data = result.data;
        return data ? data : [];
    }
    return false;
}

/**
 * **Sends a post request to server and returns the data freshly stored id or false according to response status**
 * @param {string} endPoint
 * @param {object} data
 * @returns
 */
export async function storeWithApi(endPoint, data) {

    if (data) {
        const result = await api.post(endPoint, data);
        if (result.status === 201) return result.data;
    }
    return false;
}

/**
 * **Sends a put request to server and return true or false according to response status**
 * @param {string} endPoint
 * @param {string} id
 * @param {object} data
 * @returns
 */
export async function updateWithApi(endPoint, id, data) {

    if (data && id) {
        const result = await api.put(endPoint + id, data);
        return result.status === 200;
    }
    return false;
}

/**
 * **Sends a delete request to server and returns true or false**
 * @param {string} endPoint
 * @param {string} id
 * @returns
 */
export async function deleteWithApi(endPoint, id) {

    if (id) {

        const result = await api.delete(endPoint + id);
        return result.status === 200;
    }
    return false;
}

export async function fetchToken(){

    try{
        await axios.get('http://localhost:8000/sanctum/csrf-cookie')
    }catch(error){
        throw new Error(error);
    }
}
