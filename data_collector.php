<?php 
require_once "types.php";

class DataCollector{
    
    public string $dbName;
    public string $propertyName;
    public bool $required;
    public int $length;
    public int $type;

    private function __construct($type, $propertyName, $dbName="", $required=false, $length=0){
        $this->propertyName = $propertyName;
        $this->dbName = empty($dbName) ? $propertyName : $dbName;
        $this->type = $type;
        $this->length = $length;
        $this->required = $required;
    }

    public static function STRING($propertyName, $dbName="", $required=false, $length=0){
        return new DataCollector(Types::STRING, $propertyName, $dbName, $required, $length);
    }

    public static function INT($propertyName, $dbName="", $required=false, $length=0){
        return new DataCollector(Types::INT, $propertyName, $dbName, $required, $length);
    }

    public static function BOOL($propertyName, $dbName="", $required=false, $length=0){
        return new DataCollector(Types::BOOL, $propertyName, $dbName, $required, $length);
    }

    public static function DATETIME($propertyName, $dbName="", $required=false, $length=0){
        return new DataCollector(Types::DATETIME, $propertyName, $dbName, $required, $length);
    }
}


?>