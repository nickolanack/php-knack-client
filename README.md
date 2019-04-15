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



//Iterate all values in a table

(new \knack\Client(array(
	"id"=>"XXXXXXXXXXXXXXXXXXXXXXXX",
	"key"=>"XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
)))
  ->iterateRecords('object_16', function(/*array*/$record, $i){

      print_r($record); //array(id=>string, field_1=>value ... field_2=>value ...)

  });






//Iterate all values in a table with label indexes from table definition

(new \knack\Client(array(
  "id"=>"XXXXXXXXXXXXXXXXXXXXXXXX",
  "key"=>"XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
)))

  ->defineTableObjectName('someTableName', 16)
  ->createCachedTableDefinitionIfNotExists('someTableName')
  ->useCachedTableDefinition('someTableName')
  
  ->iterateRecords('someTableName', function(/*array*/$record, $i){

      print_r($record); //array(knackid=>string, label1=>value ... label1=>value ...)

  });


```