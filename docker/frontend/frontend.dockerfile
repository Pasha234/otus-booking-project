FROM node:22.14-alpine

# Set working directory
WORKDIR /var/www/project

RUN npm install --global gulp-cli
RUN npm install -g browser-sync

ARG UID=1000

RUN groupadd -g ${UID} dev
RUN useradd -u ${UID} -g dev -d /home/dev -m dev
RUN echo '%dev ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER dev:dev
