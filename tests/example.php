<?php
spl_autoload_register(function($class){require_once str_replace(array('_','\\'),'/', $class) . '.php';});
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../library');

class ExampleSubject
{
    public function ImHeavy($seconds = 3)
    {
        sleep($seconds);
        return "Finally my result is here !";
    }
}



$p = new Proxy\Proxy;
$p->setSubjectObject($e = new ExampleSubject);
$p->setCacheObject(new Proxy\CacheAdapter\Mock());
echo str_repeat("-", 10);flush();
printf("\n%s\n", $p->ImHeavy());flush();

echo "Cached :";
echo $p->ImHeavy();
