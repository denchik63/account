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

Then create account
```bash
make create-account ARGS="-i 0.0"
```
after refill it
```bash
make refill-account ARGS="account 1 3500.0"
```
and write-off from it
```bash
make write-off-account ARGS="account 1 1500.0"
```
Note! Pparameter accountId (above 1) may be different in your case

## Run tests

```bash
make tests
```
