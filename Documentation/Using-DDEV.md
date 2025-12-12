# Using DDEV as environment to run this example

Previous requirement, you need have installed DDEV https://ddev.readthedocs.io/en/stable/

Once you clone the project, in root directory execute

```
ddev start
```

This use the file in config/config.yaml to setup environment with
- PostgresSQL 15
- PHP 8.2

After started install with composer and run migrations

```
ddev exec bin/cake composer install
ddev exec bin/cake migrations migrate
```
