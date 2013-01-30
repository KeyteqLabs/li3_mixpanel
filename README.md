# li3_mixpanel

Lithium library for sending statistical data to [Mixpanel](https://mixpanel.com).

## Installation

Add a submodule to your li3 libraries:

    git submodule add git@github.com:bruensicke/li3_mixpanel.git libraries/li3_mixpanel

and activate it in you app (config/bootstrap/libraries.php), of course:

```php
<?php
Libraries::add('li3_mixpanel', array(
    'token' => $token
));
```

## Usage

```php
<?php
Mixpanel::track('api.requests', $params['request']->params);
```

## Configuration options

* token
* env **string/array** Override which Lithium environments to allow tracking on
* host
* port

## Credits

* [li3](http://www.lithify.me)
* [Mixpanel](https://mixpanel.com)


