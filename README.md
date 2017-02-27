# build with laravel

## 配置
* apache
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

* store目录需要777权限
* php打开 mod_rewrite.so

