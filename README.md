# Boilerplate Back-End

## Dev Environment Setup

### Prerequisites
- Docker (https://docs.docker.com/get-docker/).
- `openssl`.
- `mkcert` - details below.
- No services running on ports `80` or `443`.

### Clone this repository
Select a location where you would like to clone this repository. The path used in the example below will be used throughout the file, but it can be changed for anything else.

```bash
$ mkdir -p ~/projects/app/backend
$ cd ~/projects/app/backend
$ git clone git@github.com:vmdumitrache/symfony-api-boilerplate.git . 
```
### Generate locally signed TLS certificates using `mkcert`

Navigate to the project's directory

`$ cd ~/projects/app/backend`

Install `mkcert`

See https://github.com/FiloSottile/mkcert for detailed instructions on how to install `mkcert` on your specific platform.

The next step is to generate a local CA.

`$ mkcert -install`

_Note_: This command should only be executed once per system.

Whilst still in the project's directory, generate the self-signed certificates:

```bash
$ mkcert -key-file ./docker/certs/key.pem \
-cert-file ./docker/certs/cert.pem \
app.dev \
*.app.dev 
```

Generate Diffie-Hellman parameters.
```bash
$ openssl dhparam -out ./docker/certs/dhparam.pem 4096
```

### DNS / Host Configuration

If you are using `dnsmasq`, update its configuration so that it will point `*.app.dev` to `127.0.0.1`

If you are not using `dnsmasq`, update `/etc/hosts`:
```
127.0.0.1   app.dev
```

### One-Line App Initialisation
```bash
$ make build
```

### Multi-step Installation
If you would like to manually go through all the steps to get the app working, please have a look at `./Makefile`. 
