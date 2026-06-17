FROM nginx:alpine

# Устанавливаем OpenSSL и другие необходимые пакеты
RUN apk update && apk add --no-cache openssl

# Создаем директорию для сертификатов
RUN mkdir -p /etc/nginx/certs

# Генерируем самоподписной SSL сертификат
RUN openssl req -newkey rsa:2048 -nodes -keyout /etc/nginx/certs/server.key \
    -x509 -days 365 -out /etc/nginx/certs/server.crt \
    -subj "/C=RU/ST=Moscow/L=Moscow/O=Dan-Center/CN=localhost"

# Копируем конфигурацию nginx
COPY docker/nginx/dev.default.conf /etc/nginx/conf.d/default.conf

# Открываем порты
EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
