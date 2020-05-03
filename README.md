# Validator
A library of string validators values

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
$validator = new Validator('en'); // `en` by default but you can change it
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

* required
* email
* mask
* in
* notIn
* multiple (for array)
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

## Customisation

* addLocalMessages
* setInputNames (array)
* setInputName
* addError (custom error)




**More information see examples directory**




