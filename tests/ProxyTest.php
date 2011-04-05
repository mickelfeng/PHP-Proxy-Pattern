<?php
namespace Proxy;
use Proxy\CacheAdapter as Adapter;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    protected $_mockCache;
    protected $_mockSubject;
    protected $_proxy;
    
    public function setUp()
    {
        $this->_proxy       = new Proxy();
        $this->_mockCache   = new Adapter\Mock;
        $this->_mockSubject = new \MockSubject();
        $this->_proxy->setCacheObject($this->_mockCache);
        $this->_proxy->setSubjectObject($this->_mockSubject);
    }
    
    public function assertPreconditions()
    {
        $this->assertSame($this->_mockCache, $this->_proxy->getCacheObject());
        $this->assertEquals(Proxy::DEFAULT_HASH_FUNCTION, $this->_proxy->getHashFunction());
        $this->assertEquals(Proxy::DEFAULT_TIMEOUT, $this->_proxy->getCacheObject()->getCacheTime());
    }
    
    public function testHashFunction()
    {
        $this->_proxy->setHashFunction("sha1");
        $this->assertEquals("sha1", $this->_proxy->getHashFunction());
        $this->setExpectedException("InvalidArgumentException");
        $this->_proxy->setHashFunction("doesnexists");
    }
    
    public function testTimeoutIsGivenToCacheBackendByProxy()
    {
        $this->_proxy->setCacheObject($this->_mockCache, 20);
        $this->assertEquals(20, $this->_proxy->getCacheObject()->getCacheTime());
    }
    
    public function testProxyWithAMissingSubjectObjectThrowsException()
    {
        $this->setExpectedException("DomainException");
        $p = new Proxy;
        $p->foo();
    }
    
    public function testProxyWithAMissingCachingObjectThrowsException()
    {
        $this->setExpectedException("DomainException");
        $p = new Proxy;
        $p->setSubjectObject($this->_mockSubject);
        $p->foo();
    }
    
    public function testSubjectObjectNotAnObjectThrowsException()
    {
        $this->setExpectedException("InvalidArgumentException", "Object required");
        $this->_proxy->setSubjectObject("I'm a string");        
    }
    
    public function testBadMethodCallOnProxyThrowsException()
    {
        $this->setExpectedException('RuntimeException');
        $this->_proxy->mockCall(/*with no args*/);
    }
    
    public function testCallingANonExistantMethodOnProxyThrowsException()
    {
        $this->setExpectedException('BadMethodCallException');
        $this->_proxy->foobarbaz();
    }
    
    public function testProxyProxiesAndCaches()
    {
        $arg = "foobar";
        $this->_proxy->mockCall($arg);
        $hash = $this->_proxy->makeHash(array(get_class($this->_mockSubject), 'mockCall', array($arg)));
        $this->assertInternalType("string", $this->_mockCache->get($hash));
        $this->assertStringMatchesFormat(\MockSubject::MESSAGE, $this->_mockCache->get($hash));
    }
    
    public function testProxyIncrementsCacheHits()
    {
        $arg = "foobar";
        $this->_proxy->mockCall($arg);
        $this->assertEquals(0, $this->_proxy->getCacheHits(array(get_class($this->_mockSubject), 'mockCall'), array($arg)));
        
        $this->_proxy->mockCall($arg);
        $this->assertEquals(1, $this->_proxy->getCacheHits(array(get_class($this->_mockSubject), 'mockCall'), array($arg)));
        
        $this->_proxy->mockCall($arg."modified");
        $this->assertEquals(1, $this->_proxy->getCacheHits(array(get_class($this->_mockSubject), 'mockCall'), array($arg)));
    }
    
    public function testProxyLoadsDataFromCache()
    {
        $this->_proxy->setSubjectObject($puMockSubject = $this->getMock("MockSubject"));
        $puMockSubject->expects($this->once()/*once and only once*/)
                      ->method("mockCall")
                      ->will($this->returnValue("return"));
        
        $this->_proxy->mockCall(0);
        
        $hash = $this->_proxy->makeHash(array(get_class($puMockSubject), 'mockCall', array(0)));
        
        $this->assertTrue($this->_mockCache->has($hash));
        $this->_proxy->mockCall(0);
        $this->assertEquals(1, $this->_proxy->getCacheHits($hash));
    }
    
    public function testCollision()
    {
        $this->setExpectedException("LogicException", "not allowed");
        $this->_proxy->setSubjectObject(new \Foo);
    }
}