# Validator

A fluent, chainable PHP data validator with built-in internationalization support.

[![Version](https://badge.fury.io/gh/h2lsoft%2Fvalidator.svg)](https://badge.fury.io/gh/h2lsoft%2Fvalidator)

## Requirements

- PHP >= 7.4 (PHP 8+ recommended)
- ext-mbstring

## Installation

Install via [Composer](https://getcomposer.org):
```bash
composer require h2lsoft/validator
```

## Quick Start

```php
$validator = new \h2lsoft\Data\Validator();

$validator->input('name')->required()->alpha(' ');
$validator->input('email', 'email address')->required()->email();
$validator->input('age')->required()->integer()->between(18, 99);

if($validator->fails())
{
    print_r($validator->result());
}
```

## Usage

### Creating a validator

```php
// default: locale 'en', data from $_POST
$v = new \h2lsoft\Data\Validator();

// with locale
$v = new \h2lsoft\Data\Validator('fr');

// with custom data
$v = new \h2lsoft\Data\Validator('en', ['name' => 'John', 'age' => 25]);
```

### Defining rules

Chain rules fluently after selecting an input with `input()`:

```php
$v->input('email', 'Email address')->required()->email();
$v->input('zip_code', 'Zip code')->required()->mask('99999');
$v->input('password')->required()->minLength(8);
$v->input('password_confirm')->required()->sameAs('password');
$v->input('age')->required()->integer()->between(18, 120);
$v->input('website')->url();
$v->input('start_date')->date('Y-m-d');
$v->input('config')->json();
```

### Checking results

```php
if($v->success())
{
    // validation passed
}

if($v->fails())
{
    $result = $v->result();
    // $result['error']            => true/false
    // $result['error_count']      => int
    // $result['error_stack']      => ['message 1', 'message 2', ...]
    // $result['error_stack_html'] => HTML formatted errors
    // $result['error_stack_deep'] => ['field_name' => ['message 1', ...]]
    // $result['error_fields']     => ['field1', 'field2', ...]
}
```

### Custom error messages

Every rule accepts an optional `$message` parameter:

```php
$v->input('name')->required('Please enter your name');
$v->input('age')->integer(true, 'Age must be a positive number');
```

Use `[FIELD]` placeholder to reference the field label:

```php
$v->input('email', 'Email')->required('`[FIELD]` cannot be empty');
// => "`Email` cannot be empty"
```

### Custom errors

```php
$v->addError('Something went wrong', [], 'field_name');
```

### Custom validation with callbacks

Use `custom()` for business logic that doesn't fit standard rules:

```php
// check that a promo code exists in database
$v->input('promo_code')->required()->custom(function($value) {
    return PromoCode::findOne(['code' => $value]) !== null;
}, 'Invalid promo code');

// check that an email is not already taken
$v->input('email')->required()->email()->custom(function($value) {
    return User::count(['email' => $value]) === 0;
}, '`[FIELD]` is already taken');

// business rule: minimum amount depends on country
$v->input('amount')->required()->float()->custom(function($value) {
    $minimums = ['FR' => 10, 'US' => 5, 'UK' => 8];
    return $value >= ($minimums[$_POST['country']] ?? 0);
}, '`[FIELD]` is below the minimum for your country');
```

## Available Rules

### String / Value Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required($msg)` | Must be present and not empty | `->required()` |
| `email($msg)` | Must be a valid email | `->email()` |
| `mask($mask, $msg)` | Must match a mask (`9`=digit, `a`=letter, `*`=any) | `->mask('99-aaa')` |
| `in($list, $msg)` | Value must be in the list | `->in(['a', 'b', 'c'])` |
| `notIn($list, $msg)` | Value must not be in the list | `->notIn(['x', 'y'])` |
| `integer($unsigned, $msg)` | Must be an integer | `->integer()` |
| `float($unsigned, $msg)` | Must be a float | `->float(false)` |
| `min($min, $msg)` | Value >= min (or array count) | `->min(1)` |
| `max($max, $msg)` | Value <= max (or array count) | `->max(100)` |
| `between($min, $max, $msg)` | Value between min and max | `->between(1, 10)` |
| `length($len, $msg)` | Exact character length | `->length(5)` |
| `minLength($len, $msg)` | Minimum character length | `->minLength(3)` |
| `maxLength($len, $msg)` | Maximum character length | `->maxLength(255)` |
| `equal($value, $msg)` | Must equal a given value | `->equal('yes')` |
| `accepted($msg)` | Must be `'yes'`, `'YES'` or `1` | `->accepted()` |
| `boolean($msg)` | Must be a boolean-like value (true/false/1/0/yes/no) | `->boolean()` |
| `password($min, $upper, $digit, $special, $msg)` | Password strength validation | `->password(8, true, true, true)` |
| `url($flags, $msg)` | Must be a valid URL | `->url()` |
| `alpha($exc, $latin, $min, $cap, $msg)` | Alphabetic only | `->alpha(' -')` |
| `alphaNumeric($exc, $latin, $min, $cap, $msg)` | Alphanumeric only | `->alphaNumeric('_')` |
| `date($format, $msg)` | Valid date | `->date('d/m/Y')` |
| `datetime($format, $msg)` | Valid datetime | `->datetime('d/m/Y H:i')` |
| `dateBefore($date, $format, $msg)` | Must be before a given date | `->dateBefore('2025-12-31', 'Y-m-d')` |
| `dateAfter($date, $format, $msg)` | Must be after a given date | `->dateAfter(date('Y-m-d'), 'Y-m-d')` |
| `time($format, $msg)` | Valid time | `->time('H:i')` |
| `timezone($msg)` | Valid timezone identifier | `->timezone()` |
| `country($msg)` | Valid ISO 3166-1 alpha-2 country code | `->country()` |
| `language($msg)` | Valid ISO 639-1 language code | `->language()` |
| `currency($msg)` | Valid ISO 4217 currency code | `->currency()` |
| `iban($msg)` | Valid IBAN with MOD-97 checksum | `->iban()` |
| `bic($msg)` | Valid BIC/SWIFT code (8 or 11 chars) | `->bic()` |
| `uuid($msg)` | Valid UUID (RFC 4122, v1-v5) | `->uuid()` |
| `macAddress($msg)` | Valid MAC address (colon or hyphen) | `->macAddress()` |
| `cssColor($msg)` | Valid CSS color (hex, rgb, hsl, named) | `->cssColor()` |
| `regex($pattern, $msg)` | Must match a regex | `->regex('/^\d+$/', 'Digits only')` |
| `notRegex($pattern, $msg)` | Must not match a regex | `->notRegex('/admin/i', 'Forbidden')` |
| `ip($flags, $msg)` | Valid IP address | `->ip()` |
| `ipV4($msg)` | Valid IPv4 address | `->ipV4()` |
| `ipV6($msg)` | Valid IPv6 address | `->ipV6()` |
| `json($msg)` | Valid JSON string | `->json()` |
| `jsonArray($msg)` | Valid JSON array (`[...]`) | `->jsonArray()` |
| `jsonObject($msg)` | Valid JSON object (`{...}`) | `->jsonObject()` |
| `sameAs($field, $msg)` | Must equal another field's value | `->sameAs('password')` |
| `different($field, $msg)` | Must differ from another field's value | `->different('old_password')` |
| `requiredIf($field, $value, $msg)` | Required only when another field matches | `->requiredIf('type', 'pro')` |
| `csrfToken($token, $msg, $regenerate)` | CSRF token validation (auto-regenerates on failure by default) | `->csrfToken()` |
| `custom($callback, $msg)` | Custom validation via callback | `->custom(fn($v) => $v > 10, 'Too low')` |

### Array Rules (checkboxes, multi-select)

| Rule | Description | Example |
|------|-------------|---------|
| `multiple($msg)` | Must be an array | `->multiple()` |
| `min($min, $msg)` | Minimum selections | `->min(2)` |
| `max($max, $msg)` | Maximum selections | `->max(5)` |
| `between($min, $max, $msg)` | Selections between min and max | `->between(1, 3)` |

### File Rules

| Rule | Description | Example |
|------|-------------|---------|
| `fileRequired($msg)` | File must be uploaded | `->fileRequired()` |
| `fileExtension($ext, $msg)` | Allowed extensions | `->fileExtension(['jpg', 'png'])` |
| `fileMaxSize($size, $msg)` | Max file size (kb, mb, gb) | `->fileMaxSize('2mb')` |
| `fileImage($ext)` | Must be an image | `->fileImage()` |
| `fileMime($mimes, $msg)` | Allowed MIME types | `->fileMime(['application/pdf'])` |
| `fileUploaded($msg)` | Must be a real upload | `->fileUploaded()` |
| `fileImageWidth($w, $strict, $msg)` | Image width constraint | `->fileImageWidth(800, true)` |
| `fileImageHeight($h, $strict, $msg)` | Image height constraint | `->fileImageHeight(600, true)` |
| `fileImageBase64($ext, $create, $msg)` | Validate base64 image | `->fileImageBase64('png')` |

## Internationalization

### Supported locales

| Code | Language |
|------|----------|
| `en` | English (default) |
| `fr` | French |
| `es` | Spanish |
| `pt` | Portuguese |
| `de` | German |
| `it` | Italian |

### Setting the locale

```php
$v = new \h2lsoft\Data\Validator('fr');

// or after creation
$v->setLocale('es');
```

### Adding custom translations

```php
$v->addLocaleMessages('fr', [
    "`[FIELD]` is required" => "`[FIELD]` est obligatoire",
]);
```

## Utility Methods

| Method | Description |
|--------|-------------|
| `setData(array $data)` | Replace the data to validate |
| `inputGet($name, $default)` | Get a value from the data |
| `inputSet($name, $value)` | Set a value in the data |
| `inputGetAll()` | Get all data values |
| `setInputNames(array $names)` | Set display labels for multiple fields |
| `setInputName($name, $label)` | Set a display label for one field |
| `hasErrors()` | Get the error count |
| `toJson($flags)` | Get result as JSON (auto-sets Content-Type header) |
| `reset()` | Reset errors and state for reuse with new data |

## Full Example

```php
$_POST['name'] = 'the king !';
$_POST['email'] = 'bad@email';
$_POST['zip_code'] = 'a2345';
$_POST['choices'] = ['apple'];
$_POST['job'] = 'webdesigner';
$_POST['days'] = '-50';
$_POST['date'] = '31/20/2020';
$_POST['website'] = 'text.com';
$_POST['config'] = '{"valid": true}';
$_POST['ip'] = '192.168.1.1';
$_POST['conditions'] = 0;

$v = new \h2lsoft\Data\Validator('en');

$v->input('name')->required()->alpha(' ');
$v->input('email', 'email address')->required()->email();
$v->input('zip_code', 'zip code')->required()->mask('99999');
$v->input('choices')->required()->multiple()->in(['banana', 'pear'])->minLength(2);
$v->input('job')->required()->equal('CEO');
$v->input('days')->required()->integer()->between(1, 60);
$v->input('date')->date('m/d/Y');
$v->input('website')->url(FILTER_FLAG_PATH_REQUIRED);
$v->input('config')->json();
$v->input('ip', 'IP address')->ipV4();
$v->input('job')->custom(fn($v) => strlen($v) >= 2, '`[FIELD]` is too short');
$v->input('conditions')->required()->accepted();

if($v->fails())
{
    print_r($v->result());
}
```

## License

MIT. See full [license](LICENSE).
