<?php

class JsonDateTime{

    public const JSON = 'Y-m-d\TH:i:s.v\Z';

    public static function createFromJson($dateString){
        $date = DateTime::createFromFormat(JsonDateTime::JSON, $dateString, new DateTimeZone("UTC"));
        if(!$date){
            return false;
        }
        $date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        return $date;
    }

    public static function jsonSerialize(?DateTime $dt): string{
        if(!isset($dt)) return null;

        $jsonDt = new DateTime();
        $jsonDt->setTimestamp($dt->getTimestamp());
        $jsonDt->setTimezone(new DateTimeZone("UTC"));
        return $jsonDt->format(JsonDateTime::JSON);
    }
}

?>