# Assignment #2: Implement a simple blockchain
Implement a simple blockchain in a programming language of your choice. This assignment was made in PHP and the framework Slim.
As PHP is intended to "_do and forget_" and thus does not support in-memory storage (like Java), the state of the blockchain is stored in a file as well as the associated node identifier.
A node identifier is mapped to a port number on which the php application is listening on.

## Setup
Install the dependencies with composer:
```bash
./composer.phar install
```
or when using a global composer installation:
```bash
composer install
```

## Running the application
To run the application, execute the following command:
```bash
./composer.phar start
```
or when using a global composer installation:
```bash
composer start
```