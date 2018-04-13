MyCryptoChat
============

MyCryptoChat is a PHP and Javascript based chat with end-to-end encryption. The database will only contain your encrypted messages, and will have no knowledge of the decryption key.
Encryption is provided by the [Stanford Javascript Crypto Library](https://github.com/bitwiseshiftleft/sjcl), using 256-bit AES-GCM.

This is a rework of HowTommy's [MyCryptoChat](https://github.com/HowTommy/mycryptochat)

The project is being hosted at [mycryptochat.org](https://mycryptochat.org/)

# Requirements

PHP 5.6+

php-pdo

# Setup

Give write permission to `db/chatrooms.sqlite` and `db/logs.txt`. Database errors will be logged to `db/logs.txt`, you can look from there if you're having issues.

Copy the configuration template from `inc/conf.template.php` to `inc/conf.php`

The database type will be SQLite by default, the SQLite database is stored in `db/chatrooms.sqlite`.

## MySQL

If you'd rather use MySQL, edit the configuration file, change the DB_TYPE variable to DATABASE_MYSQL, and set the DB_NAME, DB_HOST, DB_USER, DB_PASSWORD variables to their right values.

You can use the `structure.sql` to import the table structures to your database.


# Known issues
## version 1.2.4+ ##
Internet Explorer doesn't display the chat correctly due to miserable support of flexbox. Use a better browser.
