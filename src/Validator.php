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
		if($this->escapeChecking())return $this;
		
		$v = $this->values[$this->last_input];
		if(!is_array($v))
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
		
		if(is_array($v) || !ctype_digit($v) || ($unsigned && $v <= 0)
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
		
		if(is_array($v) || !filter_var($v, FILTER_VALIDATE_FLOAT) || ($unsigned && $v <= 0)
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
	
	
	
}

