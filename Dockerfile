FROM dunglas/frankenphp

RUN install-php-extensions \
    pcntl

# Chromium + Ghostscript for label PDF rendering
RUN apt-get update && apt-get install -y --no-install-recommends \
        chromium ghostscript fonts-liberation \
        libnss3 libatk-bridge2.0-0 libgbm1 libasound2 \
        nodejs npm \
    && rm -rf /var/lib/apt/lists/*

ENV BROWSERSHOT_CHROMIUM_PATH=/usr/bin/chromium

COPY . /app

ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--https", "--http-redirect"]
