# Validator
A library for string validators values in multilanguage.

[![Version](https://badge.fury.io/gh/h2lsoft%2Fvalidator.svg)](https://badge.fury.io/gh/h2lsoft%2Fvalidator)

## Installation

Install directly via [Composer](https://getcomposer.org):
```bash
$ composer require h2lsoft/validator
```

## Basic Usage


```php
// simulate POST
$_POST['name'] = 'the king !';
$_POST['email'] = 'fa@email';
$_POST['zip_code'] = 'a2345';
$_POST['choices'] = ['apple'];
$_POST['job'] = 'webdesigner';
$_POST['days'] = '-50';
$_POST['date'] = '31/20/2020';
$_POST['datetime'] = '31/20/2020 12:00';
$_POST['website'] = 'text.com';
$_POST['regex_test'] = 'abcdef';
$_POST['conditions'] = 0;

// rules
$validator = new \h2lsoft\Data\Validator('en'); // `en` by default but you can change it
$validator->input('name')->required()->alpha(' '); // space allowed
$validator->input('email', "email address")->required()->email();
$validator->input('zip_code', 'zip code')->required()->mask('99999');
$validator->input('choices')->required()->multiple()->in(['banana', 'pear'])->minLength(2);
$validator->input('job')->required()->equal('CEO');
$validator->input('days')->required()->integer()->between(1, 60);
$validator->input('date')->date('m/d/Y');
$validator->input('datetime')->required()->datetime('m/d/Y H:i');
$validator->input('website')->url(FILTER_FLAG_PATH_REQUIRED);
$validator->input('regex_test')->regex("/^x/i", "`[FIELD]` must start by x");
$validator->input('conditions')->required()->accepted();

if($validator->fails())
{
	print_r($validator->result());
}
else
{
	die("No error detected");
}
```

## Rules

### Strings
* required
* email
* mask
* in
* notIn
* integer
* float
* min
* max
* between
* length
* minLength
* maxLength
* equal
* accepted
* url
* alpha
* alphaNumeric
* date
* datetime
* regex
* ip
* sameAs
* requiredIf


### Array (checkboxes, select multiple)

* multiple
* between
* min
* max


### Files

* fileRequired
* fileMaxSize
* fileExtension
* fileMime
* fileImage
* fileImageBase64
* fileImageWidth
* fileImageHeight


## Customisation

* addLocalMessages
* setInputNames (array)
* setInputName
* addError (custom error)



**More information see examples directory**


## License

MIT. See full [license](LICENSE).


