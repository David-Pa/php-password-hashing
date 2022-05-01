<?php

class Password
{
  protected const MIN_HASH_TIME  = 0.5; // seconds
  protected const MIN_COST       = 14;
  
  static protected $cost = 5; // Start with a low cost for fast priliminary calculation (4 is too inaccurate) 

  /**
   * Hash password and adjust cost as needed
   *
   * @param string $password
   *
   * @throws Exception
   */
  static public function Hash ($password)
  {
    while (true)
    {
      // Measure min cost time
      $start_time = microtime (true);
      $hash = password_hash ($password, PASSWORD_BCRYPT, ["cost" => self::$cost]);
      $time_took = microtime (true) - $start_time;
      
      // Hashing time was sufficient
      if ($time_took >= self::MIN_HASH_TIME)
        return $hash;
      
      // Update cost
      self::UpdateCost ($time_took);
    }
  }

  /**
   * Verifies that password matches hash.
   * Use the opportunity to adjust cost.
   *
   * @param string $password
   * @param string $hash
   *
   * @throws Exception
   * @return bool
   */
  static public function Verify ($password, $hash)
  {
    $start_time = microtime (true);
    $status = password_verify ($password, $hash);
    $time_took = microtime (true) - $start_time;
    
    // Update cost
    self::UpdateCost ($time_took, $hash);
    
    return $status;
  }

  /**
   * Check if password needs rehashing
   *
   * IMPORTANT: should be run after at least one call to self::Hash() or self::Verify()
   * to adjust the cost, otherwise it might return false negative.
   *
   * @param string $hash
   *
   * @throws Exception
   * @return bool
   */
  static public function NeedsRehash ($hash)
  {
    $status = password_needs_rehash ($hash, PASSWORD_BCRYPT, ["cost" => self::$cost]);
    
    // Never rehash to lower cost
    if ($status && self::$cost <= self::GetCostFromHash ($hash))
      return false;
    
    return $status;
  }

  /**
   * Adjust cost as needed to meet self::MIN_HASH_TIME
   *
   * @param float $time_took
   * @param string? $hash
   *
   * @throws Exception
   * @return void
   */
  static protected function UpdateCost ($time_took, $hash = null)
  {
    // Calculate additional cost
    $additional_cost = (int)ceil (log (self::MIN_HASH_TIME / $time_took * 1.1) / log (2));
    
    // Use current cost from hash, if provided
    $current_cost = isset ($hash) ? self::GetCostFromHash ($hash) : self::$cost;
    
    // Set new cost
    self::$cost = max ($current_cost + $additional_cost, self::MIN_COST);
  }

  /**
   * Retrieve cost from existing hash
   *
   * @param string $hash e.g. "$2y$12$wY1/VHIVFvpDAy5zoULyeu.5S/rQToxXyTN8i3gmyL5gT7HqSVlAW"
   *
   * @throws Exception
   * @return int $cost
   */
  static protected function GetCostFromHash ($hash)
  {
    if (!preg_match ('~^\$2[abxy]\$(\d{1,2}\$)~', $hash, $matches))
      throw new Exception ('Unsupported algorithm: ' . $hash);
    
    return (int)$matches [1];
  }
}
