# Rubix ML Server
Bring your [Rubix ML](https://github.com/RubixML/RubixML) models into production by serving them with one of our high-performance stand-alone model servers. Model severs wrap your trained estimators in an API such as REST or RPC that can be queried over a network in real-time. In addition, the library provides client implementations that make querying models from your application fast and easy.

## Installation
Install Rubix Server using [Composer](https://getcomposer.org/):

```sh
$ composer require rubix/server
```

## Requirements
- [PHP](https://php.net/manual/en/install.php) 7.2 or above

#### Optional
- [Event extension](https://pecl.php.net/package/event) for high-volume servers

## Documentation

### Table of Contents
- [Servers](#servers)
	- [HTTP Server](#http-server)
- [Clients](#clients)
	- [REST Client](#rest-client)
	- [RPC Client](#rpc-client)
- [HTTP Middleware](#http-middleware)
	- [Access Log Generator](#access-log-generator)
	- [Basic Authenticator](#basic-authenticator)
	- [Shared Token Authenticator](#shared-token-authenticator)
	- [Trusted Clients](#trusted-clients)

---
### Servers
Rubix model servers are stand-alone processes that wrap an estimator in an API that can be queried over a network connection. Since servers implements their own networking stack, they can be run directly from the PHP command line interface (CLI) without the need for an intermediary server such as Nginx or Apache. By utilizing concurrency, each server instance is able to handle thousands of connections at the same time. Need more inference throughput? Model servers scale by adding more instances behind a load balancer.

To boot up a server, pass a trained estimator instance to the `serve()` method:
```php
public function serve(Estimator $estimator) : void
```

```php
use Rubix\Server\HTTPServer;
use Rubix\ML\Classifiers\KNearestNeighbors;

$server = new HTTPServer('127.0.0.1', 8080);

$estimator = new KNearestNeighbors(5);

// Import a dataset

$estimator->train($dataset);

$server->serve($estimator);
```

Or, you can load a previously trained estimator from storage and serve it like in the example below.

```php
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

$estimator = PersistentModel::load(new Filesystem('example.model'));

$server->serve($estimator);
```

> **Note**: The server will stay running until the process is terminated. It is a good practice to use a process monitor such as [Supervisor](http://supervisord.org/) to start and autorestart the server in case of a failure.

#### Shutting Down The Server
To gracefully shut down the server, send a terminate (`SIGTERM`) to the process. To shut down immediately, without waiting for current connections to close, you can either send a second `SIGTERM` signal or you can send a single kill (`SIGKILL`) or interrupt (`SIGINT`) signal instead.

#### Verbose Interface
Servers that implement the Verbose interface accept any PSR-3 compatible logger instance and begin logging critical information such as errors and start/stop events. To set a logger pass the PSR-3 logger instance to the `setLogger()` method on the server instance.

```php
use Rubix\ML\Other\Loggers\Screen;

$server->setLogger(new Screen());
```

### HTTP Server
An HTTP(S) server exposing Representational State Transfer (REST) and Remote Procedure Call (RPC) APIs.

Interfaces: [Server](#servers), [Verbose](#verbose-interface)

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | host | '127.0.0.1' | string | The host address to bind the server to. |
| 2 | port | 80 | int | The network port to run the HTTP services on. |
| 3 | cert | | string | The path to the certificate used to authenticate and encrypt the HTTP channel. |
| 4 | middlewares | | array | The HTTP middleware stack to run on each request/response. |
| 5 | sse retry buffer | 50 | int | The size of the server-sent events retry buffer. |

**Example**

```php
use Rubix\Server\HTTPServer;
use Rubix\Server\HTTP\Middleware\AccessLogGenerator;
use Rubix\ML\Other\Loggers\Screen;
use Rubix\Server\HTTP\Middleware\BasicAuthenticator;

$server = new HTTPServer('127.0.0.1', 443, '/cert.pem', [
	new AccessLogGenerator(new Screen()),
	new BasicAuthenticator([
		'morgan' => 'secret',
		'taylor' => 'secret',
	]),
], 100);
```

#### Routes
The HTTP server exposes the following resources.

| Method | URI | Description |
|---|---|---|
| GET | / | The web user interface. |
| POST | /model/predictions | Make a set of predictions on a dataset. |
| POST | /model/probabilities | Return the joint probabilities of each sample in a dataset. |
| POST | /model/anomaly_scores | Return the anomaly scores of each sample in a dataset. |
| GET | /server | The server dashboard. |
| GET | /server/dashboard | Query the dashboard model. |
| GET | /server/dashboard/events | Subscribe to the dashboard event stream. |

#### Web Interface
The HTTP server provides its own high-level user interface to the REST API it exposes under the hood. To access the web UI, navigate to `http://hostname:port` using your web browser.

![Server Web UI Screenshot](https://raw.githubusercontent.com/RubixML/Server/master/docs/images/server-web-ui-screenshot.png)

#### PHP Configuration
This server respects the following `php.ini` configuration variables.

| Name | Default | Description |
|---|---|---|
| memory_limit | 128M | The total amount of memory available to the server to handle requests. |
| post_max_size | 8M | The maximum size of a request body to handle. |
| enable_post_data_reading | 1 | Should we automatically parse form and file upload data? |

---
### Clients
Clients allow you to communicate directly with a model server using a friendly object-oriented interface inside your PHP applications. Under the hood, clients handle all the networking communication and content negotiation for you so you can write programs *as if* the model was directly accessible in your applications.

Return the predictions from the model:
```php
public predict(Dataset $dataset) : array
```

```php
use Rubix\Server\RESTClient;

$client = new RESTClient('127.0.0.1', 8080);

// Import a dataset

$predictions = $client->predict($dataset);
```

Calculate the joint probabilities of each sample in a dataset:
```php
public proba(Dataset $dataset) : array
```

Calculate the anomaly scores of each sample in a dataset:
```php
public score(Dataset $dataset) : array
```

### Async Clients
Clients that implement the Async Client interface have asynchronous versions of all the standard client methods. All asynchronous methods return a [Promises/A+](https://promisesaplus.com/) object that resolves to the return value of the response. Promises allow you to perform other work while the request is processing or to execute multiple requests in parallel. Calling the `wait()` method on the promise will block until the promise is resolved and return the value.

```php
public predictAsync(Dataset $dataset) : Promise
```

```php
$promise = $client->predictAsync($dataset);

// Do something else

$predictions = $promise->wait();
```

Return a promise for the probabilities predicted by the model:
```php
public probaAsync(Dataset $dataset) : Promise
```

Return a promise for the anomaly scores predicted by the model:
```php
public scoreAsync(Dataset $dataset) : Promise
```

### REST Client
The REST (Representational State Transfer) client communicates with a [REST Server](#rest-server).

Interfaces: [Client](#clients), [AsyncClient](#async-clients)

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | host | '127.0.0.1' | string | The IP address or hostname of the server. |
| 2 | port | 8888 | int | The network port that the HTTP server is running on. |
| 3 | secure | false | bool | Should we use an encrypted HTTP channel (HTTPS)?. |
| 4 | headers | | array | Additional HTTP headers to send along with each request. |
| 5 | timeout | | float | The number of seconds to wait before giving up on the request. |
| 6 | retries | 3 | int | The number of retries before giving up on the request. |

**Example**

```php
use Rubix\Server\RESTClient;

$client = new RESTClient('127.0.0.1', 443, true, [
    'Authorization' => 'Basic ' . base64_encode('morgan:secret'),
], 0.0, 5);
```

---
### HTTP Middleware
HTTP middleware are objects that process incoming HTTP requests before and after they are handled by a final request handler (controller). They allow the user to hook into the HTTP request/response cycle by inserting additional logic into the pipeline.

### Access Log Generator
Generates an HTTP access log using a format similar to the Apache log format.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | logger | | LoggerInterface | A PSR-3 logger instance. |

**Example**

```php
use Rubix\Server\HTTP\Middleware\AccessLog;
use Rubix\ML\Other\Loggers\Screen;

$middleware = new AccessLog(new Screen());
```

```sh
[2020-11-04 23:10:57] INFO: 127.0.0.1 "POST /predictions HTTP/1.1" 200 140 - "Rubix RPC Client"
[2020-11-04 23:11:54] INFO: 127.0.0.1 "POST /predictions/sample HTTP/1.1" 200 96 - "Rubix RPC Client"
```

### Basic Authenticator
An implementation of HTTP Basic Auth as described in [RFC7617](https://tools.ietf.org/html/rfc7617).

> **Note:** This authorization strategy is only secure over an encrypted communication channel such as HTTPS with SSL or TLS.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | passwords | | array | An associative map from usernames to their passwords. |
| 2 | realm | 'auth' | string | The unique name given to the scope of permissions required for this server. |

**Example**

```php
use Rubix\Server\HTTP\Middleware\BasicAuthenticator;

$middleware = new BasicAuthenticator([
	'morgan' => 'secret',
	'taylor' => 'secret',
], 'ml models');
```

### Shared Token Authenticator
Authenticates incoming requests using a shared key that is kept secret between the client and server. It uses the `Authorization` header with the `Bearer` prefix to indicate the shared key.

> **Note:** This authorization strategy is only secure over an encrypted communication channel such as HTTPS with SSL or TLS.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | tokens | | array | The shared secret keys (bearer tokens) used to authorize requests. |
| 2 | realm | 'auth' | string | The unique name given to the scope of permissions required for this server. |

**Example**

```php
use Rubix\Server\HTTP\Middleware\SharedTokenAuthenticator;

$middleware = new SharedTokenAuthenticator([
	'secret', 'another-secret',
], 'ml models');
```

### Trusted Clients
A whitelist of clients that can access the server - all other connections will be dropped.

#### Parameters
| # | Param | Default | Type | Description |
|---|---|---|---|---|
| 1 | ips | ['127.0.0.1'] | array | An array of trusted client ip addresses. |

**Example**

```php
use Rubix\Server\HTTP\Middleware\TrustedClients;

$middleware = new TrustedClients([
	'127.0.0.1', '192.168.4.1', '45.63.67.15',
]);
```

## License
The code is licensed [MIT](LICENSE.md) and the documentation is licensed [CC BY-NC 4.0](https://creativecommons.org/licenses/by-nc/4.0/).