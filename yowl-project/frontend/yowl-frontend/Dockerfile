# Stage 1: Build
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps
COPY . .
ARG VITE_BASE_URL
ARG VITE_STORAGE_URL
ENV VITE_BASE_URL=${VITE_BASE_URL}
ENV VITE_STORAGE_URL=${VITE_STORAGE_URL}
RUN npm run build

# Stage 2: Nginx static serve
FROM nginx:1.25-alpine
COPY --from=build /app/dist /usr/share/nginx/html
# Default nginx config override
COPY docker/nginx/frontend.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx","-g","daemon off;"]
