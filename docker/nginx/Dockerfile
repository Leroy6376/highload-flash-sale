FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# The "app" build context is the production PHP image declared in Compose.
# It provides the exact public files and Vite build served by Nginx.
COPY --from=app /var/www/public /var/www/public
