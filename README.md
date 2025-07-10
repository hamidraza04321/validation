# 🛡️ PHP Validation Class

A lightweight and extensible PHP validation class that supports common input validation rules, including file/image validation, time formatting, and even database uniqueness checks using PDO.

---

## 📌 Features

* Validate fields using simple rules (e.g., `required|string|unique:users,email`)
* Support for:

  * `required`, `required_if`
  * `string`, `number`, `time`
  * `image`, `min`, `max`
  * `in`, `nullable`, `unique`
* Custom error message generation
* PDO-based database connection for `unique` validation
* Image file extension and size validation
* Easily extendable structure

---

## 📂 File Structure

```
📁 /your-project
 └── Validation.php
```

---

## 🚀 Getting Started

### 🔧 Usage

```php
require_once 'Validation.php';

$data = [
    'name' => 'John Doe',
    'age' => '30',
    'profile_picture' => $_FILES['profile_picture']
];

$rules = [
    'name' => 'required|string',
    'age' => 'required|number',
    'profile_picture' => 'nullable|image|min:10|max:500'
];

$validator = new Validation($data, $rules);

if ($errors = $validator->errors()) {
    print_r($errors);
} else {
    $validatedData = $validator->validated();
    // Process your validated data
}
```

---

## 📜 Available Validation Rules

| Rule                                    | Description                                                      |
| --------------------------------------- | ---------------------------------------------------------------- |
| `required`                              | Field must not be empty                                          |
| `required_if:otherField,value`          | Field is required only if another field has a specific value     |
| `nullable`                              | Field can be empty or null                                       |
| `string`                                | Must be alphabetic characters with spaces                        |
| `number`                                | Must be numeric                                                  |
| `time`                                  | Must be in 24-hour time format (HH\:mm)                          |
| `image`                                 | Must be a valid image file (`jpg`, `jpeg`, `png`, `gif`, `webp`) |
| `min:value`                             | Minimum file size in KB                                          |
| `max:value`                             | Maximum file size in KB                                          |
| `in:val1,val2,...`                      | Must be one of the listed values                                 |
| `unique:table,column[,except_id,value]` | Checks database for duplicate values                             |

---

## 💾 Database Connection

The `unique` rule internally uses a PDO connection. Update the `connection()` method in the class to match your database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'test');
```

---

## 📤 Error Handling

Errors are stored as an associative array with field names as keys:

```php
$errors = $validator->errors();

foreach ($errors as $field => $messages) {
    foreach ($messages as $message) {
        echo "$message<br>";
    }
}
```

## 🤝 Contributions

Pull requests, bug fixes, and enhancements are welcome. Feel free to fork this repository and submit improvements.

---

