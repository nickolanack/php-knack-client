# php-knack-client
a knack client for php


#Install

```js
//in composer.json
{

    "repositories":[ 
        {
            "type":"git",
            "url":"https://github.com/nickolanack/php-knack-client.git"
        }
    ],
    "require": {
       "nblackwe/php-knack-client":"dev-master"
   }
}

```

```bash

composer install

```

#Usage
```php

include_once __DIR__.'/vendor/autoload.php';

$client=new \knack\Client(array(
	"id"=>"XXXXXXXXXXXXXXXXXXXXXXXX",
	"key"=>"XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
));


```