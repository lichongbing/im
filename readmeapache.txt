<IfModule mod_rewrite.c>
  Options +FollowSymlinks -Multiviews
  RewriteEngine On
  RewriteRule static/(.*).(php)$ �C [F]
  RewriteRule upload/(.*).(php)$ �C [F]
  RewriteRule ^im/h5 /h5.html [L]
  RewriteRule ^im/web /web.html [L]
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php?s=$1 [QSA,PT,L]
</IfModule>


