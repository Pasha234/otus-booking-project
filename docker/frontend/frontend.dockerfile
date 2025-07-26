FROM node:22.14-alpine

# Set working directory
WORKDIR /var/www/project

# Install npm global packages and sudo
RUN npm install --global gulp-cli
RUN npm install -g browser-sync && \
    apk add --no-cache sudo

# --- IMPORTANT: Install 'shadow' package for usermod/groupmod ---
RUN apk add --no-cache shadow

# ARG to pass the host UID during build. Default to 1000 for convenience.
ARG HOST_UID=1000

# Conditional logic to set up the 'dev' user with the correct UID
RUN set -eux; \
    # Check if a 'node' user exists and its UID
    NODE_UID=$(id -u node 2>/dev/null || echo ""); \
    \
    if [ "${HOST_UID}" = "1000" ] && [ "${NODE_UID}" = "1000" ]; then \
    # Scenario 1: Host wants UID 1000, and 'node' user already has it.
    # We cannot create a *new* user with UID 1000.
    # Best approach is to rename the 'node' user to 'dev' and adjust its home directory.
    echo "Host UID 1000 matches 'node' user. Renaming 'node' to 'dev' and adjusting home directory."; \
    usermod -l dev node; \
    groupmod -n dev node; \
    # Move the default /home/node directory to /home/dev and update the user's home path
    mv /home/node /home/dev; \
    usermod -d /home/dev dev; \
    chown -R dev:dev /home/dev; \
    elif [ -z "${NODE_UID}" ] || [ "${NODE_UID}" != "${HOST_UID}" ]; then \
    # Scenario 2: 'node' user doesn't exist, or it has a different UID than HOST_UID.
    # We can create a new 'dev' user with the desired HOST_UID.
    # First, check if HOST_UID is already taken by *another* user (highly unlikely for non-1000 UIDs)
    if getent passwd ${HOST_UID} > /dev/null; then \
    echo "ERROR: Host UID ${HOST_UID} is already taken by another user. Cannot proceed."; \
    exit 1; \
    fi; \
    echo "Creating 'dev' user with UID ${HOST_UID}."; \
    adduser -D -u ${HOST_UID} -h /home/dev dev; \
    else \
    # This else block should ideally not be hit with the current logic,
    # but it's good for robustness or if node user exists but with unexpected UID.
    echo "ERROR: Unexpected scenario during user setup. Node UID: ${NODE_UID}, Host UID: ${HOST_UID}"; \
    exit 1; \
    fi;

# Add sudoers entry for 'dev'
RUN echo '%dev ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

# Switch to the 'dev' user
USER dev:dev