<?php
require_once "data_collector.php";

class IndexableData{
    public DataCollector $index;
    public array $parameters;

    public array $fullDefintion;

    public ReflectionClass $represents;

    public function __construct(string $represents, DataCollector $index, DataCollector ...$parameters){
        $this->represents = new ReflectionClass($represents);
        $this->index = $index;
        $this->parameters = $parameters;
        $this->fullDefintion = array_merge([$index], $parameters);
    }

    public function getKey(object &$instance){
        return $this->getProperty($instance, $this->index->propertyName);
    }

    public function getProperty(object &$instance, string $propertyName){
        return $this->represents->getProperty($propertyName)->getValue($instance);
    }

    public function setProperty(object &$instance, string $propertyName, $value){
        return $this->represents->getProperty($propertyName)->setValue($instance, $value);
    }
}

?>