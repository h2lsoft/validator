<?php

use h2lsoft\Data\Validator;

include '../src/Validator.php';

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

// rules
$validator = new Validator('en');
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
$validator->input('conditions')->accepted();

if($validator->fails())
{
	echo "<pre>";
	print_r($validator->result());
	echo "</pre>";
}
else
{
	die("No error detected");
}




