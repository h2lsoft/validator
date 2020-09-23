<?php

namespace h2lsoft\Data;

use \voku\helper\AntiXSS;

class Validator
{
	const LATINS_CHARS = "éèëêàäâáùüûúîïíöôóñ";
	
	private $values = [];
	private $error_count = 0;
	private $last_input = "";
	
	private $error_stack = [];
	private $error_stack_deep = [];
	private $input_names = [];
	private $error_fields = [];
	
	private $locale = 'en';
	private $locale_messages = [];
	
	
	public function __construct($locale='en', $data='POST')
	{
		if(!is_array($data))
		{
			if($data == 'POST')
				$data = $_POST;
		}
		
		$this->values = $data;
		$this->locale = $locale;
		
		if($this->locale != 'en')
			$this->locale_messages[$this->locale] = include(__DIR__."/locale/{$this->locale}.php");
		
		return $this;
	}
	
	public function addLocaleMessages($locale, $messages)
	{
		foreach($messages as $default => $message)
			$this->locale_messages[$locale][$default] = $message;
	}
	
	private function _ts($message)
	{
		if(isset($this->locale_messages[$this->locale][$message]))
		{
			$message = $this->locale_messages[$this->locale][$message];
		}
		
		return $message;
	}
	
	public function input($name, $label='')
	{
		$this->last_input = $name;
		
		if(!empty($label))
			$this->input_names[$name] = $label;
		
		return $this;
	}
	
	public function setInputNames($names)
	{
		$this->input_names = $names;
		return $this;
	}
	
	public function setInputName($name, $new_name)
	{
		$this->input_names[$name] = $new_name;
		return $this;
	}
	
	public function inputGet($name)
	{
		$v = (isset($this->values[$name])) ? $this->values[$name] : false;
		return $v;
	}
	
	public function inputGetAll()
	{
		return $this->values;
	}
	
	
	public function inputSet($name, $value)
	{
		$this->values[$name] = $value;
		return $this;
	}
	
	public function getInputName($name)
	{
		if(isset($this->input_names[$name]))
			$name = $this->input_names[$name];
		
		return $name;
	}
	
	public function hasErrors()
	{
		return $this->error_count;
	}
	
	
	public function success()
	{
		return !$this->hasErrors();
	}
	
	public function fails()
	{
		return $this->hasErrors();
	}
	
	public function result()
	{
		$tmp = [];
		$tmp['error_count'] = $this->error_count;
		$tmp['error_stack'] = $this->error_stack;
		$tmp['error_stack_deep'] = $this->error_stack_deep;
		$tmp['error_fields'] = $this->error_fields;
		
		return $tmp;
	}
	
	private function escapeChecking()
	{
		return (
			!$this->values ||
			!isset($this->values[$this->last_input]) ||
			(!is_array($this->values[$this->last_input]) && strlen($this->values[$this->last_input]) == 0)
		);
	}
	
	
	// rules ***********************************************************************************************************
	public function addError($message, $params=[], $input='')
	{
		if(empty($input))$input = $this->last_input;
		
		$message = $this->_ts($message);
		$message = str_replace('[FIELD]', $this->getInputName($input), $message);
		
		foreach($params as $key => $val)
			$message = str_replace("[{$key}]", $val, $message);
		
		$this->error_count++;
		$this->error_stack[] = $message;
		$this->error_stack_deep[$input][] = $message;
		
		if(!in_array($input, $this->error_fields))
			$this->error_fields[] = $input;
		
		return $this;
	}
	
	public function required($message='')
	{
		if(!$this->values)return $this;
		
		if(!isset($this->values[$this->last_input]))
		{
			$error = true;
		}
		else
		{
			$v = $this->values[$this->last_input];
			$error = false;
			if(
				(is_array($v) && !count($v)) || (!is_array($v) && !strlen(trim($v)))
			)
				$error = true;
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` is required" : $message;
			$this->addError($message);
		}
		return $this;
	}
	
	public function email($message='')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		if(!filter_var($v, FILTER_VALIDATE_EMAIL))
		{
			$message = (empty($message)) ? "`[FIELD]` must be an email address" : $message;
			$this->addError($message);
		}
		
		return $this;
	}
	
	public function mask($mask, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		if(strlen($v) != strlen($mask))
		{
			$error = true;
		}
		else
		{
			for($i=0; $i < strlen($mask); $i++)
			{
				$mask_letter = $mask[$i];
				if($mask_letter == '9' && !ctype_digit($v[$i]))
				{
					$error = true;
					break;
				}
				elseif($mask_letter == 'a' && !ctype_alpha($v[$i]))
				{
					$error = true;
					break;
				}
				elseif(!in_array($mask_letter, ['*', '9', 'a']) && $mask_letter != $v[$i])
				{
					$error = true;
					break;
				}
			}
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be in format `[MASK]`" : $message;
			$this->addError($message, ['MASK' => $mask]);
		}
		
		return $this;
	}
	
	public function in($list)
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		if(!is_array($v))
		{
			if(!in_array($v, $list))
				$error = true;
		}
		else
		{
			foreach($v as $val)
			{
				if(!in_array($val, $list))
					$error = true;
			}
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must have a valid option" : $message;
			$this->addError($message, []);
		}
		
		
		return $this;
	}
	
	public function notIn($list)
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		if(!is_array($v))
		{
			if(in_array($v, $list))
				$error = true;
		}
		else
		{
			foreach($v as $val)
			{
				if(in_array($val, $list))
					$error = true;
			}
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must not have option `[OPTIONS]`" : $message;
			
			$params = [];
			$params['OPTIONS'] = join(', ', $list);
			$this->addError($message, []);
		}
		
		
		return $this;
	}
	
	public function multiple()
	{
		if(!isset($this->values[$this->last_input]) || !is_array($this->values[$this->last_input]))
		{
			$message = (empty($message)) ? "`[FIELD]` must be an array" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	public function integer($unsigned=true, $message="")
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		$v = $this->values[$this->last_input];
		
		if(is_array($v) || ($v != 0 && !filter_var($v, FILTER_VALIDATE_INT)) || ($unsigned && $v < 0)
		)
		{
			$error = true;
		}
		
		if($error)
		{
			$positive = (!$unsigned) ? '': 'positive';
			
			$message = (empty($message)) ? "`[FIELD]` must be an integer [POSITIVE]" : $message;
			
			$params = [];
			$params['POSITIVE'] = $positive;
			$this->addError($message, $params);
		}
		
		
		return $this;
	}
	
	public function float($unsigned=true, $message="")
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		$v = $this->values[$this->last_input];
		
		if(is_array($v) || ($v != 0 && !filter_var($v, FILTER_VALIDATE_FLOAT)) || ($unsigned && $v < 0)
		)
		{
			$error = true;
		}
		
		if($error)
		{
			$positive = (!$unsigned) ? '': 'positive';
			
			$message = (empty($message)) ? "`[FIELD]` must be a float [POSITIVE]" : $message;
			$this->addError($message, ['POSITIVE' => $positive]);
		}
		
		
		return $this;
	}
	
	public function min($min, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if(
			(is_array($v) && count($v) < $min) ||
			(!is_array($v) && $v < $min)
		)
		{
			$error = true;
		}
		
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` must be greater than `[MIN]`" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have `[MIN]` choices selected minimum" : $message;
			
			$this->addError($message, ['MIN' => $min]);
		}
		
		return $this;
	}
	
	public function max($max, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if(
			(is_array($v) && count($v) > $max) ||
			(!is_array($v) && $v > $max)
		)
		{
			$error = true;
		}
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` must be lower than `[MAX]`" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have `[MAX]` choices selected maximum" : $message;
			
			$this->addError($message, ['MAX' => $max]);
		}
		
		return $this;
	}
	
	public function between($min, $max, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if(
			(is_array($v) && (count($v) < $min || count($v) > $max) ) ||
			(!is_array($v) && ($v < $min || $v > $max))
		)
		{
			$error = true;
		}
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` must be between `[MIN]` and `[MAX]`" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have choices selected between `[MIN]` and `[MAX]`" : $message;
			
			$this->addError($message, ['MIN' => $min, 'MAX' => $max]);
		}
		
		return $this;
	}
	
	public function length($length, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if((!is_array($v) && strlen(trim($v)) != $length) || (is_array($v) && count($v) != $length))
			$error = true;
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` length must be equal to `[LENGTH]`" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have `[LENGTH]` choices selected" : $message;
			
			$this->addError($message, ['LENGTH' => $length]);
		}
		
		return $this;
	}
	
	public function minLength($length, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if((!is_array($v) && strlen(trim($v)) < $length) || (is_array($v) && count($v) < $length))
			$error = true;
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` length must be `[LENGTH]` minimum" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have `[LENGTH]` choices selected minimum" : $message;
			
			$this->addError($message, ['LENGTH' => $length]);
		}
		
		return $this;
	}
	
	public function maxLength($length, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		
		$error = false;
		if((!is_array($v) && strlen(trim($v)) > $length) || (is_array($v) && count($v) > $length))
			$error = true;
		
		
		if($error)
		{
			if(!is_array($v))
				$message = (empty($message)) ? "`[FIELD]` length must be `[LENGTH]` character maximum" : $message;
			else
				$message = (empty($message)) ? "`[FIELD]` must have `[LENGTH]` choices selected maximum" : $message;
			
			$this->addError($message, ['LENGTH' => $length]);
		}
		
		return $this;
	}
	
	public function equal($value, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if(strlen(trim($v)) > 0 && $v != $value)
			$error = true;
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be equal to `[VALUE]`" : $message;
			$this->addError($message, ['VALUE' => $value]);
		}
		
		return $this;
	}
	
	public function accepted($message='')
	{
		if(!$this->values)return $this;
		
		$error = false;
		if(!isset($this->values[$this->last_input]) || is_array($this->values[$this->last_input]) || !in_array($this->values[$this->last_input], ['yes', 'YES', 1]))
			$error = true;
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be accepted" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	public function url($php_filters_flags='', $message='')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		if(is_array($v) || !filter_var($v, FILTER_VALIDATE_URL, $php_filters_flags))
			$error = true;
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid url" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	public function alpha($exceptions="", $latin_chars_allowed=true, $min_allowed=true, $capital_allowed=true, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		if(is_array($this->values[$this->last_input]))
		{
			$error = true;
		}
		else
		{
			$v = $this->values[$this->last_input];
			
			if($latin_chars_allowed)
			{
				$latins = $this::LATINS_CHARS;
				if(!$min_allowed && $capital_allowed)
					$exceptions .= strtoupper($latins);
			}
			
			if(!empty($exceptions))
			{
				for($i=0; $i < strlen($exceptions); $i++)
					$v = str_replace($exceptions{$i}, 'x', $v);
			}
			
			if($min_allowed && $capital_allowed && !ctype_alpha($v))
				$error = true;
			elseif($min_allowed && !$capital_allowed && !ctype_lower($v))
				$error = true;
			elseif(!$min_allowed && $capital_allowed && !ctype_upper($v))
				$error = true;
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must contain only alphabetic characters" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	public function alphaNumeric($exceptions="", $latin_chars_allowed=true, $min_allowed=true, $capital_allowed=true, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$error = false;
		if(is_array($this->values[$this->last_input]))
		{
			$error = true;
		}
		else
		{
			$v = $this->values[$this->last_input];
			
			$exceptions .= '0123456789';
			
			if($latin_chars_allowed)
			{
				$latins = $this::LATINS_CHARS;
				if(!$min_allowed && $capital_allowed)
					$exceptions .= strtoupper($latins);
			}
			
			if(!empty($exceptions))
			{
				for($i=0; $i < strlen($exceptions); $i++)
					$v = str_replace($exceptions{$i}, 'x', $v);
			}
			
			if($min_allowed && $capital_allowed && !ctype_alpha($v))
				$error = true;
			elseif($min_allowed && !$capital_allowed && !ctype_lower($v))
				$error = true;
			elseif(!$min_allowed && $capital_allowed && !ctype_upper($v))
				$error = true;
		}
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must contain only alphabetic and numeric characters" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	public function date($format='Y-m-d')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		
		if(is_array($v))
		{
			$error = true;
		}
		else
		{
			$d = \Datetime::createFromFormat($format, $v);
			$d_errors = \Datetime::getLastErrors();
			
			if(@$d_errors['warning_count'] > 0 || @$d_errors['error_count'] > 0)
				$error = true;
		}
		
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid date in format `[FORMAT]`" : $message;
			
			$params = [];
			$params['FORMAT'] = $format;
			
			$this->addError($message, $params);
		}
		
		
		
		return $this;
	}
	
	public function datetime($format='Y-m-d H:i:s')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		$error = false;
		
		if(is_array($v))
		{
			$error = true;
		}
		else
		{
			$d = \Datetime::createFromFormat($format, $v);
			$d_errors = \Datetime::getLastErrors();
			
			if(@$d_errors['warning_count'] > 0 || @$d_errors['error_count'] > 0)
				$error = true;
		}
		
		
		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid date in format `[FORMAT]`" : $message;
			
			$params = [];
			$params['FORMAT'] = $format;
			
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	public function regex($pattern, $message)
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		if(is_array($v) || !(preg_match($pattern, $v)))
		{
			$this->addError($this->_ts($message), []);
		}
		
		return $this;
	}
	
	public function notRegex($pattern, $message)
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		if(is_array($v) || (preg_match($pattern, $v)))
		{
			$this->addError($this->_ts($message), []);
		}
		
		return $this;
	}
	
	public function ip($php_flags='', $message='')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		if(is_array($v) || !filter_var($v, FILTER_VALIDATE_IP, $php_flags))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid ip address" : $message;
			
			$params = [];
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	
	public function sameAs($input_parent, $message='')
	{
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		
		if(!isset($this->values[$input_parent]) || $v != $this->values[$input_parent])
		{
			$message = (empty($message)) ? "`[FIELD]` must be equal to `[FIELD_PARENT]`" : $message;
			
			$params = [];
			$params['FIELD_PARENT'] = $this->getInputName($input_parent);
			
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	public function requiredIf($input_parent, $value, $message='')
	{
		if(
			isset($this->values[$input_parent]) &&
			(
				(!is_array($this->values[$input_parent]) && !empty($this->values[$input_parent])) ||
				(is_array($this->values[$input_parent]) && count($this->values[$input_parent]) > 0)
			) &&
			(
				(!is_array($value) && $this->values[$input_parent] == $value) ||
				(is_array($value) && in_array($this->values[$input_parent], $value))
			)
		)
		{
			return $this->required($message);
		}
		
		return $this;
	}
	
	
	public function fileRequired($message='')
	{
		if(!$this->values)return $this;
		
		if(!isset($_FILES[$this->last_input]) || !isset($_FILES[$this->last_input]['size']) || !$_FILES[$this->last_input]['size'])
		{
			$message = (empty($message)) ? "`[FIELD]` file is required" : $message;
			$this->addError($message, []);
		}
		
		return $this;
	}
	
	
	public function fileExtension($extensions, $message='')
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['name']) || empty($_FILES[$this->last_input]['name']))return $this;
		
		$ext = explode('.', $_FILES[$this->last_input]['name']);
		$ext = strtolower(end($ext));
		
		if(!in_array($ext, $extensions))
		{
			$message = (empty($message)) ? "`[FIELD]` extension must be `[EXTENSIONS]`" : $message;
			
			$params = [];
			$params['EXTENSIONS'] = join(', ', $extensions);
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	public function fileMaxSize($size, $message='')
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['size']) || empty($_FILES[$this->last_input]['name']))return $this;
		
		$size_octet = strtolower($size);
		$size_octet = str_replace(' ', '', $size_octet);
		
		// unit conversion
		$unit = 1;
		if(strpos($size_octet, 'ko') !== false || strpos($size_octet, 'kb') !== false)
			$unit = 1000;
		elseif(strpos($size_octet, 'mo') !== false || strpos($size_octet, 'mb') !== false)
			$unit = 1000*1000;
		elseif(strpos($size_octet, 'go') !== false || strpos($size_octet, 'gb') !== false)
			$unit = 1000*1000*1000;
		
		$size_octet = (int)(str_replace(['ko','kb','mo','mb','go','gb'], '', $size_octet)) * $unit;
		
		$file_size = $_FILES[$this->last_input]['size'];
		if($file_size > $size_octet)
		{
			$message = (empty($message)) ? "`[FIELD]` file must be lower than `[SIZE]`" : $message;
			
			$params = [];
			$params['SIZE'] = $size;
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	
	public function fileImage($extensions=['jpg', 'jpeg', 'gif', 'png', 'svg'])
	{
		$extensions = array_map('strtolower', $extensions);
		
		$mimes = [];
		foreach($extensions as $extension)
		{
			$mimes[] = "image/{$extension}";
			if($extension == 'jpg')
				$mimes[] = "image/jpeg";
		}
		
		$mimes = array_unique($mimes);
		return $this->fileExtension($extensions)->fileMime($mimes);
	}
	
	public function fileMime($mimes=[])
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['type']) || empty($_FILES[$this->last_input]['name']))return $this;
		$mimes = array_map('strtolower', $mimes);
		
		$file_mime = $_FILES[$this->last_input]['type'];
		
		if(!in_array($file_mime, $mimes))
		{
			$message = (empty($message)) ? "`[FIELD]` must be `[MIMES]` (not `[FILE_MIME]`)" : $message;
			
			$params = [];
			$params['MIMES'] = join(', ', $mimes);
			$params['FILE_MIME'] = $file_mime;
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	
	public function fileUploaded()
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['tmp_name']))return $this;
		
		if(!@is_uploaded_file($_FILES[$this->last_input]['tmp_name']))
		{
			$message = (empty($message)) ? "`[FIELD]` is not an uploaded file" : $message;
			
			$params = [];
			$this->addError($message, $params);
		}
		
		return $this;
	}
	
	public function fileImageBase64($ext='png', $create_file=true)
	{
		if(!$this->values)return $this;
		
		
		$error = false;
		if(!isset($this->values[$this->last_input]))
			$error = true;
		else
		{
			$v = $this->values[$this->last_input];
			// split the string on commas
			// $data[ 0 ] == "data:image/png;base64"
			// $data[ 1 ] == <actual base64 string>
			$data = explode(',', $v);
			
			if($data[0] != "data:image/{$ext};base64")
			{
				$message = (empty($message)) ? "`[FIELD]` must be a valid file" : $message;
				
				$params = [];
				$this->addError($message, $params);
			}
			else
			{
				$im = @imagecreatefromstring(base64_decode($data[1]));
				if(!$im)
				{
					$message = (empty($message)) ? "`[FIELD]` must be a valid file" : $message;
					
					$params = [];
					$this->addError($message, $params);
				}
				elseif($create_file)
				{
					$tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
					$tmp_fname = tempnam($tmp_dir, "BIX");
					
					$contents = base64_decode($data[1]);
					file_put_contents($tmp_fname, $contents);
					
					$_FILES[$this->last_input]['type'] = "image/{$ext}";
					$_FILES[$this->last_input]['name'] = basename($tmp_fname).".{$ext}";
					$_FILES[$this->last_input]['tmp_name'] = $tmp_fname;
					$_FILES[$this->last_input]['size'] = strlen($contents);
					$_FILES[$this->last_input]['error'] = 0;
				}
			}
			
			
			
		}
		
		
		
	}
	
	
	
}

