# upn-qr-code

### Xdebug 3

1) `php -v` preveri PHP verzijo
2) `brew install php-xdebug` namesti Xdebu
3) `php -m | grep xdebug` preveri ali obstaja Xdebug
4) `php --ini` poišči .ini datoteko in dodaj
```
zend_extension="/opt/homebrew/lib/php/pecl/20250925/xdebug.so"

xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```
5) Namesti VScode vtičnik `PHP Debug` (by Xdebug)
6) V VScode odpri `Run & Debug` sidebar in poženi  `create launch.json`
```
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003
    }
  ]
}
```
