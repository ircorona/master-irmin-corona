# PHP por FastCGI (php-cgi de Laragon, lo arranca prueba.py)
location ~ \.php$ {
    try_files $uri =404;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass php_upstream;
}
