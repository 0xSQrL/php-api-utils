# php-api-utils

## Description
Utilities for creating a RESTful MVC back-end in PHP

Design a datatype once and easily convert between JSON, PHP Objects, and Database queries

Roughly based on experince working with Hibernate for Springboot Java

## Install
* Clone repository as sub-repository into your existing PHP project
* Done

## Usage
Create your data type
```php
<?php
// data/user.php
require_once __DIR__."/../php-api-utils/indexable_data.php";

class User{
  // Define your data class
  public int $userId;
  public string $username;
  public string $email;
  
  // Create a static indexable representation to help with type conversion and DB interactions
  private static IndexableData $indexRepresentation;
  public static function asIndexable(){
      if(!isset(self::$indexRepresentation)){
          self::$indexRepresentation = new IndexableData(
              static::class,
              // Property Name, Database Name, Required
              DataCollector::INT("userId", "id"),
              DataCollector::STRING("username", "", true),
              DataCollector::STRING("email")
          );
      }
      return self::$indexRepresentation;
  }
}
?>
```

Create your repository
```php
<?php
// data/user_repository.php
require_once __DIR__."/../php-api-utils//repository/repository.php";
require_once __DIR__."/user.php";

class UserRepository extends Repository{
  public function __construct(PDO $db)
  {
      parent::__construct($db, "users", User::asIndexable());
  }
  
  // Define custom DB actions using direct queries
  function getUsersCount() : int{
      return (int)$this->database->query
          ("SELECT COUNT(id) FROM $this->table")
          ->fetchColumn();
   }
   
   // Define queries using built-in query builder
   function getUserEmail(string $username) : ?string{

        $stmt = $this->select([
            DataCollector::STRING("email")
        ], 
        "username=:username",
        [
            QueryParam::STRING(":username", $username)
        ]);
        
        $user = $stmt->next();
        if(!isset($user)){
          return null;
        }
    
        return $user->email;
    }

}
?>
```

Create your endpoint
```php
// api/user.php
require_once __DIR__.'/../data/user_repository.php';
require_once __DIR__.'/../php-api-utils/serialize/serialize.php';

// Connect to your database using PDO
$dsn = "mysql:host=127.0.0.1;dbname=mydatabase;charset=UTF8";

$db = new PDO($dsn, "user", "password");

// Initialize the typed repository
$userRepo = new UserRepository($db);

// Set up the JSON exchange
$jsonString = file_get_contents('php://input');
header('Content-Type: application/json');
try{
    switch($_SERVER["REQUEST_METHOD"]){
        case "PUT":
            // Handle updates which require the index
            $user = Serialize::fromJson(User::asIndexable(), $jsonString, true);
            break;
        case "POST":
            // Handle insertions which don't require an index
            http_response_code(201);
            $user = Serialize::fromJson(User::asIndexable(), $jsonString, false);
            break;
    }

    // Attempt to save the result to the database
    if(!$newsRepo->save($news)){
        throw new Exception("Unable to write user to database");
    }
    
    // Return the created/updated object to the user
    echo Serialize::toJson(User::asIndexable(), $user);

}catch(Exception $e){
    // Handle errors in a user (developer) friendly way
    http_response_code(400);
    echo json_encode([
        "success"=>false,
        "message"=>$e->getMessage()
    ]);
    exit(0);
}

```
