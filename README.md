# php-password-hashing - with automatic bcrypt cost calculation
Encapsulation of PHP's password hashing functions, with automatic bcrypt cost calculation to meet required minimum hashing time.

## Features
- Encapsulates PHP>=5.5 `password_*` functions (hash, verify, needs_rehash)
- Impose minimum hashing time (500ms by default)
- Impose minimum bcrypt cost (14 by default)
- Automatic and on-the-fly bcrypt cost calculation based on running CPU
- Calculation is efficient (not trying all costs until hashing time is met)

## What's the purpose of this?
Password hashing is supposed to be slow, in order to render brute force attacks harder. The hashing time is determined by the number of repetitions the algorithm is run on the input - the more repetitions, the slower it is to hash and verify.

But how to determine how many repetitions to use? In bcrypt, the number of repetitions is determined by the "cost" value. Each consecutive cost value doubles the number repetitions from the previous one.

So why not just use a predefined cost value? Because CPUs (and GPUs) are getting better all the time, and therefore are able to hash (crack) faster. Use this class to automatically adjust the cost value based on the running CPU, to meet the required minimum hashing time..

Notice that PHP's default cost is 10, which is too weak for current CPUs.

## Installation
Download `Password.class.php` to you project's directory and include it in your code.

## Configuration
Adjust the `MIN_HASH_TIME` and `MIN_COST` constants to your needs.

Ideally, the hashing time should be around 100-250ms. I used a harsher value of 500-999ms (that's 0.5 to 1 second).

Setting `MIN_COST` makes sure that the calculated cost will never be less than the defined value, even if it means longer hashing time.

## Hashing Example
```php
<?php

  require "Password.class.php";
  
  $hash = Password::Hash ('go_vegan');
  
?>
```

## Verifying Hash Example
```php
<?php

  require "Password.class.php";

  $password = 'go_vegan';
  $hash = '$2y$10$EVlxCqOSxw6EvvinZ49Feu6lOFyuAnmHj712IN81Ln5ayCnqeOniy';

  // Verify stored hash against plain-text password
  if (Password::Verify ($password, $hash))
  {
      // Check if higher cost is recommended
      if (Password::NeedsRehash ($hash))
      {
          // If so, create a new hash, and replace the old one
          $new_hash = Password::Hash ($password);
      }
  
      // Log user in
  }
  
?>
```
