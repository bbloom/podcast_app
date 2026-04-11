# Base image
FROM dunglas/frankenphp:1.11.2-builder-php8

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    libpq-dev \
    zip \
    unzip \
    postgresql-client \
    sudo \
    # --- ADDED: Python and Pip ---
    python3 \
    python3-pip \
    # -----------------------------
    && rm -rf /var/lib/apt/lists/*

# --- ADDED: Install YouTube Transcript Library ---
# We use --break-system-packages because Debian 12 manages its own Python environment
RUN pip install --break-system-packages yt-dlp youtube-transcript-api
# -------------------------------------------------

# Grant the frankenphp binary the ability to bind to privileged ports (80/443)
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

# Create non-root user for Dev Containers
ARG USERNAME=bob
ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupadd --gid $USER_GID $USERNAME \
    && useradd --uid $USER_UID --gid $USER_GID -m -s /bin/bash $USERNAME \
    && echo "$USERNAME ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Fix permissions for Caddy/FrankenPHP data directories
# These MUST be writable by the non-root user or Caddy will crash
RUN mkdir -p /data/caddy/pki /config \
    && chown -R $USERNAME:$USERNAME /data /config

# Configure Git
RUN git config --global user.email "bob.bloom@lasallesoftware.ca" \
    && git config --global user.name "Bob Bloom"

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    pcntl \
    xdebug

# Set working directory
WORKDIR /app

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy entrypoint
COPY docker-entrypoint-app-container.sh /usr/local/bin/docker-entrypoint-app-container.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-app-container.sh

# Switch to non-root user for Dev Containers
USER bob

# VS Code will override CMD during development
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-app-container.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
