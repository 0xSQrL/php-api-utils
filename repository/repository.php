<?php
require_once 'coerce.php';
require_once __DIR__.'/../indexable_data.php';

abstract class Repository{

    public PDO $database;
    public string $table;
    public IndexableData $datatype;
    public DataCollector $key;

    function __construct(PDO $database, string $table, IndexableData $datatype)
    {
        $this->database = $database;
        $this->table = $table;
        $this->datatype = $datatype;
    }

    protected function select(array $dataTypes, string $where = "", array $whereParams = []){
        $query = ["SELECT "];

        $csv = [];
        foreach($dataTypes as $dataType){
            array_push($csv, $dataType->dbName);
        }
        array_push($query, join(", ", $csv));

        array_push($query, " FROM $this->table");
        if(!empty($where)){
            array_push($query, " WHERE $where;");
        }
        $queryExec = $this->database->prepare(join("", $query));
        if(!empty($where)){
            foreach($whereParams as $param){
                $queryExec->bindParam($param->key, $param->value, $param->pdoParamType, $param->length);
            }
        }
        $queryExec->execute();

        return new Accumulator($queryExec, $dataTypes, $this->datatype->represents);
    }

    protected function insert(&$object, array $dataTypes) : bool{
        $query = ["INSERT INTO $this->table ("];
        
        $csv = [];
        foreach($dataTypes as $dataType){
            array_push($csv, $dataType->dbName);
        }
        array_push($query, join(", ", $csv));

        array_push($query, ") VALUES (");
        
        $csv = [];
        foreach($dataTypes as $k => $dataType){
            array_push($csv, ":$dataType->propertyName");
        }
        array_push($query, join(", ", $csv));

        array_push($query, ");");
            
        $this->database->beginTransaction();
        try{
            $queryExec = $this->database->prepare(join("", $query));
            foreach($dataTypes as $dataType){
                $value = $this->datatype->getProperty($object, $dataType->propertyName);
                $pdo = CoerceHandler::toPDOValue($dataType->type, $value);
                $value = CoerceHandler::toDB($dataType->type, $value);

                $queryExec->bindValue(":$dataType->propertyName", $value, $pdo);
            }
            $result = $queryExec->execute();

            if($result){
                // If the insert is a success, store the generated key in the object
                $id = $this->database->lastInsertId();
                $id = CoerceHandler::fromDB($this->datatype->index->type, $id);
                $this->datatype->setProperty($object, $this->datatype->index->propertyName, $id);
                $this->database->commit();
            }else{
                $this->database->rollBack();
            }

            return $result;
        }catch(Exception $e){
            $this->database->rollBack();
            return false;
        }
    }

    protected function update($object, array $dataTypes, string $where = "", array $whereParams = []){
        $query = ["UPDATE $this->table SET "];
        
        $csv = [];
        foreach($dataTypes as $dataType){
            array_push($csv, "$dataType->dbName=:$dataType->propertyName"."u");
        }
        array_push($query, join(", ", $csv));
        
        if(!empty($where)){
            array_push($query, " WHERE $where;");
        }
        
        $queryExec = $this->database->prepare(join("", $query));
        if(!empty($where)){
            foreach($whereParams as $param){
                $queryExec->bindParam($param->key, $param->value, $param->pdoParamType, $param->length);
            }
        }
        foreach($dataTypes as $dataType){
            $value = $this->datatype->getProperty($object, $dataType->propertyName);
            $pdo = CoerceHandler::toPDOValue($dataType->type, $value);
            $value = CoerceHandler::toDB($dataType->type, $value);
            $queryExec->bindValue(":$dataType->propertyName"."u", $value, $pdo);
        }
        $result = $queryExec->execute();
        if(!$result){
            throw new Exception("Error in query: ".$queryExec->errorInfo()[2]);
        }
        if($queryExec->rowCount() === 0){
            throw new Exception("No rows affected");
        }
        return true;
    }

    private static function dataCollectorToQueryParam(DataCollector &$dataCollector, $value) : QueryParam{
        
        $pdo = CoerceHandler::toPDOValue($dataCollector->type, $value);
        $value = CoerceHandler::toDB($dataCollector->type, $value);
        return new QueryParam($pdo, ":$dataCollector->propertyName", $value, $dataCollector->length);
    }

    public function save(&$object) : bool{
        $key = $this->datatype->getKey($object);
        $index = $this->datatype->index;
        if(isset($key)){
            // If the key is set, object already exists in DB
            // Use key to find it
            $where = $index->dbName.'=:'.$index->propertyName;
            return $this->update($object, $this->datatype->parameters, $where, [self::dataCollectorToQueryParam($index, $key)]);
        }else{
            return $this->insert($object, $this->datatype->fullDefintion);
        }
    }

    public function loadByKey($key){
        $index = $this->datatype->index;
        $where = $index->dbName.'=:'.$index->propertyName;
        $result = $this->select($this->datatype->fullDefintion, $where, [self::dataCollectorToQueryParam($index, $key)]);
        return $result->next();
    }
}

class Accumulator{
    private PDOStatement $queryResult;
    private array $dataTypes;
    private ReflectionClass $toClass;

    public function __construct(PDOStatement $queryResult, array $dataTypes, ReflectionClass $toClass)
    {
        $this->queryResult = $queryResult;
        $this->dataTypes = $dataTypes;
        $this->toClass = $toClass;
    }

    function next(){
        if(!($row = $this->queryResult->fetch(PDO::FETCH_ASSOC))) return null;

        $classObj = $this->toClass->newInstance();
        foreach($this->dataTypes as $dataType){
            $value = CoerceHandler::fromDB($dataType->type, $row[$dataType->dbName]);
            $this->toClass->getProperty($dataType->propertyName)->setValue($classObj, $value);
        }
        return $classObj;
    }

    function collect(){
        $result = [];

        while($classObj = $this->next()){
            array_push($result, $classObj);
        }
        
        return $result;
    }
}

class QueryParam{
    public string $key;
    public $value;
    public int $length;
    public int $pdoParamType;

    
    public function __construct($pdoParamType, $key, $value, $length=0){
        $this->key = $key;
        $this->value = $value;
        $this->length = $length;
        $this->pdoParamType = $pdoParamType;
    }

    public static function STRING($key, $value, $length=0){
        return new QueryParam(PDO::PARAM_STR, $key, $value, $length);
    }

    public static function INT($key, $value, $length=0){
        return new QueryParam(PDO::PARAM_INT, $key, $value, $length);
    }

    public static function BOOL($key, $value, $length=0){
        return new QueryParam(PDO::PARAM_BOOL, $key, $value, $length);
    }

    public static function DATETIME($key, DateTime $value, $length=0){
        $value = $value->format(CoerceHandler::DateTimeDBFormat);
        return new QueryParam(PDO::PARAM_STR, $key, $value, $length);
    }
}


?>