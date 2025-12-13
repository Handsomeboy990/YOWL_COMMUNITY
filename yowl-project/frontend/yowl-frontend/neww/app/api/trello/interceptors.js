import { trelloClient } from "./client";

export const setupInterceptors = () => {
  trelloClient.interceptors.request.use(
    (config) => {
      console.log("Request:", config.method.toUpperCase(), config.url);
      return config;
    },
    (error) => {
      console.error("Request Error:", error);
      return Promise.reject(error);
    }
  );

  trelloClient.interceptors.response.use(
    (response) => {
      return response.data;
    },
    (error) => {
      console.error("API Error:", error?.response?.data || error.message);
      return Promise.reject(error);
    }
  );
};
