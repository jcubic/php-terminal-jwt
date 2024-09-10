# JWT and jQuery Terminal demo

This is a demo of JWT tokens using with [jQuery Terminal](https://terminal.jcubic.pl/)
JSON-RPC feature. The PHP code use [Firebase JWT](https://github.com/firebase/php-jwt)
library and implements Access and Refresh tokens.
The code also use [json-rpc.php](https://github.com/jcubic/json-rpc) for JSON-RPC implementation.

## Usage

To use this code:
1. Run `composer install` (composer need to be installed on your system)
2. Copy `.env.example` to `.env` and set username, password, secrets, and the rest of the configuration

To test this in action you can check: https://terminal.jcubic.pl/jwt/
The username and password is default (demo:demo).

## License
Licensed under [MIT](http://opensource.org/licenses/MIT) license<br/>
Copyright (c) 2023 [Jakub T. Jankiewicz](https://jcubic.pl/me)
