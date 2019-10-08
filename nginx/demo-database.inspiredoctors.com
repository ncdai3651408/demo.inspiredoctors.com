server {
        listen 80;
        listen [::]:80;
        server_name demo-database.inspiredoctors.com;
        location / {
                proxy_pass http://localhost:9999;
                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection 'upgrade';
                proxy_set_header Host $host;
                proxy_cache_bypass $http_upgrade;
        }
}
