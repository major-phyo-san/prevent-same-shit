# Prevent Same Shit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/major-phyo-san/prevent-same-shit.svg?style=flat-square)](https://packagist.org/packages/major-phyo-san/prevent-same-shit)
[![Total Downloads](https://img.shields.io/packagist/dt/major-phyo-san/prevent-same-shit.svg?style=flat-square)](https://packagist.org/packages/major-phyo-san/prevent-same-shit)
[![License](https://img.shields.io/packagist/l/major-phyo-san/prevent-same-shit.svg?style=flat-square)](https://packagist.org/packages/major-phyo-san/prevent-same-shit)

## Introduction

`Prevent Same Shit` is a Laravel package (based on Laravel 10) that provides a simple and efficient way to prevent duplicate database records. It helps developers handling of duplications of each record at database level, making their work easier and more efficient.

---

## What It Does
This package generates ``'record_hash'`` (or if you prefer, your custom ``hash_column_name``) database columns for Eloquent models. The ``'record_hash'`` column stores SHA256 hash for each database entry of a specific models. When there's a new record entry, the package will calculate the hash value of the new record, checking it against the existing hash values and if any duplication exists, prevent the entry of the new record.

## Installation

You can install the package via Composer:

```bash
composer require major-phyo-san/prevent-same-shit
```
Laravel uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider. But for the standard documentation purpose, the following is the package discovery.

```bash
php artisan vendor:publish --provider="MajorPhyoSan\\PreventSameShit\\PreventSameShitServiceProvider"
```

## Usage
The ``HasRowHash`` trait will handle record hashing and checking if duplicated record exists based on the hash value automatically. All you need to do is to use the trait in your Eloquent model. The model using the trait must have the ``'record_hash'`` [string, nullable] (or your custom hash column name, just don't forget to provide your custom hash column name in the model) column.

### Example

```bash
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use MajorPhyoSan\PreventSameShit\Traits\HasRowHash;

class Product extends Model
{
    use SoftDeletes, HasRowHash;
}
```

The behavior of ``HasRowHash`` trait can be manipulated using three properties: ``$recordHashColumn``, ``$excludedFromHash`` and ``$includedInHash``. You must provide ``$recordHashColumn`` property if you customized the hash column's name (like ``$recordHashColumn = "sha_256_val";``). The ``$excludedFromHash`` property tells the trait to ignore some columns when checking for duplication in the database table. For example, if you want to exclude ``'provider_id'`` and ``'entry_date'`` columns when hash checking the records, you just have to add a model attribute (an array) named ``$excludedFromHash`` and provide column names to be excluded.

```bash
$excludedFromHash = ['provider_id','entry_date']; // those columns will be ignored from checking
```

Note that ``'id'``, ``'record_hash'``, ``'created_at'`` and ``'updated_at'`` columns have been ignored by default.

The ``$includedInHash`` property does the opposite of the ``$excludedFromHash`` property. It tells the trait to only include specified columns when hash checking.

```bash
$includedInHash = ['name','category_id']; // those columns will be the only columns for checking duplication
```

## Cli Usage
If your project has been completed and/or in the development process and want to integrate this package, you can do so by the commands provided by the package.

### Genreate hash column for a model
```bash
php artisan prevent-same-shit:generate-hash-column App\\Models\\YourModel hash_column_name
```

The above command generates a migration file to add a hash column in the database table. The first arg for the above command is the target model. You should provide fully qualified model class. Or if you've implemented morph map in the ``AppServiceProvider``, you can provide morp mapped name of the model. The second parameter is optional for the hash column name, and if you provide the custom name for the model, that name will stick througout the model's hash calculation and checking. You must set the model's ``$recordHashColumn`` property to the name of the hash column.

### Genreate hash column for all models
```bash
php artisan prevent-same-shit:generate-hash-columns hash_column_name
```
The above command generates migration files to add a hash column for each of the models in your project. The second arg is optional for the hash column name.

### Genreate hash values
```bash
php artisan prevent-same-shit:generate-hashes App\\Models\\YourModel hash_column_name
```
The above command generates hash values for a specified model. The second arg is only required for those who customized the hash column name.

### Genreate hash values for all models
```bash
php artisan prevent-same-shit:generate-hashes-all hash_column_name
```
The above command generates hash values for all of the models and don't need to provide model name. And again, the hash_column_name arg is only required for those who customized the hash column name.

## Contributing
You can visit https://github.com/major-phyo-san/prevent-same-shit and contribute the project.