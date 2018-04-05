MyCryptoChat
============

MyCryptoChat is a PHP and Javascript based chat with end-to-end encryption. The database will only contain your encrypted messages, and will have no knowledge of the decryption key.
Encryption is provided by the Stanford Javascript Crypto Library, using 256-bit AES-GCM.

# Requirements

PHP 5.6+

php-pdo

# Setup

Give write permission to `db/chatrooms.sqlite` and `db/logs.txt`. Database errors will be logged to `db/logs.txt`, you can look from there if you're having issues.

Copy the configuration template from `inc/conf.template.php` to `inc/conf.php`

Edit the configuration file `inc/conf.php`. You will have to change the SEED variable.

The database type will be SQLite by default, the SQLite database is stored in `db/chatrooms.sqlite`.

## MySQL

If you'd rather use MySQL, edit the configuration file, change the DB_TYPE variable to DATABASE_MYSQL, and set the DB_NAME, DB_HOST, DB_USER, DB_PASSWORD variables to their right values.

You can use the `structure.sql` to import the table structures to your database.
