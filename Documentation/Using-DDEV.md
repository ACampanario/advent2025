# Using DDEV as environment to run this example

Previous requirement, you need have installed DDEV https://ddev.readthedocs.io/en/stable/

Once you clone the project, in root directory execute

```
ddev start
```

This use the file in config/config.yaml to setup environment with
- PostgresSQL 15
- PHP 8.2

When it has started, enter on container

```
ddev ssh
```

Install with composer and run migrations

```
bin/cake composer install
bin/cake migrations migrate
```
