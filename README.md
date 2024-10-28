## Service with account balance manipulations (test)

## Installation

```bash
cp .env.local.dist .env.local
cp auth.json.dist auth.json
```
fill github username and password in auth.json
```bash
make start
```

## How use?

```bash
make start-worker QUEUE_NAME=account
```

where 'QUEUE_NAME' value you can set whatever you want

## Run tests

```bash
make tests
```
