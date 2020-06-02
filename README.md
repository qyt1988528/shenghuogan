# shenghuogan
shenghuogan-wechatpro-api
git://github.com/phalcon/cphalcon.git v3.3.2
/usr/local/etc/nginx/
/usr/local/Cellar/php@7.1/7.1.32/pecl/20160303a

1 cd /opt/cphalcon-3.2.1/build/php7/64bits
2 && 
 /www/server/php/70/bin/phpize --enable-phalcon  --with-phpconfig=/www/server/php/70/bin/php-config
 ./configure --with-php-config=/www/server/php/70/bin/php-config
5 &&
 
 make && make install

cd cphalcon/build
./install --phpize /www/server/php/70/bin/phpize --php-config /www/server/php/70/bin/php-config --arch 64bits


yum	install	-y	wget	&&	wget	-O	install.sh	http://download.bt.cn/install/install.sh	&&	sh	install.sh
/www/server/php/70/lib/php/extensions/no-debug-non-zts-20151012