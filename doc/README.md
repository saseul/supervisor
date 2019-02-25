# Documentation

## Install dependencies

```bash
composer install --prefer-dist
```

## Generate HTML

```bash
composer doc
```

## Generate HTML with Docker

```bash
docker build -t saseul-origin-doc .

docker run -it --rm \
    -v $(pwd)/output:/u/app/output \
    -v $(pwd)/..:/u/src \
    saseul-origin-doc
```
