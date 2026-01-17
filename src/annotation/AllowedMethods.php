<?php
#[\Attribute(\Attribute::TARGET_METHOD)] // post,get,put,delete...
class AllowedMethods {
    public array $methods;

    public function __construct(array $methods) {
        $this->methods = $methods;
    }
}