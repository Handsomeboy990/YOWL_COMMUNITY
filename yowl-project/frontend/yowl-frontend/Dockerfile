# Stage 1: Build
FROM node:20-alpine AS build
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps
COPY . .
ARG VITE_API_URL
ENV VITE_API_URL=${VITE_API_URL}
RUN npm run build

# Stage 2: Nginx static serve
FROM nginx:1.25-alpine
COPY --from=build /app/dist /usr/share/nginx/html
# Default nginx config override
COPY docker/nginx/frontend.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx","-g","daemon off;"]
