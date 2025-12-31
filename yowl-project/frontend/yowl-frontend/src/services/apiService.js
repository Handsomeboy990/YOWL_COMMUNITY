import axios from "axios";
import { useUserStore } from "@/stores/user";

/**
 *  **Axios instance for using api endpoints**
 */

const baseURL = import.meta.env.VITE_BASE_URL || "http://localhost:8000/api";

const api = axios.create({
    baseURL,
    timeout: 15000, // 15s delay max
    headers: {
        "Content-Type": "application/json",
    },
});

// Add the token dynamically if available
api.interceptors.request.use((config) => {
    const store = useUserStore()
    const token = store.token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor for better error handling
api.interceptors.response.use(
    (response) => response,
    (err) => {
        if (err.response?.status === 401) {
            const store = useUserStore();
            store.logoutUser();
        }
        return Promise.reject(err);
    }
);

export default api;

/**
 *  **Sends a get request to server and returns a list of data or false based on response status**
 * @param {string} endPoint
 * @returns {Promise<any>}
 */
export async function fetchAllFromApi(endPoint) {
    try {
        const result = await api.get(endPoint);
        if (result.status === 200) {
            return result.data || [];
        }
        return false;
    } catch (err) {
        return false;
    }
}

/**
 * **Sends a post request to server and returns the data freshly stored id or false according to response status**
 * @param {string} endPoint
 * @param {object} data
 * @returns {Promise<any>}
 */
export async function storeWithApi(endPoint, data) {
    if (!data) return false;
    
    try {
        const result = await api.post(endPoint, data);
        if (result.status === 201 || result.status === 200) {
            return result.data;
        }
        return false;
    } catch (err) {
        throw err;
    }
}

/**
 * **Sends a put request to server and return true or false according to response status**
 * @param {string} endPoint
 * @param {string} id
 * @param {object} data
 * @returns {Promise<boolean>}
 */
export async function updateWithApi(endPoint, id, data) {
    if (!data || !id) return false;
    
    try {
        const result = await api.put(endPoint + id, data);
        return result.status === 200;
    } catch (err) {
        throw err;
    }
}

/**
 * **Sends a delete request to server and returns true or false**
 * @param {string} endPoint
 * @param {string} id
 * @returns {Promise<boolean>}
 */
export async function deleteWithApi(endPoint, id) {
    if (!id) return false;
    
    try {
        const result = await api.delete(endPoint + id);
        return result.status === 200 || result.status === 204;
    } catch (err) {
        throw err;
    }
}
