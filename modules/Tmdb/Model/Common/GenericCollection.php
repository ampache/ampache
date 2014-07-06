<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Some code is borrowed from Guzzle, and thus I've credited the author, however most of it is in a modified form.
 *
 * @package Tmdb
 * @author Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb\Model\Common;

use Tmdb\Model\Filter\AdultFilter;
use Tmdb\Model\Filter\CountryFilter;
use Tmdb\Model\Filter\LanguageFilter;

/**
 * Class GenericCollection
 * @package Tmdb\Model\Common
 */
class GenericCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array Data associated with the object. */
    protected $data = array();

    /**
     * @param array $data Associative array of data to set
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Removes all key value pairs
     *
     * @return GenericCollection
     */
    public function clear()
    {
        $this->data = array();

        return $this;
    }

    /**
     * Get all or a subset of matching key value pairs
     *
     * @param array $keys Pass an array of keys to retrieve only a subset of key value pairs
     *
     * @return array Returns an array of all matching key value pairs
     */
    public function getAll(array $keys = null)
    {
        return $keys ? array_intersect_key($this->data, array_flip($keys)) : $this->data;
    }

    /**
     * Get a specific key value.
     *
     * @param string $key Key to retrieve.
     *
     * @return mixed|null Value of the key or NULL
     */
    public function get($key)
    {
        if (is_object($key)) {
            $key = spl_object_hash($key);
        }

        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Set a key value pair
     *
     * @param string $key   Key to set
     * @param mixed  $value Value to set
     *
     * @return GenericCollection Returns a reference to the object
     */
    public function set($key, $value)
    {
        if ($key === null && is_object($value)) {
            $key = spl_object_hash($value);
        }

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Add a value to a key.
     *
     * @param string $key   Key to add
     * @param mixed  $value Value to add to the key
     *
     * @return GenericCollection Returns a reference to the object.
     */
    public function add($key, $value)
    {
        if ($key === null && is_object($value)) {
            $key = spl_object_hash($value);
        }

        if (!array_key_exists($key, $this->data) && null !== $key) {
            $this->data[$key] = $value;
        } elseif (!array_key_exists($key, $this->data) && null == $key) {
            $this->data[] = $value;
        } elseif (is_array($this->data[$key])) {
            $this->data[$key][] = $value;
        } else {
            $this->data[$key] = array($this->data[$key], $value);
        }

        return $this;
    }

    /**
     * Remove a specific key value pair
     *
     * @param string $key A key to remove or an object in the same state
     *
     * @return GenericCollection
     */
    public function remove($key)
    {
        if (is_object($key)) {
            $key = spl_object_hash($key);
        }

        unset($this->data[$key]);

        return $this;
    }

    /**
     * Get all keys in the collection
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->data);
    }

    /**
     * Returns whether or not the specified key is present.
     *
     * @param string $key The key for which to check the existence.
     *
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Case insensitive search the keys in the collection
     *
     * @param string $key Key to search for
     *
     * @return bool|string Returns false if not found, otherwise returns the key
     */
    public function keySearch($key)
    {
        foreach (array_keys($this->data) as $k) {
            if (!strcasecmp($k, $key)) {
                return $k;
            }
        }

        return false;
    }

    /**
     * Checks if any keys contains a certain value
     *
     * @param string $value Value to search for
     *
     * @return mixed Returns the key if the value was found FALSE if the value was not found.
     */
    public function hasValue($value)
    {
        return array_search($value, $this->data);
    }

    /**
     * Replace the data of the object with the value of an array
     *
     * @param array $data Associative array of data
     *
     * @return GenericCollection Returns a reference to the object
     */
    public function replace(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Add and merge in a Collection or array of key value pair data.
     *
     * @param GenericCollection|array $data Associative array of key value pair data
     *
     * @return GenericCollection Returns a reference to the object.
     */
    public function merge($data)
    {
        foreach ($data as $key => $value) {
            $this->add($key, $value);
        }

        return $this;
    }

    /**
     * Returns a Collection containing all the elements of the collection after applying the callback function to each
     * one. The Closure should accept three parameters: (string) $key, (string) $value, (array) $context and return a
     * modified value
     *
     * @param \Closure $closure Closure to apply
     * @param array    $context Context to pass to the closure
     * @param bool     $static  Set to TRUE to use the same class as the return rather than returning a Collection
     *
     * @return GenericCollection
     */
    public function map(\Closure $closure, array $context = array(), $static = true)
    {
        $collection = $static ? new static() : new self();
        foreach ($this as $key => $value) {
            $collection->add($key, $closure($key, $value, $context));
        }

        return $collection;
    }

    /**
     * Iterates over each key value pair in the collection passing them to the Closure. If the  Closure function returns
     * true, the current value from input is returned into the result Collection.  The Closure must accept three
     * parameters: (string) $key, (string) $value and return Boolean TRUE or FALSE for each value.
     *
     * @param \Closure $closure Closure evaluation function
     * @param bool     $static  Set to TRUE to use the same class as the return rather than returning a Collection
     *
     * @return GenericCollection
     */
    public function filter(\Closure $closure, $static = true)
    {
        $collection = ($static) ? new static() : new self();
        foreach ($this->data as $key => $value) {
            if ($closure($key, $value)) {
                $collection->add($key, $value);
            }
        }

        return $collection;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Filter by id
     *
     * @param  integer           $id
     * @return GenericCollection
     */
    public function filterId($id)
    {
        $result = $this->filter(
            function ($key, $value) use ($id) {
                if ($value->getId() == $id) {
                    return true;
                }
            }
        );

        if ($result && 1 === count($result)) {
            return array_shift($this->data);
        }

        return null;
    }

    /**
     * Filter by language ISO 639-1 code.
     *
     * @param  string            $language
     * @return GenericCollection
     */
    public function filterLanguage($language = 'en')
    {
        return $this->filter(
            function ($key, $value) use ($language) {
                if ($value instanceof LanguageFilter && $value->getIso6391() == $language) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by country ISO 3166-1 code.
     *
     * @param  string            $country
     * @return GenericCollection
     */
    public function filterCountry($country = 'US')
    {
        return $this->filter(
            function ($key, $value) use ($country) {
                if ($value instanceof CountryFilter && $value->getIso31661() == $country) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by adult content
     *
     * @param  boolean           $adult
     * @return GenericCollection
     */
    public function filterAdult($adult = false)
    {
        return $this->filter(
            function ($key, $value) use ($adult) {
                if ($value instanceof AdultFilter && $value->getAdult() == $adult) {
                    return true;
                }
            }
        );
    }
}
