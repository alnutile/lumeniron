#LuminIron

Library to make Iron Workers out of Lumin

Inspired by https://github.com/iron-io/laraworker

## Setup

Once installed via composer to lumin

~~~
composer require alnutile/luminiron
~~~

Then register the provider in `bootstrap/app.php`

~~~
$app->register('LuminIron\LuminIronServiceProvider');
~~~

Allow Facades in `bootstrap/app.php` line 20 or so

~~~
$app->withFacades();
~~~

And in the same file

~~~
Dotenv::load(__DIR__.'/../');
~~~

And the commands

~~~

~~~

Add your IRON_TOKEN and IRON_PROJECT_ID to your .env

~~~
IRON_TOKEN='bar'
IRON_PROJECT_ID='foo'
IRON_ENCRYPTION_KEY='foo'
QUEUE_DRIVER=iron
~~~

Then run the publish command for Lumin to setup

~~~
php artisan vendor:publish
~~~

You will now have a workers folder at the root of your Lumin dir with and example
worker.


## How to use it

Now you are ready to use

~~~
php artisan luminiron:upload --queue=ExampleWorker ExampleWorker
~~~

Will begin the upload


## TODO ITEMS

Add params feature to .env or to iron.json file when uploading the queue I can pull thos in
