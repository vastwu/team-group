#scp -r Http root@47.93.117.118:/home/www-deploy/laravel/app
rsync -av --exclude "*.ttf" ./Http root@47.93.117.118:/home/www-deploy/laravel/app
