# Laravel-Translator

> Laravel command that interactively helps you translate missing keys.

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/rmariuzzo/Laravel-Translator)

![Laravel Translator screenshot](https://raw.github.com/rmariuzzo/Laravel-Translator/master/screenshot.png)

## Installation

Add the following line to your `composer.json` file under `require`:

```json
"mariuzzo/laravel-translator": "1.0.*"
```

Then run:

```bash
composer update
```

Add the service provider into your Laravel app (`app/config/app.php`):

```php
'providers' => array(
    ...
    'Mariuzzo\Translator\TranslatorServiceProvider'
    ...
)
```

That's it!

## Usage

This project comes with a single command which start the translator. The translator will ask what you want to do.

```bash
php artisan translator:start
```

> **Warning:** Saving translation changes to disk will overwrite all lang files.

### Features

The Laravel Translator command allows you to:

 - Check for missing translation lines.
 - Translate interactively missing translation lines.
 - Save changes to disk.

## How to contribute?

> All help are more than welcome!

### Development Workflow

 1. Fork this repository.
 2. Clone your fork and create a feature branch from develop.

    ```bash
    git checkout develop 
    git checkout -b feature-fancy-name
    ```

 3. Install development dependencies.

    ```bash
    composer update
    ```

 4. Code and be happy!
 5. Submit a pull request.

## Tests

 > 404 Test not found! This is the very first release, try again later, and you will find the tests.
