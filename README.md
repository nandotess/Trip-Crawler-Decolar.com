# Trip Crawler - Decolar.com
A PHP script/crawler to get from [Decolar.com](http://www.decolar.com/) flight tickets promotions and send alerts (e-mail and/or [Pushover App](https://pushover.net/)) for who you want.

## About me
* PHP script
* [Decolar.com](http://www.decolar.com/) flight tickets crawler

## Installation

### Install Composer (root directory)
`curl -sS https://getcomposer.org/installer | php`

### Install Composer PHP PhantomJS module (root directory)
`php composer.phar install`

This will run the composer.json file and download PHP PhantomJS module to "vendor" folder.

## Resources
* https://github.com/jonnnnyw/php-phantomjs
* https://pushover.net/

## Receiving alerts on your smartphone
This PHP script is compatible with the [Pushover App](https://pushover.net/) (available for Android and iPhone). To use this feature and receive in your smartphone alerts with the promotions, you need pass the parameters `pushover_token` and `pushover_user` to `TripCrawlerDecolarCom` class constructor. The file `example.php` contains the related instructions.

## PhantomJS issues
If you are having issues with PhantomJS binary (empty header and body content from response, for example), please read this solutions:
* https://github.com/jonnnnyw/php-phantomjs/issues/9#issuecomment-39685608
* https://github.com/jonnnnyw/php-phantomjs/issues/86#issuecomment-229947749

Probably you will need download the latest version from [PhantomJS binary](http://phantomjs.org/download.html) compatible with your system.
