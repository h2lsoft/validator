<?php

namespace h2lsoft\Data;

/**
 * Fluent data validator with chainable rules and i18n support.
 */
class Validator
{
	const VERSION = '2.0.1';
	const LATINS_CHARS = "éèëêàäâáùüûúîïíìöôóòõãñçýÿ";

	protected array $values = [];
	protected int $error_count = 0;
	protected string $last_input = "";

	protected array $error_stack = [];
	protected array $error_stack_deep = [];
	protected array $input_names = [];
	protected array $error_fields = [];

	protected string $locale = 'en';
	protected array $locale_messages = [];

	/**
	 * Set the locale for error messages.
	 *
	 * @param string $locale locale code (e.g. 'fr', 'en')
	 * @return self
	 */
	public function setLocale(string $locale): self
	{
		$this->locale = $locale;

		if($this->locale != 'en')
			$this->locale_messages[$this->locale] = include(__DIR__."/locale/{$this->locale}.php");

		return $this;
	}

	/**
	 * Set the data array to validate.
	 *
	 * @param array $data key-value pairs to validate
	 * @return self
	 */
	public function setData(array $data): self
	{
		$this->values = $data;
		return $this;
	}

	/**
	 * Create a new Validator instance.
	 *
	 * @param string $locale locale code for error messages
	 * @param array|string $data data to validate, or 'POST' to use $_POST
	 */
	public function __construct(string $locale='en', $data='POST')
	{
		if(!is_array($data))
		{
			if($data == 'POST')$data = $_POST;
		}

		$this->values = $data;
		$this->locale = $locale;

		if($this->locale != 'en')
			$this->locale_messages[$this->locale] = include(__DIR__."/locale/{$this->locale}.php");
	}

	/**
	 * Add or override locale messages.
	 *
	 * @param string $locale locale code
	 * @param array $messages ['english default' => 'translated message']
	 * @return void
	 */
	public function addLocaleMessages(string $locale, array $messages): void
	{
		foreach($messages as $default => $message)
			$this->locale_messages[$locale][$default] = $message;
	}

	/**
	 * Translate a message string using the current locale.
	 *
	 * @param string $message message to translate
	 * @return string translated message or original if no translation found
	 */
	protected function _ts(string $message): string
	{
		if(isset($this->locale_messages[$this->locale][$message]))
		{
			$message = $this->locale_messages[$this->locale][$message];
		}

		return $message;
	}

	/**
	 * Set the current input to validate.
	 *
	 * @param string $name input field name
	 * @param string $label display label for error messages
	 * @return self
	 */
	public function input(string $name, string $label=''): self
	{
		$this->last_input = $name;

		if(!empty($label))
			$this->input_names[$name] = $label;

		return $this;
	}

	/**
	 * Set all input display names at once.
	 *
	 * @param array $names ['field_name' => 'Display Label']
	 * @return self
	 */
	public function setInputNames(array $names): self
	{
		$this->input_names = $names;
		return $this;
	}

	/**
	 * Set a display name for a specific input.
	 *
	 * @param string $name input field name
	 * @param string $new_name display label
	 * @return self
	 */
	public function setInputName(string $name, string $new_name): self
	{
		$this->input_names[$name] = $new_name;
		return $this;
	}

	/**
	 * Get a value from the data by key.
	 *
	 * @param string $name input field name
	 * @param mixed $default default value if key does not exist
	 * @return mixed
	 */
	public function inputGet(string $name, $default='')
	{
		$v = (isset($this->values[$name])) ? $this->values[$name] : $default;
		return $v;
	}

	/**
	 * Get all data values.
	 *
	 * @return array
	 */
	public function inputGetAll(): array
	{
		return $this->values;
	}

	/**
	 * Set a value in the data.
	 *
	 * @param string $name input field name
	 * @param mixed $value value to set
	 * @return self
	 */
	public function inputSet(string $name, $value): self
	{
		$this->values[$name] = $value;
		return $this;
	}

	/**
	 * Get the display name for an input field.
	 *
	 * @param string $name input field name
	 * @return string display name or original name if not set
	 */
	public function getInputName(string $name): string
	{
		if(isset($this->input_names[$name]))
			$name = $this->input_names[$name];

		return $name;
	}

	/**
	 * Get the number of errors.
	 *
	 * @return int error count
	 */
	public function hasErrors(): int
	{
		return $this->error_count;
	}

	/**
	 * Check if validation passed (no errors).
	 *
	 * @return bool true if no errors
	 */
	public function success(): bool
	{
		return !$this->hasErrors();
	}

	/**
	 * Check if validation failed (has errors).
	 *
	 * @return bool true if there are errors
	 */
	public function fails(): bool
	{
		return (bool)$this->hasErrors();
	}

	/**
	 * Get the full validation result as an associative array.
	 *
	 * @return array ['error' => bool, 'error_count' => int, 'error_stack' => array, 'error_stack_html' => string, 'error_stack_deep' => array, 'error_fields' => array]
	 */
	public function result(): array
	{
		$tmp = [];

		$tmp['error'] = ($this->error_count > 0) ? true : false;
		$tmp['error_count'] = $this->error_count;
		$tmp['error_stack'] = $this->error_stack;
		$tmp['error_stack_html'] = '&bull; '.join("<br>\n&bull; ", $this->error_stack);
		$tmp['error_stack_deep'] = $this->error_stack_deep;
		$tmp['error_fields'] = $this->error_fields;

		return $tmp;
	}

	/**
	 * Get the validation result as a JSON string.
	 * Automatically sets the Content-Type header to application/json if not already sent.
	 *
	 * @param int $flags json_encode flags (default: 0)
	 * @return string JSON encoded result
	 */
	public function toJson(int $flags=0): string
	{
		if(!headers_sent())
		{
			$has_content_type = false;
			foreach(headers_list() as $header)
			{
				if(stripos($header, 'content-type:') === 0)
				{
					$has_content_type = true;
					break;
				}
			}

			if(!$has_content_type)
				header('Content-Type: application/json; charset=utf-8');
		}

		return json_encode($this->result(), $flags);
	}

	/**
	 * Reset the validator state (errors, fields) for reuse.
	 *
	 * @return self
	 */
	public function reset(): self
	{
		$this->error_count = 0;
		$this->last_input = "";
		$this->error_stack = [];
		$this->error_stack_deep = [];
		$this->error_fields = [];

		return $this;
	}

	/**
	 * Check if the current input should be skipped (empty or missing).
	 *
	 * @return bool true if the input should be skipped
	 */
	protected function escapeChecking(): bool
	{
		return (
			!$this->values ||
			!isset($this->values[$this->last_input]) ||
			(!is_array($this->values[$this->last_input]) && strlen($this->values[$this->last_input]) == 0)
		);
	}


	// rules ***********************************************************************************************************

	/**
	 * Add a custom error message to the error stack.
	 *
	 * @param string $message error message with optional [FIELD] and custom placeholders
	 * @param array $params placeholder replacements ['KEY' => 'value']
	 * @param string $input input field name (defaults to current input)
	 * @return self
	 */
	public function addError(string $message, array $params=[], string $input=''): self
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

	/**
	 * Validate that the input is present and not empty.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function required(string $message=''): self
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

	/**
	 * Validate that the input is a valid email address.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function email(string $message=''): self
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

	/**
	 * Validate that the input matches a mask pattern.
	 * Mask tokens: 9 = digit, a = letter, * = any character.
	 *
	 * @param string $mask mask pattern (e.g. '999-aaa')
	 * @param string $message custom error message
	 * @return self
	 */
	public function mask(string $mask, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$error = false;

		$v = $this->values[$this->last_input];
		if(mb_strlen($v) != strlen($mask))
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

	/**
	 * Validate that the input value is in a given list.
	 *
	 * @param array $list allowed values
	 * @param string $message custom error message
	 * @return self
	 */
	public function in(array $list, string $message=''): self
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

	/**
	 * Validate that the input value is not in a given list.
	 *
	 * @param array $list forbidden values
	 * @param string $message custom error message
	 * @return self
	 */
	public function notIn(array $list, string $message=''): self
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
			$this->addError($message, $params);
		}


		return $this;
	}

	/**
	 * Validate that the input is an array (for multiple selects).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function multiple(string $message=''): self
	{
		if(!isset($this->values[$this->last_input]) || !is_array($this->values[$this->last_input]))
		{
			$message = (empty($message)) ? "`[FIELD]` must be an array" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid integer.
	 *
	 * @param bool $unsigned if true, only positive integers are allowed
	 * @param string $message custom error message
	 * @return self
	 */
	public function integer(bool $unsigned=true, string $message=""): self
	{
		if($this->escapeChecking())return $this;

		$error = false;
		$v = $this->values[$this->last_input];

		if(is_array($v) || filter_var($v, FILTER_VALIDATE_INT) === false || ($unsigned && $v < 0)
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

	/**
	 * Validate that the input is a valid float.
	 *
	 * @param bool $unsigned if true, only positive floats are allowed
	 * @param string $message custom error message
	 * @return self
	 */
	public function float(bool $unsigned=true, string $message=""): self
	{
		if($this->escapeChecking())return $this;

		$error = false;
		$v = $this->values[$this->last_input];

		if(is_array($v) || filter_var($v, FILTER_VALIDATE_FLOAT) === false || ($unsigned && $v < 0)
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

	/**
	 * Validate that the input value is greater than or equal to a minimum.
	 * For arrays, validates the number of elements.
	 *
	 * @param float $min minimum value
	 * @param string $message custom error message
	 * @return self
	 */
	public function min(float $min, string $message=''): self
	{
		if($this->escapeChecking())return $this;

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

	/**
	 * Validate that the input value is less than or equal to a maximum.
	 * For arrays, validates the number of elements.
	 *
	 * @param float $max maximum value
	 * @param string $message custom error message
	 * @return self
	 */
	public function max(float $max, string $message=''): self
	{
		if($this->escapeChecking())return $this;

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

	/**
	 * Validate that the input value is between a minimum and maximum.
	 * For arrays, validates the number of elements.
	 *
	 * @param float $min minimum value
	 * @param float $max maximum value
	 * @param string $message custom error message
	 * @return self
	 */
	public function between(float $min, float $max, string $message=''): self
	{
		if($this->escapeChecking())return $this;

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

	/**
	 * Validate that the input has an exact character length (or array count).
	 *
	 * @param int $length expected length
	 * @param string $message custom error message
	 * @return self
	 */
	public function length(int $length, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$error = false;
		if((!is_array($v) && mb_strlen(trim($v)) != $length) || (is_array($v) && count($v) != $length))
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

	/**
	 * Validate that the input has a minimum character length (or array count).
	 *
	 * @param int $length minimum length
	 * @param string $message custom error message
	 * @return self
	 */
	public function minLength(int $length, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$error = false;
		if((!is_array($v) && mb_strlen(trim($v)) < $length) || (is_array($v) && count($v) < $length))
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

	/**
	 * Validate that the input has a maximum character length (or array count).
	 *
	 * @param int $length maximum length
	 * @param string $message custom error message
	 * @return self
	 */
	public function maxLength(int $length, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$error = false;
		if((!is_array($v) && mb_strlen(trim($v)) > $length) || (is_array($v) && count($v) > $length))
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

	/**
	 * Validate that the input equals a given value.
	 *
	 * @param mixed $value expected value
	 * @param string $message custom error message
	 * @return self
	 */
	public function equal($value, string $message=''): self
	{
		if($this->escapeChecking())return $this;

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

	/**
	 * Validate that the input is accepted (value is 'yes', 'YES' or 1).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function accepted(string $message=''): self
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

	/**
	 * Validate that the input is a boolean-like value.
	 * Accepts: true, false, 1, 0, '1', '0', 'true', 'false', 'yes', 'no'.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function boolean(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !in_array(strtolower((string)$v), ['true', 'false', '1', '0', 'yes', 'no'], true))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a boolean value" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate password strength with configurable requirements.
	 *
	 * @param int $min_length minimum length (default: 8)
	 * @param bool $require_upper require at least one uppercase letter
	 * @param bool $require_digit require at least one digit
	 * @param bool $require_special require at least one special character
	 * @param string $message custom error message (overrides all individual messages)
	 * @return self
	 */
	public function password(int $min_length=8, bool $require_upper=true, bool $require_digit=true, bool $require_special=true, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v))
		{
			$this->addError((empty($message)) ? "`[FIELD]` must be a valid password" : $message, []);
			return $this;
		}

		if(mb_strlen($v) < $min_length)
		{
			$this->addError((empty($message)) ? "`[FIELD]` must be at least `[LENGTH]` characters" : $message, ['LENGTH' => $min_length]);
		}

		if($require_upper && !preg_match('/[A-Z]/', $v))
		{
			$this->addError((empty($message)) ? "`[FIELD]` must contain at least one uppercase letter" : $message, []);
		}

		if($require_digit && !preg_match('/[0-9]/', $v))
		{
			$this->addError((empty($message)) ? "`[FIELD]` must contain at least one digit" : $message, []);
		}

		if($require_special && !preg_match('/[^a-zA-Z0-9]/', $v))
		{
			$this->addError((empty($message)) ? "`[FIELD]` must contain at least one special character" : $message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid URL.
	 *
	 * @param int|string $php_filters_flags optional PHP FILTER_FLAG_* constants
	 * @param string $message custom error message
	 * @return self
	 */
	public function url($php_filters_flags='', string $message=''): self
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

	/**
	 * Validate that the input contains only alphabetic characters.
	 *
	 * @param string $exceptions additional allowed characters
	 * @param bool $latin_chars_allowed allow accented latin characters
	 * @param bool $min_allowed allow lowercase letters
	 * @param bool $capital_allowed allow uppercase letters
	 * @param string $message custom error message
	 * @return self
	 */
	public function alpha(string $exceptions="", bool $latin_chars_allowed=true, bool $min_allowed=true, bool $capital_allowed=true, string $message=''): self
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
				for($i=0; $i < mb_strlen($exceptions); $i++)
					$v = str_replace(mb_substr($exceptions, $i, 1), 'x', $v);
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

	/**
	 * Validate that the input contains only alphabetic and numeric characters.
	 *
	 * @param string $exceptions additional allowed characters
	 * @param bool $latin_chars_allowed allow accented latin characters
	 * @param bool $min_allowed allow lowercase letters
	 * @param bool $capital_allowed allow uppercase letters
	 * @param string $message custom error message
	 * @return self
	 */
	public function alphaNumeric(string $exceptions="", bool $latin_chars_allowed=true, bool $min_allowed=true, bool $capital_allowed=true, string $message=''): self
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
				for($i=0; $i < mb_strlen($exceptions); $i++)
					$v = str_replace(mb_substr($exceptions, $i, 1), 'x', $v);
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

	/**
	 * Validate that the input is a valid date.
	 *
	 * @param string $format expected date format (default: Y-m-d)
	 * @param string $message custom error message
	 * @return self
	 */
	public function date(string $format='Y-m-d', string $message=''): self
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

			if($d === false || ($d_errors !== false && ($d_errors['warning_count'] > 0 || $d_errors['error_count'] > 0)))
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

	/**
	 * Validate that the input is a valid datetime.
	 *
	 * @param string $format expected datetime format (default: Y-m-d H:i:s)
	 * @param string $message custom error message
	 * @return self
	 */
	public function datetime(string $format='Y-m-d H:i:s', string $message=''): self
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

			if($d === false || ($d_errors !== false && ($d_errors['warning_count'] > 0 || $d_errors['error_count'] > 0)))
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

	/**
	 * Validate that the input date is before a given date.
	 *
	 * @param string $date the reference date to compare against
	 * @param string $format date format (default: Y-m-d)
	 * @param string $message custom error message
	 * @return self
	 */
	public function dateBefore(string $date, string $format='Y-m-d', string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$d_input = \Datetime::createFromFormat($format, $v);
		$d_ref = \Datetime::createFromFormat($format, $date);

		if($d_input === false || $d_ref === false || $d_input >= $d_ref)
		{
			$message = (empty($message)) ? "`[FIELD]` must be before `[DATE]`" : $message;
			$this->addError($message, ['DATE' => $date]);
		}

		return $this;
	}

	/**
	 * Validate that the input date is after a given date.
	 *
	 * @param string $date the reference date to compare against
	 * @param string $format date format (default: Y-m-d)
	 * @param string $message custom error message
	 * @return self
	 */
	public function dateAfter(string $date, string $format='Y-m-d', string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$d_input = \Datetime::createFromFormat($format, $v);
		$d_ref = \Datetime::createFromFormat($format, $date);

		if($d_input === false || $d_ref === false || $d_input <= $d_ref)
		{
			$message = (empty($message)) ? "`[FIELD]` must be after `[DATE]`" : $message;
			$this->addError($message, ['DATE' => $date]);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid time.
	 *
	 * @param string $format expected time format (default: H:i:s)
	 * @param string $message custom error message
	 * @return self
	 */
	public function time(string $format='H:i:s', string $message=''): self
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

			if($d === false || ($d_errors !== false && ($d_errors['warning_count'] > 0 || $d_errors['error_count'] > 0)))
				$error = true;
		}

		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid time in format `[FORMAT]`" : $message;
			$this->addError($message, ['FORMAT' => $format]);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid CSS color.
	 * Accepts: hex (#fff, #ff0000, #ff000080), rgb(), rgba(), hsl(), hsla() and named CSS colors.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function cssColor(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$error = true;

		if(!is_array($v))
		{
			$val = strtolower(trim((string)$v));

			// hex: #rgb, #rrggbb, #rrggbbaa
			if(preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $val))
			{
				$error = false;
			}
			// rgb() / rgba()
			elseif(preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $val))
			{
				$error = false;
			}
			// hsl() / hsla()
			elseif(preg_match('/^hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $val))
			{
				$error = false;
			}
			// named colors
			else
			{
				$named = [
					'aliceblue','antiquewhite','aqua','aquamarine','azure',
					'beige','bisque','black','blanchedalmond','blue','blueviolet','brown','burlywood',
					'cadetblue','chartreuse','chocolate','coral','cornflowerblue','cornsilk','crimson','cyan',
					'darkblue','darkcyan','darkgoldenrod','darkgray','darkgreen','darkgrey','darkkhaki','darkmagenta','darkolivegreen','darkorange','darkorchid','darkred','darksalmon','darkseagreen','darkslateblue','darkslategray','darkslategrey','darkturquoise','darkviolet','deeppink','deepskyblue','dimgray','dimgrey','dodgerblue',
					'firebrick','floralwhite','forestgreen','fuchsia',
					'gainsboro','ghostwhite','gold','goldenrod','gray','green','greenyellow','grey',
					'honeydew','hotpink',
					'indianred','indigo','ivory',
					'khaki',
					'lavender','lavenderblush','lawngreen','lemonchiffon','lightblue','lightcoral','lightcyan','lightgoldenrodyellow','lightgray','lightgreen','lightgrey','lightpink','lightsalmon','lightseagreen','lightskyblue','lightslategray','lightslategrey','lightsteelblue','lightyellow','lime','limegreen','linen',
					'magenta','maroon','mediumaquamarine','mediumblue','mediumorchid','mediumpurple','mediumseagreen','mediumslateblue','mediumspringgreen','mediumturquoise','mediumvioletred','midnightblue','mintcream','mistyrose','moccasin',
					'navajowhite','navy',
					'oldlace','olive','olivedrab','orange','orangered','orchid',
					'palegoldenrod','palegreen','paleturquoise','palevioletred','papayawhip','peachpuff','peru','pink','plum','powderblue','purple',
					'rebeccapurple','red','rosybrown','royalblue',
					'saddlebrown','salmon','sandybrown','seagreen','seashell','sienna','silver','skyblue','slateblue','slategray','slategrey','snow','springgreen','steelblue',
					'tan','teal','thistle','tomato','turquoise',
					'violet',
					'wheat','white','whitesmoke',
					'yellow','yellowgreen',
					'transparent',
				];

				if(in_array($val, $named, true))
					$error = false;
			}
		}

		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid CSS color" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid MAC address.
	 * Accepts colon (:) or hyphen (-) separators.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function macAddress(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !preg_match('/^([0-9A-Fa-f]{2}[:\-]){5}[0-9A-Fa-f]{2}$/', (string)$v))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid MAC address" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid UUID (RFC 4122, versions 1-5).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function uuid(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', (string)$v))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid UUID" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid BIC/SWIFT code.
	 * Format: 4 letters (bank) + 2 letters (country) + 2 alphanumeric (location) + 3 optional alphanumeric (branch).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function bic(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$bic = strtoupper(str_replace(' ', '', (string)$v));

		if(is_array($v) || !preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid BIC/SWIFT code" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid IBAN (International Bank Account Number).
	 * Checks format (2 letters + 2 digits + alphanumeric) and MOD-97 checksum (ISO 13616).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function iban(string $message=''): self
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
			$iban = strtoupper(str_replace(' ', '', (string)$v));

			// format: 2 letters + 2 digits + 11-30 alphanumeric
			if(!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban))
			{
				$error = true;
			}
			else
			{
				// mod-97 checksum (ISO 13616)
				$rearranged = substr($iban, 4) . substr($iban, 0, 4);
				$numeric = '';
				for($i = 0; $i < strlen($rearranged); $i++)
				{
					$char = $rearranged[$i];
					$numeric .= ctype_alpha($char) ? (ord($char) - 55) : $char;
				}

				// bcmod for large numbers
				$remainder = $numeric;
				while(strlen($remainder) > 2)
				{
					$block = substr($remainder, 0, 9);
					$remainder = ((int)$block % 97) . substr($remainder, strlen($block));
				}

				if((int)$remainder % 97 !== 1)
					$error = true;
			}
		}

		if($error)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid IBAN" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid ISO 4217 currency code.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function currency(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$currencies = [
			'AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN',
			'BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BRL','BSD','BTN','BWP','BYN','BZD',
			'CAD','CDF','CHF','CLP','CNY','COP','CRC','CUP','CVE','CZK',
			'DJF','DKK','DOP','DZD',
			'EGP','ERN','ETB','EUR',
			'FJD','FKP',
			'GBP','GEL','GHS','GIP','GMD','GNF','GTQ','GYD',
			'HKD','HNL','HRK','HTG','HUF',
			'IDR','ILS','INR','IQD','IRR','ISK',
			'JMD','JOD','JPY',
			'KES','KGS','KHR','KMF','KPW','KRW','KWD','KYD','KZT',
			'LAK','LBP','LKR','LRD','LSL','LYD',
			'MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRU','MUR','MVR','MWK','MXN','MYR','MZN',
			'NAD','NGN','NIO','NOK','NPR','NZD',
			'OMR',
			'PAB','PEN','PGK','PHP','PKR','PLN','PYG',
			'QAR',
			'RON','RSD','RUB','RWF',
			'SAR','SBD','SCR','SDG','SEK','SGD','SHP','SLE','SLL','SOS','SRD','SSP','STN','SVC','SYP','SZL',
			'THB','TJS','TMT','TND','TOP','TRY','TTD','TWD','TZS',
			'UAH','UGX','USD','UYU','UZS',
			'VES','VND','VUV',
			'WST',
			'XAF','XCD','XOF','XPF',
			'YER',
			'ZAR','ZMW','ZWL',
		];

		if(is_array($v) || !in_array(strtoupper((string)$v), $currencies, true))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid currency code" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid ISO 639-1 language code.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function language(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$languages = [
			'aa','ab','af','ak','am','an','ar','as','av','ay','az',
			'ba','be','bg','bh','bi','bm','bn','bo','br','bs',
			'ca','ce','ch','co','cr','cs','cu','cv','cy',
			'da','de','dv','dz',
			'ee','el','en','eo','es','et','eu',
			'fa','ff','fi','fj','fo','fr','fy',
			'ga','gd','gl','gn','gu','gv',
			'ha','he','hi','ho','hr','ht','hu','hy','hz',
			'ia','id','ie','ig','ii','ik','io','is','it','iu',
			'ja','jv',
			'ka','kg','ki','kj','kk','kl','km','kn','ko','kr','ks','ku','kv','kw','ky',
			'la','lb','lg','li','ln','lo','lt','lu','lv',
			'mg','mh','mi','mk','ml','mn','mr','ms','mt','my',
			'na','nb','nd','ne','ng','nl','nn','no','nr','nv','ny',
			'oc','oj','om','or','os',
			'pa','pi','pl','ps','pt',
			'qu',
			'rm','rn','ro','ru','rw',
			'sa','sc','sd','se','sg','si','sk','sl','sm','sn','so','sq','sr','ss','st','su','sv','sw',
			'ta','te','tg','th','ti','tk','tl','tn','to','tr','ts','tt','tw','ty',
			'ug','uk','ur','uz',
			've','vi','vo',
			'wa','wo',
			'xh',
			'yi','yo',
			'za','zh','zu',
		];

		if(is_array($v) || !in_array(strtolower((string)$v), $languages, true))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid language code" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid ISO 3166-1 alpha-2 country code.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function country(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		$countries = [
			'AD','AE','AF','AG','AI','AL','AM','AO','AQ','AR','AS','AT','AU','AW','AX','AZ',
			'BA','BB','BD','BE','BF','BG','BH','BI','BJ','BL','BM','BN','BO','BQ','BR','BS','BT','BV','BW','BY','BZ',
			'CA','CC','CD','CF','CG','CH','CI','CK','CL','CM','CN','CO','CR','CU','CV','CW','CX','CY','CZ',
			'DE','DJ','DK','DM','DO','DZ',
			'EC','EE','EG','EH','ER','ES','ET',
			'FI','FJ','FK','FM','FO','FR',
			'GA','GB','GD','GE','GF','GG','GH','GI','GL','GM','GN','GP','GQ','GR','GS','GT','GU','GW','GY',
			'HK','HM','HN','HR','HT','HU',
			'ID','IE','IL','IM','IN','IO','IQ','IR','IS','IT',
			'JE','JM','JO','JP',
			'KE','KG','KH','KI','KM','KN','KP','KR','KW','KY','KZ',
			'LA','LB','LC','LI','LK','LR','LS','LT','LU','LV','LY',
			'MA','MC','MD','ME','MF','MG','MH','MK','ML','MM','MN','MO','MP','MQ','MR','MS','MT','MU','MV','MW','MX','MY','MZ',
			'NA','NC','NE','NF','NG','NI','NL','NO','NP','NR','NU','NZ',
			'OM',
			'PA','PE','PF','PG','PH','PK','PL','PM','PN','PR','PS','PT','PW','PY',
			'QA',
			'RE','RO','RS','RU','RW',
			'SA','SB','SC','SD','SE','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SR','SS','ST','SV','SX','SY','SZ',
			'TC','TD','TF','TG','TH','TJ','TK','TL','TM','TN','TO','TR','TT','TV','TW','TZ',
			'UA','UG','UM','US','UY','UZ',
			'VA','VC','VE','VG','VI','VN','VU',
			'WF','WS',
			'YE','YT',
			'ZA','ZM','ZW',
		];

		if(is_array($v) || !in_array(strtoupper((string)$v), $countries, true))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid country code" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid timezone identifier.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function timezone(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !in_array($v, timezone_identifiers_list(), true))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid timezone" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input matches a regular expression.
	 *
	 * @param string $pattern regex pattern
	 * @param string $message error message (required)
	 * @return self
	 */
	public function regex(string $pattern, string $message): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !(preg_match($pattern, $v)))
		{
			$this->addError($this->_ts($message), []);
		}

		return $this;
	}

	/**
	 * Validate that the input does not match a regular expression.
	 *
	 * @param string $pattern regex pattern
	 * @param string $message error message (required)
	 * @return self
	 */
	public function notRegex(string $pattern, string $message): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || (preg_match($pattern, $v)))
		{
			$this->addError($this->_ts($message), []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid IP address.
	 *
	 * @param int|string $php_flags optional PHP FILTER_FLAG_* constants
	 * @param string $message custom error message
	 * @return self
	 */
	public function ip($php_flags='', string $message=''): self
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

	/**
	 * Validate that the input is a valid IPv4 address.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function ipV4(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid IPv4 address" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid IPv6 address.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function ipV6(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || !filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid IPv6 address" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid JSON string.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function json(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(is_array($v) || json_decode($v) === null)
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid JSON string" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid JSON array (e.g. [1, 2, 3]).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function jsonArray(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];
		$decoded = is_array($v) ? null : json_decode($v, true);

		if($decoded === null || !is_array($decoded) || $decoded !== array_values($decoded))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid JSON array" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input is a valid JSON object (e.g. {"key": "value"}).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function jsonObject(string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];
		$decoded = is_array($v) ? null : json_decode($v, true);

		if($decoded === null || !is_array($decoded) || $decoded === array_values($decoded))
		{
			$message = (empty($message)) ? "`[FIELD]` must be a valid JSON object" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the input equals another input's value.
	 *
	 * @param string $input_parent the other input field name to compare with
	 * @param string $message custom error message
	 * @return self
	 */
	public function sameAs(string $input_parent, string $message=''): self
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

	/**
	 * Validate that the input is different from another input's value.
	 *
	 * @param string $input_parent the other input field name to compare with
	 * @param string $message custom error message
	 * @return self
	 */
	public function different(string $input_parent, string $message=''): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(isset($this->values[$input_parent]) && $v == $this->values[$input_parent])
		{
			$message = (empty($message)) ? "`[FIELD]` must be different from `[FIELD_PARENT]`" : $message;
			$this->addError($message, ['FIELD_PARENT' => $this->getInputName($input_parent)]);
		}

		return $this;
	}

	/**
	 * Validate that the input is required only when another input has a specific value.
	 *
	 * @param string $input_parent the other input field name to check
	 * @param mixed $value value or array of values that triggers the requirement
	 * @param string $message custom error message
	 * @return self
	 */
	public function requiredIf(string $input_parent, $value, string $message=''): self
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

	/**
	 * Validate that the input matches a CSRF token.
	 * By default, reads the expected token from $_SESSION['csrf_token'].
	 * When validation fails and $regenerate is true, a new token is generated in $_SESSION.
	 *
	 * @param string|null $expected_token the expected CSRF token, or null to read from $_SESSION['csrf_token']
	 * @param string $message custom error message
	 * @param bool $regenerate regenerate the token on failure (default: true)
	 * @return self
	 */
	public function csrfToken(?string $expected_token=null, string $message='', bool $regenerate=true): self
	{
		if(!$this->values)return $this;

		if($expected_token === null)
			$expected_token = $_SESSION['csrf_token'] ?? '';

		$v = $this->values[$this->last_input] ?? '';

		if(empty($expected_token) || !hash_equals($expected_token, $v))
		{
			$message = (empty($message)) ? "Invalid or expired CSRF token" : $message;
			$this->addError($message, []);

			if($regenerate)
				$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}

		return $this;
	}

	/**
	 * Validate the input using a custom callback function.
	 * The callback receives the input value and must return true (valid) or false (invalid).
	 *
	 * @param callable $callback function($value): bool
	 * @param string $message error message (required)
	 * @return self
	 */
	public function custom(callable $callback, string $message): self
	{
		if($this->escapeChecking())return $this;

		$v = $this->values[$this->last_input];

		if(!$callback($v))
		{
			$this->addError($this->_ts($message), []);
		}

		return $this;
	}


	/**
	 * Validate that a file has been uploaded (required).
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileRequired(string $message=''): self
	{
		if(!$this->values)return $this;

		if(!isset($_FILES[$this->last_input]) || !isset($_FILES[$this->last_input]['size']) || !$_FILES[$this->last_input]['size'])
		{
			$message = (empty($message)) ? "`[FIELD]` file is required" : $message;
			$this->addError($message, []);
		}

		return $this;
	}

	/**
	 * Validate that the uploaded file has an allowed extension.
	 *
	 * @param array $extensions allowed extensions (e.g. ['jpg', 'png'])
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileExtension(array $extensions, string $message=''): self
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

	/**
	 * Validate that the uploaded file does not exceed a maximum size.
	 * Supports units: kb, mb, gb (or ko, mo, go).
	 *
	 * @param string $size maximum size with unit (e.g. '2mb', '500kb')
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileMaxSize(string $size, string $message=''): self
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

	/**
	 * Validate that the uploaded file is an image with allowed extensions and MIME types.
	 *
	 * @param array $extensions allowed image extensions
	 * @return self
	 */
	public function fileImage(array $extensions=['jpg', 'jpeg', 'gif', 'png', 'svg']): self
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

	/**
	 * Validate that the uploaded file has an allowed MIME type.
	 *
	 * @param array $mimes allowed MIME types (e.g. ['image/png', 'image/jpeg'])
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileMime(array $mimes=[], string $message=''): self
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['type']) || empty($_FILES[$this->last_input]['name']))return $this;
		$mimes = array_map('strtolower', $mimes);

		// $file_mime = $_FILES[$this->last_input]['type'];
		$file_mime = @mime_content_type($_FILES[$this->last_input]['tmp_name']);

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

	/**
	 * Validate that the file was properly uploaded via HTTP POST.
	 *
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileUploaded(string $message=''): self
	{
		if(!$this->values || empty($_FILES[$this->last_input]['size']))return $this;

		if(!@is_uploaded_file($_FILES[$this->last_input]['tmp_name']))
		{
			$message = (empty($message)) ? "`[FIELD]` is not an uploaded file" : $message;

			$params = [];
			$this->addError($message, $params);
		}

		return $this;
	}

	/**
	 * Validate the uploaded image width.
	 *
	 * @param int $width expected width in pixels
	 * @param bool $contraint if true, width must match exactly
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileImageWidth(int $width, bool $contraint=false, string $message=''): self
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['tmp_name']) || empty($_FILES[$this->last_input]['tmp_name'])  || !file_exists($_FILES[$this->last_input]['tmp_name']))return $this;

		list($w, $h) = @getimagesize($_FILES[$this->last_input]['tmp_name']);

		if($contraint && $width != $w)
		{
			$message = (empty($message)) ? "`[FIELD]` image width must be equal to `[SIZE]`" : $message;
			$params = [];
			$params['SIZE'] = $width;
			$this->addError($message, $params);
		}

		return $this;
	}

	/**
	 * Validate the uploaded image height.
	 *
	 * @param int $height expected height in pixels
	 * @param bool $contraint if true, height must match exactly
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileImageHeight(int $height, bool $contraint=false, string $message=''): self
	{
		if(!$this->values || !isset($_FILES[$this->last_input]['tmp_name']) || empty($_FILES[$this->last_input]['tmp_name'])  || !file_exists($_FILES[$this->last_input]['tmp_name']))return $this;

		list($w, $h) = @getimagesize($_FILES[$this->last_input]['tmp_name']);

		if($contraint && $height != $h)
		{
			$message = (empty($message)) ? "`[FIELD]` image height must be equal to `[SIZE]`" : $message;
			$params = [];
			$params['SIZE'] = $height;
			$this->addError($message, $params);
		}

		return $this;
	}

	/**
	 * Validate and decode a base64-encoded image, optionally creating a temporary file in $_FILES.
	 *
	 * @param string $ext expected image extension (e.g. 'png', 'jpg')
	 * @param bool $create_file if true, creates a temp file and populates $_FILES
	 * @param string $message custom error message
	 * @return self
	 */
	public function fileImageBase64(string $ext='png', bool $create_file=true, string $message=''): self
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

		return $this;
	}


}
