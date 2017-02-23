# Barista - Form Builder for Laravel

Let the Barista makes you a coffee. Just sit and relax. Tell the Barista to build your form and your form will build from scratch. 
Yeah, I know Laravel has built-in form support yet this package make the process even simpler. It supports build forms from a DataModel. DataModel is included in another package called "model-courier" by gguney.

### Requirements

- Barista works with PHP 5.6 or above.

### Installation

```bash
$ composer require gguney/barista
```

### Usage
Add package's service provider to your config/app.php

```php
...
        Barista\BaristaProvider::class,
...
		    'aliases' => [
...
        'Barista' => Barista\Facades\Barista::class,
    ],
...
```

Then write this line on cmd.
```bash
$ php artisan vendor:publish
```

This will publish barista.php config file to your app's config folder. So you can change views just by changing this config file.

### Author

Gökhan Güney - <gokhanguneygg@gmail.com><br />

### License

Barista is licensed under the MIT License - see the `LICENSE` file for details
