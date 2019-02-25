# API

## Install packages

Set up on CentOS.

```bash
# httpd24*
yum install -y httpd24*

# php71
yum install -y php71*

# memacached
yum install -y memcached

# git
yum install -y git

# openssl-devel
yum install -y openssl-*
```

### php-ed25519-ext

```bash
git clone git://github.com/encedo/php-ed25519-ext.git
cd php-ed25519-ext
phpize
./configure
make
sudo make install
```

### MongoDB

Refer to [MongoDB homepage](https://www.mongodb.com/).

### php-mongodb-driver

Refer to [MongoDB homepage](https://www.mongodb.com/).

### php source codes

```sh
# Clone the repository.
git clone git@github.com:Artifriends-inc/saseul-origin.git

# Change working directory.
cd saseul-origin/api

# Install all the dependencies using composer.
composer install
```

## Run fixer

```bash
composer fixer
```

## Etc

Development is still in progress.
