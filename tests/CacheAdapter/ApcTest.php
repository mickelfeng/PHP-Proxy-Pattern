<?php
namespace Proxy\CacheAdapter;
use Proxy\Proxy;

class ApcTest extends \PHPUnit_Framework_TestCase
{
    protected $_proxy;
    protected $_apc;
    protected $_subject;

    public function setup()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped("ext/APC should be loaded");
        }
        if (!ini_get('apc.enabled') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped("ext/APC is loaded but not enabled, check for
            apc.enabled and apc.enable_cli in php.ini");
        }
        $this->_apc     = new Apc();
        $this->_proxy   = new Proxy();
        $this->_subject = new \MockSubject();
        $this->_proxy->setSubjectObject($this->_subject);
        $this->_proxy->setCacheObject($this->_apc);
    }

    public function testApi()
    {
        $this->_apc->set('foo', 'bar');
        $this->assertTrue($this->_apc->has('foo'));
        $this->assertEquals('bar', $this->_apc->get('foo'));
    }
    
    public function testCacheTime()
    {
        $this->_apc->setCacheTime(1200);
        $this->assertEquals(1200, $this->_apc->getCacheTime());
    }
    
    public function testCacheWithRealProxy()
    {
        $this->_proxy->mockCall(42);
        $hash = $this->_proxy->makeHash(array(get_class($this->_subject), 'mockCall', array(42)));
        $this->assertRegExp("/42/", $this->_apc->get($hash));
    }
}