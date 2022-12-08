<?php

require_once __DIR__.'/../indexable_data.php';
require_once 'json_date_time.php';

class Serialize{
    
    public static function toJson(IndexableData $template, object &$instance)
    {
        // Create a clone of the object in array notation
        $jsonObject = (array) $instance;

        foreach($template->fullDefintion as $param){
            if(!isset($jsonObject[$param->propertyName])){
                // If the value is null, we don't need to process it
                continue;
            }
            switch($param->type){
                case Types::DATETIME:
                    $jsonObject[$param->propertyName] = JsonDateTime::jsonSerialize($jsonObject[$param->propertyName]);
                    break;
                default:
                    // We can trust JSON serialization with primative data types
                    break;
            }
        }

        return json_encode($jsonObject);
    }

    private static function validateDatatype(DataCollector &$param, $jsonValue){
        switch($param->type){
            case Types::DATETIME:
                if(!is_string($jsonValue) || !($jsonValue = JsonDateTime::createFromJson($jsonValue))){
                    throw new Exception("Field '$param->propertyName' must be a date string formatted YYYY-MM-DDTHH:mm:ss.zzzZ");
                }
                break;
            case Types::BOOL:
                if(!is_bool($jsonValue)){
                    throw new Exception("Field '$param->propertyName' must be a boolean");
                }
                break;
            case Types::INT:
                if(!is_int($jsonValue)){
                    throw new Exception("Field '$param->propertyName' must be an integer");
                }
                break;
            case Types::STRING:
                if(!is_string($jsonValue)){
                    throw new Exception("Field '$param->propertyName' must be an string");
                }
                break;
            default:
                throw new Exception("Unknown datatype!");
                continue;
        }
        return $jsonValue;
    }

    public static function fromJson(IndexableData &$template, string &$jsonString, bool $requireIndex=false){
        $jsonObject = json_decode($jsonString, true);

        $classObj = $template->represents->newInstance();

        if($requireIndex){
            $index = $template->index;
            $jsonValue = $jsonObject[$index->propertyName];
            
            if(!isset($jsonValue)){
                throw new Exception("Field '$index->propertyName' is required");
            }
            
            $jsonValue = static::validateDatatype($index, $jsonValue);
            $template->setProperty($classObj, $index->propertyName, $jsonValue);
        }

        foreach($template->parameters as $param){
            $jsonValue = $jsonObject[$param->propertyName];

            if(!isset($jsonValue)){
                if($param->required){
                    throw new Exception("Field '$param->propertyName' is required");
                }
                continue;
            }

            $jsonValue = static::validateDatatype($param, $jsonValue);
            $template->setProperty($classObj, $param->propertyName, $jsonValue);
        }

        return $classObj;
    }
}


?>