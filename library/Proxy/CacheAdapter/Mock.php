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

namespace Proxy\CacheAdapter;

/**
* Mock Cache
*
* This class can be used as a PHP-based cache backend
*
* @author Julien Pauli <jpauli@php.net>
* @copyright 2010 Julien Pauli <jpauli@php.net>
* @license http://www.opensource.org/licenses/bsd-license.php BSD License
* @link http://julien-pauli.developpez.com
* @version Release: @package_version@
*/
class Mock implements Cacheable
{
    /**
     * Time to keep items in cache
     * Actually not implemented
     *
     * @var int
     */
    protected $_cacheTime;
    
    /**
     * Items stored in cache
     *
     * @var array
     */
    protected $_items = array();
    
    /**
     * Retrieves an item from cache
     * 
     * @param string $item item hash
     * @return mixed The result
     */
    public function get($item)
    {
        return $this->_items[$item]['value'];
    }
    
    /**
     * Stores an item into the cache
     * 
     * @param string $item The item hash
     * @param mixed $value The value to store
     * @return bool
     */
    public function set($item, $value)
    {
        $this->_items[$item]['value'] = $value;
        $this->_items[$item]['ttl']   = $this->_cacheTime;
        return true;
    }
    
    /**
     * Checks if an item is in the cache
     * 
     * @param string $item the item hash
     * @return bool
     */
    public function has($item)
    {
        return array_key_exists($item, $this->_items);
    }
    
    /**
     * Set the cache time to keep items in the cache
     * Actually not implemented on the mock adapter
     * 
     * @param int $time
     * @return Mock
     */
    public function setCacheTime($time)
    {
        $this->_cacheTime = (int)$time;
        return $this;
    }
    
    /**
     * Gets the cache time for items
     * 
     * @return int
     */
    public function getCacheTime()
    {
        return $this->_cacheTime;
    }
}