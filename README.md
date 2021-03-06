# li3_mixpanel

Lithium library for sending statistical data to [Mixpanel](https://mixpanel.com).

## Installation

### Composer

```json
{
  "require" : {
    "KeyteqLabs/li3_mixpanel" : "*"
  }
}
```

    composer.phar install

### Submodule

Add a submodule to your li3 libraries:

    git submodule add git@github.com:KeyteqLabs/li3_mixpanel.git libraries/li3_mixpanel

### Activate it

Add this to your app (config/bootstrap/libraries.php):

```php
<?php
Libraries::add('li3_mixpanel', array(
    'token' => $token
));
```

### Filtering track calls by environment

Send the `env` key when adding the library to only enable one or a set of environments.
Passing `*` means enabling it for every environment.

```php
<?php
Libraries::add('li3_mixpanel', array(
    'token' => $token,
    'env' => array('production', 'staging')
));
```

## Sending data

```php
<?php
Mixpanel::track('api.requests', $params['request']->params);
// Track revenue
Mixpanel::transaction($userId, $sum);
// Track people by identifying the person
Mixpanel::set($userId, array(
    '$name' => $user->name,
    '$username' => $user->username
));
```

## Credits

* [li3](http://www.lithify.me)
* [Mixpanel](https://mixpanel.com)


