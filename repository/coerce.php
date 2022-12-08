<?php
require_once __DIR__."/../types.php";

class CoerceHandler{

    const DateTimeDBFormat = 'Y-m-d H:i:s';

    static function fromDB(int $type, $value){
        if($value === null) return null;

        switch($type){
            case Types::STRING:
                return "$value";
            case Types::BOOL:
                return !!$value;
            case Types::INT:
                $intval = (int)$value;
                return $intval;
            case Types::DATETIME:
                return new DateTime($value);
            default:
                return $value;
        }
    }

    static function toDB(int $type, $value){
        if(!isset($value)) return "NULL";

        switch($type){
            case Types::STRING:
                return "$value";
            case Types::BOOL:
                return $value ? true : false;
            case Types::INT:
                return (int)$value;
            case Types::DATETIME:
                return $value->format(CoerceHandler::DateTimeDBFormat);
            default:
                return $value;
        }
    }

    static function toPDOValue(int $type, $value){
        if($value === null) return PDO::PARAM_NULL;
        
        switch($type){
            case Types::BOOL:
                return PDO::PARAM_BOOL;
            case Types::INT:
                return PDO::PARAM_INT;
            default:
                return PDO::PARAM_STR;
        }
    }
}

?>