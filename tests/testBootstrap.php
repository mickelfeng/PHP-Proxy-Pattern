<?php
spl_autoload_register(function ($class) { $file = str_replace(array('_',"\\"), "/", ltrim($class, '\\')) . '.php';
  if (!@fopen($file, 'r', true)) { return;} require_once $file;}, true);
define ('LIB_PATH', dirname(__DIR__) . '/library');

set_include_path(get_include_path() . PATH_SEPARATOR . LIB_PATH);

class MockSubject
{
    const MESSAGE = "Hello from %s with a value arg of %d";
    
    public function mockCall($param)
    {
        return sprintf(self::MESSAGE, __CLASS__, $param);
    }
}

class Foo
{
    public function setSubjectObject() { }
}