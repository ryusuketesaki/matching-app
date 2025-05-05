FROM php:8.1-apache

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# 必要なファイルのコピー
COPY composer.json composer.lock ./
COPY src ./src
COPY config ./config
COPY public ./public
COPY scripts ./scripts
COPY tests ./tests

# Composerの依存関係をインストール
RUN composer install --no-dev --optimize-autoloader

# Apacheの設定
RUN a2enmod rewrite
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# パーミッションの設定
RUN chown -R www-data:www-data /var/www/html

# ポートの公開
EXPOSE 80

# Apacheの起動
CMD ["apache2-foreground"]
