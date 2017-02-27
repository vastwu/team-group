# build with laravel

## apache 配置
```
 <VirtualHost *:80>
      ServerName laravel.app
      ErrorLog “.../logs/laravelapp-error.log"
      CustomLog “.../logs/laravelapp-access.log" common
      DocumentRoot ".../laravel-master/public"
      <Directory ".../laravel-master/public">
          DirectoryIndex index.php,index.html
          AllowOverride all
          Options FollowSymLinks
          Order allow,deny
          Allow from all
     </Directory>
</VirtualHost>
```

