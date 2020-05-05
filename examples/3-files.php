<?php
/**
 * files examples
 */

use h2lsoft\Data\Validator;

include '../src/Validator.php';

// rules
$validator = new Validator();
$validator->input('Name')->required()->alpha();
$validator->input('CV')->fileRequired()
					   ->fileExtension(['pdf','doc','docx','rtf'])
					   ->fileMaxSize('150 ko');

$validator->input('Photo')->fileRequired()
		  			   ->fileImage(['jpg'])
		  			   ->fileMaxSize('150 ko');


$errors = [];
$errors_str = '';
$name = $validator->inputGet('Name');

if($validator->fails())
{
	$errors = $validator->result()['error_stack'];
	
	$errors_str = '<div class="alert alert-danger">';
	foreach($errors as $error)
		$errors_str .= " &bull; {$error}<br>";
	$errors_str .= '</div>';
}




echo <<<HTML
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	    <title>Files test example</title>
        
        <link rel="stylesheet" href="https://bootswatch.com/4/yeti/bootstrap.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
        <style>
        label[required]:after {content:" * "; color:red}
        .form-heading {border-bottom:1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;}
        </style>
  </head>
  <body>
  
    <div class="container">
    
	<form  name="form" method="post"  action="" enctype="multipart/form-data">
	
		<div class="row">
			<div class="col">
				<h1  class="form-heading">File example</h1>
			</div>
		</div>
		
		{$errors_str}
		
		<div class="form-group row">
			<label class="col-sm-3 col-form-label text-left" for="Name" required>Name</label>
			<div class="col">
				<input type="text" name="Name" id="Name" value="{$name}" class=" form-control" >
			</div>
		</div>
		
		<div class="form-group row">
			<label class="col-sm-3 col-form-label text-left" for="Photo" required>Upload your photo</label>
			<div class="col">
				<input type="file" name="Photo" id="Photo" class="">
				<small id="PhotoHelpBlock" class="form-text text-muted">accepted File Types : .jpg</small>
			</div>
		</div>
		
		<div class="form-group row">
			<label class="col-sm-3 col-form-label text-left" for="CV" required>Upload your CV</label>
			<div class="col">
				<input type="file" name="CV" id="CV" value="" class="">
				<small id="CVHelpBlock" class="form-text text-muted">accepted File Types : .pdf, .doc[x], .rtf</small>
			</div>
		</div>
		
		<div class="row row-form-footer">
			<div class="col text-center"></div>
			<div class="col text-right">
				<button type="submit" class="btn btn-primary btn-submit" name="submit" value="1">Submit my CV</button>
			</div>
		</div>
		
    </form>
    
    </div>
  
  
  </body>
</html>
HTML;

