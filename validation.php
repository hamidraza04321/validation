<?php 

class Validation {

	/**
     * @var array $input - Data to validate.
     */
	private $data;

	/**
     * @var array $rules - rules to validate.
     */
	private $rules;

	/**
     * @var string $input.
     */
	private $input;

	/**
     * @var $value.
     */
	private $value;

	/**
     * @var array $errors - Store Input errors.
     */
	private $errors;

	/**
	 * @var array $allowedImageExtensions
	 */
	private $allowedImageExtensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];

	/**
     * @var array $messages - Store Input error messages.
     */
	private $messages = [
		'required' => 'The {field} is required',
		'string' => 'The {field} must be a string',
		'number' => 'The {field} must be a number',
		'unique' => 'The {field} has already been taken',
		'time' => 'The {field} must be a valid time format',
		'image' => 'The {field} must be a valid image file (jpg, jpeg, png, gif, webp)',
		'min' => 'The {field} must be at least {value} KB in size',
		'max' => 'The {field} must not exceed {value} KB in size',
		'in' => 'The {field} must be one of the following: {value}',
	];

	/**
	 * @var PDO
	 */
	private $pdo;

	/**
     * Validation - Create new instance of Validation class.
     * 
     * @param array $data - Data to validate.
     * @return object Validator
     */
	function __construct($data, $rules) {
		$this->data = $data;
		$this->rules = $rules;
		$this->validate();
	}

	/**
	 * Build PDO Connection
	 */
	private function connection()
	{
		define('DB_HOST', '127.0.0.1');
		define('DB_USER', 'root');
		define('DB_PASS', '');
		define('DB_NAME', 'test');

        $this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . "," . DB_USER, DB_PASS);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Validation
	 * 
	 * @return void
	 */
	private function validate()
	{
		foreach ($this->rules as $input => $value) {
			$this->input = $input;
			$this->value = (isset($this->data[$input])) ? $this->data[$input] : null;
			$this->input(explode('|', $value));
		}
	}

	/**
	 * Validate single input
	 * 
	 * @param $rules 	array
	 * @return 			void
	 */
	private function input($rules)
	{
		$nullable = in_array('nullable', $rules);

		// If value is empty and field is nullable, skip all validation
		if ($nullable && (!$this->exists() || $this->value === null || $this->value === '')) {
			return;
		}

		foreach ($rules as $rule) {

			// Skip for null
			if ($rule === 'nullable') continue;

			if (strpos($rule, ':') !== false) {
				list($ruleName, $param) = explode(':', $rule, 2);
				$this->$ruleName($param);
			} else {
				$this->$rule();
			}

		}
	}

	/**
	 * Required Validation
	 * 
	 * @return void
	 */
	private function required()
	{
		// If input does not exists in data or empty
		if (!$this->exists() || $this->value == '') {
			$this->addError('required');
		}
	}

	/**
	 * Required If Validation
	 * 
	 * @param string $condition
	 * @return void
	 */
	private function required_if($condition)
	{
	    list($otherField, $expectedValue) = explode(',', $condition);

	    // If the condition is met and current value is empty
	    if (isset($this->data[$otherField]) && $this->data[$otherField] == $expectedValue && empty($this->value)) {
	        $this->addError('required');
	    }
	}

	/**
	 * In Validation
	 * 
	 * @param string $values - Comma separated list of allowed values
	 * @return void
	 */
	private function in($values)
	{
	    $allowed = explode(',', $values);

	    if ($this->exists() && !in_array($this->value, $allowed)) {
	        $this->addError('in', ['value' => implode(', ', $allowed)]);
	    }
	}

	/**
	 * String Validation
	 * 
	 * @return void
	 */
	private function string()
	{
		// Only alphabets and spaces
		if ($this->exists() && !preg_match("/^[a-zA-Z ]+$/", $this->value) && $this->value != '') {
			$this->addError('string');
		}
	}

	/**
	 * Numeric Validation
	 * 
	 * @return void
	 */
	private function number()
	{
		// If is not a number
		if ($this->exists() && !is_numeric($this->value)) {
			$this->addError('number');
		}
	}

	/**
	 * Time Validation
	 * 
	 * @return void
	 */
	private function time()
	{
		// If is not a time format
		if ($this->exists() && !preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $this->value)) {
			$this->addError('time');
		}
	}

	/**
	 * Image Validation
	 * 
	 * @return void
	 */
	private function image()
	{
		if ($this->exists() && is_array($this->value)) {
			// Skip if no file was uploaded
	        if (
	            empty($this->value['name']) &&
	            empty($this->value['type']) &&
	            empty($this->value['tmp_name']) &&
	            $this->value['error'] == 4 &&
	            $this->value['size'] == 0
	        ) {
	            return; // Skip validation, treat as "nullable"
	        }

			// Get the file extension
			$extension = strtolower(pathinfo($this->value['name'], PATHINFO_EXTENSION));

			// Check if it's a valid image extension
			if (!in_array($extension, $this->allowedImageExtensions)) {
				$this->addError('image');
			}
		}
	}

	/**
	 * Minimum File Size
	 * 
	 * @param $kb 	string
	 * @return 		void
	 */
	private function min($kb)
	{
		if ($this->exists() && is_array($this->value) && isset($this->value['size'])) {
			$sizeKb = $this->value['size'] / 1024;
			if ($sizeKb < (float) $kb) {
				$this->addError('min', ['value' => $kb]);
			}
		}
	}

	/**
	 * Maximum File Size
	 * 
	 * @param $kb 	string
	 * @return 		void
	 */
	private function max($kb)
	{
		if ($this->exists() && is_array($this->value) && isset($this->value['size'])) {
			$sizeKb = $this->value['size'] / 1024;
			if ($sizeKb > (float) $kb) {
				$this->addError('max', ['value' => $kb]);
			}
		}
	}

	/**
	 * Unique Column Name
	 */
	private function unique($param)
	{
		$this->connection();

	    list($table, $column, $exceptKey, $exceptVal) = array_pad(explode(',', $param), 4, null);

	    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
	    $params = ['value' => $this->value];

	    if ($exceptKey === 'except_id' && $exceptVal) {
	        $sql .= " AND record_id != :except";
	        $params['except'] = $exceptVal;
	    }

	    $stmt = $this->pdo->prepare($sql);
	    $stmt->execute($params);
	    $count = $stmt->fetchColumn();

	    if ($count > 0) {
	        $this->addError('unique');
	    }
	}

	/**
	 * Check input exists in data
	 * 
	 * @return bool
	 */
	private function exists()
	{
		return isset($this->data[$this->input]);
	}

	/**
	 * Add Error
	 * 
	 * @param $type 	string
	 * @return 			void
	 */
	private function addError($type, $replacements = [])
	{
		$message = $this->messages[$type];
		$message = str_replace('{field}', $this->input, $message);
		
		foreach ($replacements as $key => $val) {
			$message = str_replace("{" . $key . "}", $val, $message);
		}
		
		$this->errors[$this->input][] = $message;
	}

	/**
	 * Fetch error messages
	 * 
	 * @return array
	 */
	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Validated input
	 * 
	 * @return array
	 */
	public function validated()
	{
		$inputs = [];

		foreach ($this->data as $input => $value) {
		    if (isset($this->rules[$input])) {
		        $inputs[$input] = $value;
		    }
		}

		return $inputs;
	}
}
