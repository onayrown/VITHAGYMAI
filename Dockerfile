FROM php:8.1-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP necessárias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar PHP
RUN echo "date.timezone = America/Sao_Paulo" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copiar arquivos do projeto
COPY . /var/www/html/

# Criar diretórios necessários e dar permissões
RUN mkdir -p /var/www/html/logs \
    /var/www/html/uploads/fotos \
    /var/www/html/uploads/videos \
    /var/www/html/uploads/documentos \
    /var/www/html/cache \
    /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && chmod -R 777 /var/www/html/logs \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/cache

# Criar script de inicialização
RUN echo '#!/bin/bash\n\
echo "🚀 Iniciando VithaGymAI..."\n\
\n\
# Aguarda o MySQL estar disponível\n\
until php -r "try { new PDO(\"mysql:host=db;dbname=vithagymai\", \"$DB_USER\", \"$DB_PASS\"); echo \"OK\"; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do\n\
  echo "⏳ Aguardando MySQL..."\n\
  sleep 2\n\
done\n\
\n\
echo "✅ MySQL disponível, inicializando tabelas..."\n\
php /var/www/html/init-database.php\n\
\n\
echo "🌐 Iniciando Apache..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/start-vithagymai.sh \
    && chmod +x /usr/local/bin/start-vithagymai.sh

# Expor porta 80
EXPOSE 80

# Comando de inicialização
CMD ["/usr/local/bin/start-vithagymai.sh"]