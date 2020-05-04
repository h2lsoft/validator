<?php

use h2lsoft\Data\Validator;

include '../src/Validator.php';

// simulate POST
$_POST['name'] = 'the king !';
$_POST['email'] = 'fa@email';
$_POST['password'] = 'azerty;-)';
$_POST['password2'] = 'azerty';
$_POST['zip_code'] = 'a2345';
$_POST['choices'] = ['apple'];
$_POST['job'] = 'webdesigner';
$_POST['experiences'] = '';
$_POST['websites'] = [];
$_POST['days'] = '-50';
$_POST['date'] = '31/20/2020';
$_POST['datetime'] = '31/20/2020 12:00';
$_POST['website'] = 'text.com';
$_POST['regex_test'] = 'abcdef';

// custom fr locale
$locales = [
	"`[FIELD]` must start by x" => "`[FIELD]` doit commencer par x",
	"Please enter at least 5 websites" => "veuillez saisir au moins 5 sites Web"
];

// rules
$validator = new Validator('fr');
$validator->addLocaleMessages('fr', $locales); // add custom messages

$validator->input('name')->required()->alpha(' '); // space allowed
$validator->input('email', "email address")->required()->email();
$validator->input('password', 'mot de passe')->required()->alphaNumeric(' -_!#');
$validator->input('password2', 'mot de passe 2')->sameAs('password');
$validator->input('zip_code', 'zip code')->required()->mask('99999');
$validator->input('choices')->required()->multiple()->in(['banana', 'pear'])->minLength(2);
$validator->input('job')->required()->equal('CEO');
$validator->input('experiences')->requiredIf('job', ['webdesigner', 'CEO'])->minLength(50)->maxLength(800);

if($validator->inputGet('job') == 'webdesigner')
{
	$validator->input('websites')->required()
			  ->multiple()
			  ->min(5, "please enter at least 5 websites");
}

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





