# Peyflex VTU CSV PHP Package

[![Run Tests](https://github.com/henryejemuta/php-peyflex-vtu/actions/workflows/run-tests.yml/badge.svg)](https://github.com/henryejemuta/php-peyflex-vtu/actions/workflows/run-tests.yml)
[![Latest Stable Version](https://poser.pugx.org/henryejemuta/php-peyflex-vtu/v/stable)](https://packagist.org/packages/henryejemuta/php-peyflex-vtu)
[![Total Downloads](https://poser.pugx.org/henryejemuta/php-peyflex-vtu/downloads)](https://packagist.org/packages/henryejemuta/php-peyflex-vtu)
[![License](https://poser.pugx.org/henryejemuta/php-peyflex-vtu/license)](https://packagist.org/packages/henryejemuta/php-peyflex-vtu)
[![Quality Score](https://img.shields.io/scrutinizer/g/henryejemuta/php-peyflex-vtu.svg?style=flat-square)](https://scrutinizer-ci.com/g/henryejemuta/php-peyflex-vtu)

A robust PHP package for integrating with the Peyflex VTU API. This package allows you to easily purchase airtime, data, cable TV, and electricity tokens.

## Features

-   **Airtime Purchase**: Buy airtime for all major Nigerian networks.
-   **Data Purchase**: Buy data bundles for all major Nigerian networks.
-   **Cable TV Subscription**: Subscribe to DSTV, GOTV, and Startimes.
-   **Electricity Bill Payment**: Pay for prepaid and postpaid electricity meters.
-   **Universal Compatibility**: Works with Laravel, CodeIgniter, Symfony, and raw PHP projects.

## Installation

You can install the package via composer:

```bash
composer require henryejemuta/php-peyflex-vtu
```

## Usage

### Initialization

To start using the package, initialize the `Client` with your API token and optional configuration.

```php
use HenryEjemuta\Peyflex\Client;

$config = [
    'base_url' => 'https://client.peyflex.com.ng/api/', // Optional: Defaults to live URL
    'timeout' => 30, // Optional: Request timeout in seconds
];

$client = new Client('YOUR_API_TOKEN', $config);
```

### Airtime Purchase

```php
$response = $client->purchaseAirtime('mtn', '08012345678', 100);

print_r($response);
```

### Data Purchase

```php
// Network identifier and plan code come from getDataNetworks() / getDataPlans()
$response = $client->purchaseData('mtn_data_share', '08012345678', 'M1GBS');

print_r($response);
```

### Cable TV Subscription

```php
// Verify the IUC/Smartcard first
$verify = $client->verifyCable('dstv', '1234567890');

// Then subscribe: provider, IUC, plan code, subscriber phone, amount
$response = $client->purchaseCable('dstv', '1234567890', 'premium', '08012345678', 5000);

print_r($response);
```

### Electricity Bill Payment

```php
// Verify the meter first
$verify = $client->verifyMeter('ikeja-electric', '1234567890', 'prepaid');

// Then pay: disco plan, meter, amount, type, subscriber phone
$response = $client->purchaseElectricity('ikeja-electric', '1234567890', 1000, 'prepaid', '08012345678');

print_r($response);
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
