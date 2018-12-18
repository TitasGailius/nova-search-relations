# Search relationships in Laravel Nova

This package allows you to include relationship columns into Laravel Nova search query.

## Screenshot

![screenshot of the search relations tool](./screenshot.png)

## Installation

```
composer require titasgailius/search-relations
```

Next, add `Titasgailius\SearchRelations\SearchesRelations` trait to your base resource class `App\Nova\Resource`
```php
use Titasgailius\SearchRelations\SearchesRelations;

abstract class Resource extends NovaResource
{
    use SearchesRelations;
```

## Usage

Simply add `public static $searchRelations` array to any of your Nova resources.
This array has a relationship name as a key and an array of columns to search for as a value.
```php
/**
 * The relationship columns that should be searched.
 *
 * @var array
 */
public static $searchRelations = [
    'user' => ['username', 'email'],
];
```

## Global search

You may disable global search for relationship columns by defining `$searchRelationsGlobally` property in your nova resource:

```php
/**
 * Determine if relations should be searched globally.
 *
 * @var array
 */
public static $searchRelationsGlobally = false;
```

When you have disabled global search for relationships, you may still enable it for specific relationships like this:
```php
/**
 * Determine if relations should be searched globally.
 *
 * @var array
 */
public static $searchRelationsGlobally = false;

/**
 * The relationship columns that should be searched globally.
 *
 * @var array
 */
public static $globalSearchRelations = [
    'user' => ['email'],
];

```

Now when searching globally, Laravel Nova is going to **ignore** relationships declared in `$searchRelations` and is going to use `$globalSearchRelations` instead.

## Nested relationships

You may search nested relationships using dot notation.

```php
/**
 * The relationship columns that should be searched.
 *
 * @var array
 */
public static $searchRelations = [
    'user.country' => ['code'],
];
```
