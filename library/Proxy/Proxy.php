<?php
/**
* PHP-Proxy-Pattern
*
* Copyright (c) 2010, Julien Pauli <jpauli@php.net>.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions
* are met:
*
* * Redistributions of source code must retain the above copyright
* notice, this list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright
* notice, this list of conditions and the following disclaimer in
* the documentation and/or other materials provided with the
* distribution.
*
* * Neither the name of Julien Pauli nor the names of his
* contributors may be used to endorse or promote products derived
* from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
* FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
* COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
* ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://julien-pauli.developpez.com
*/

namespace Proxy;

/**
* This is the proxy object implementing a Proxy design pattern.
* It takes a subject and a cache object as params.
* A method call on the proxy will proxy it to the subject and put the
* result in the cache object for next calls to be prevented on the subject.
* TTL is implemented, as well as cache hit count.
* 
* Aggregate as 1,n : Only one cache object and one subject object
* at the same time
*
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://julien-pauli.developpez.com
* @version Release: @package_version@
*/
class Proxy
{
    /**
     * Default timeout for cache entries
     *
     * @var int
     */
    const DEFAULT_TIMEOUT = 120;

    /**
     * Default hash callback to compute the hash
     * for an item
     *
     * @var string
     */
    const DEFAULT_HASH_FUNCTION = 'md5';
    
    /**
     * Object to cache method results from
     *
     * @var object
     */
    protected $_subjectObject;

    /**
     * Hash function used to compute a hash
     * from a method call on the subject object
     * Should be a valid PHP callback
     * 
     * @var string
     */
    protected $_hashFunction = self::DEFAULT_HASH_FUNCTION;
    
    /**
     * CacheAdapter to use
     *
     * @var CacheAdapter\Cacheable
     */
    protected $_cacheObject;

    /**
     * Number of cache hits for a specific hash
     *
     * @var array
     */
    protected $_cacheHits = array();
    
    /**
     * Proxy methods to compare to Subject methods
     * Proxy methods are cached into this property
     */
    protected $_thisMethods = array();

    /**
     * Sets a subject to cache methods from
     *
     * @param object $o
     * @throws \InvalidArgumentException
     * @return Proxy
     */
    public function setSubjectObject($o)
    {
        if (!is_object($o)) {
            throw new \InvalidArgumentException("Object required");
        }
        $this->_subjectObject = $o;
        $this->_checkForConsistency();
        return $this;
    }
    
    /**
     * Checks that the subject doesn't have the
     * same methods as the proxy. Proxy uses __call(), so...
     * 
     * @throws \LogicException
     * @return Proxy
     */
    protected function _checkForConsistency()
    {
        if (!$this->_thisMethods) {
            $reflection = new \ReflectionObject($this);
            $this->_thisMethods = array_filter(
                       $reflection->getMethods(),
                       function ($val){return $val->isPublic() && $val->getName() !== '__call';});
            $this->_thisMethods = array_map(
                       function ($val) {return $val->getName();},
                       $this->_thisMethods);
        }
        if ($comonMethods = array_intersect($this->_thisMethods, get_class_methods($this->_subjectObject))) {
            throw new \LogicException(sprintf("Methods %s are not allowed in the subject", implode(' ', $comonMethods)));
        }
        return $this;
    }

    /**
     * Generic proxy method
     * The hash is based on array(subjectclass, methodcalled, array(params))
     *  to avoid collisions
     *
     * @param string $meth
     * @param array $args
     * @return mixed
     * @throws DomainException
     * @throws RuntimeException
     * @throws BadMethodCallException
     */
    public function __call($meth, $args)
    {
        if (!$this->_cacheObject || !$this->_subjectObject) {
            throw new \DomainException("Cache object or subject object not set");
        }
        
        $hash = $this->makeHash(array(get_class($this->_subjectObject), $meth, $args));
        if ($this->_cacheObject->has($hash)) {
            $this->_setCacheHit($hash);
            return $this->_cacheObject->get($hash);
        }
        if (method_exists($this->_subjectObject, $meth)) {
            $before = error_get_last();
            $return = @call_user_func_array(array($this->_subjectObject, $meth), $args);
            $after  = error_get_last();
            if ($after && $after != $before) {
                throw new \RuntimeException($after['message']);
            }
            $this->_cacheObject->set($hash, $return);
            $this->_cacheHits[$hash] = 0;
            return $return;
        }
        throw new \BadMethodCallException("Method $meth doesnt exists");
    }

    /**
     * Increments cache hits for this hash
     *
     * @param string $hash
     * @return int
     * @throws InvalidArgumentException if the hash doesn't exist
     */
    protected function _setCacheHit($hash)
    {
        if (array_key_exists($hash, $this->_cacheHits)) {
            return ++$this->_cacheHits[$hash];
        }
        throw new \InvalidArgumentException("Cache key $hash does not exist");
    }
    
    /**
     * Setter for Cacheable object
     *
     * @param CacheAdapter\Cacheable $cache
     * @param int $timeout
     * @return Proxy
     */
    public function setCacheObject(CacheAdapter\Cacheable $cache, $timeout = self::DEFAULT_TIMEOUT)
    {
        $cache->setCacheTime($timeout);
        $this->_cacheObject = $cache;
        return $this;
    }

    /**
     * Retrieves the cache object used
     *
     * @return CacheAdapter\Cacheable
     */
    public function getCacheObject()
    {
        return $this->_cacheObject;
    }    

    /**
     * Computes a hash with the hash function registered
     *
     * @param array $params params to hash
     * @return string
     */
    public function makeHash(array $params)
    {
        return call_user_func($this->_hashFunction, serialize($params));
    }
    
    /**
     * Sets a hash callback to be used later
     * for computing the hash
     * 
     * @param string $hashFunction callback
     * @return Proxy
     * @throws InvalidArgumentException for invalid callbacks
     */
    public function setHashFunction($hashFunction)
    {
        if (is_callable($hashFunction)) {
            $this->_hashFunction = $hashFunction;
            return $this;
        }
        throw new \InvalidArgumentException("Hash function is not a valid callback");
    }
    
    /**
     * Gets the hash function used
     * 
     * @return string
     */
    public function getHashFunction()
    {
        return $this->_hashFunction;
    }

    /**
     * Gets the number of cache hits for a specific
     * entry. Entry can be set as a hash value or as
     * an 'unhashed' value aka: array($obj, 'method', array($args))
     * 
     * @param string|array $hashOrCall
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getCacheHits($hashOrCall, array $params = null)
    {
        if (is_array($hashOrCall) && is_callable($hashOrCall) && $params !== null) {
            $hashOrCall = array_merge($hashOrCall, array($params));
            return $this->_cacheHits[$this->makeHash($hashOrCall)];
        } elseif (is_string($hashOrCall)) {
            if (array_key_exists($hashOrCall, $this->_cacheHits)) {
                return $this->_cacheHits[$hashOrCall];
            }
            throw new \InvalidArgumentException("Unknown hash");
        }
        throw new \InvalidArgumentException("Callback or string hash expected");
    }
}