FROM node:22.14-alpine

# Set working directory
WORKDIR /var/www/project

RUN npm install --global gulp-cli
RUN npm install -g browser-sync && \
    apk add --no-cache sudo

RUN echo '%node ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER node:node
