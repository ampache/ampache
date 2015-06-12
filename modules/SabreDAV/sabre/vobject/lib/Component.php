<?php

namespace Sabre\VObject;

/**
 * Component
 *
 * A component represents a group of properties, such as VCALENDAR, VEVENT, or
 * VCARD.
 *
 * @copyright Copyright (C) 2011-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Component extends Node {

    /**
     * Component name.
     *
     * This will contain a string such as VEVENT, VTODO, VCALENDAR, VCARD.
     *
     * @var string
     */
    public $name;

    /**
     * A list of properties and/or sub-components.
     *
     * @var array
     */
    public $children = array();

    /**
     * Creates a new component.
     *
     * You can specify the children either in key=>value syntax, in which case
     * properties will automatically be created, or you can just pass a list of
     * Component and Property object.
     *
     * By default, a set of sensible values will be added to the component. For
     * an iCalendar object, this may be something like CALSCALE:GREGORIAN. To
     * ensure that this does not happen, set $defaults to false.
     *
     * @param Document $root
     * @param string $name such as VCALENDAR, VEVENT.
     * @param array $children
     * @param bool $defaults
     * @return void
     */
    function __construct(Document $root, $name, array $children = array(), $defaults = true) {

        $this->name = strtoupper($name);
        $this->root = $root;

        if ($defaults) {
            // This is a terribly convoluted way to do this, but this ensures
            // that the order of properties as they are specified in both
            // defaults and the childrens list, are inserted in the object in a
            // natural way.
            $list = $this->getDefaults();
            $nodes = array();
            foreach($children as $key=>$value) {
                if ($value instanceof Node) {
                    if (isset($list[$value->name])) {
                        unset($list[$value->name]);
                    }
                    $nodes[] = $value;
                } else {
                    $list[$key] = $value;
                }
            }
            foreach($list as $key=>$value) {
                $this->add($key, $value);
            }
            foreach($nodes as $node) {
                $this->add($node);
            }
        } else {
            foreach($children as $k=>$child) {
                if ($child instanceof Node) {

                    // Component or Property
                    $this->add($child);
                } else {

                    // Property key=>value
                    $this->add($k, $child);
                }
            }
        }

    }

    /**
     * Adds a new property or component, and returns the new item.
     *
     * This method has 3 possible signatures:
     *
     * add(Component $comp) // Adds a new component
     * add(Property $prop)  // Adds a new property
     * add($name, $value, array $parameters = array()) // Adds a new property
     * add($name, array $children = array()) // Adds a new component
     * by name.
     *
     * @return Node
     */
    function add($a1, $a2 = null, $a3 = null) {

        if ($a1 instanceof Node) {
            if (!is_null($a2)) {
                throw new \InvalidArgumentException('The second argument must not be specified, when passing a VObject Node');
            }
            $a1->parent = $this;
            $this->children[] = $a1;

            return $a1;

        } elseif(is_string($a1)) {

            $item = $this->root->create($a1, $a2, $a3);
            $item->parent = $this;
            $this->children[] = $item;

            return $item;

        } else {

            throw new \InvalidArgumentException('The first argument must either be a \\Sabre\\VObject\\Node or a string');

        }

    }

    /**
     * This method removes a component or property from this component.
     *
     * You can either specify the item by name (like DTSTART), in which case
     * all properties/components with that name will be removed, or you can
     * pass an instance of a property or component, in which case only that
     * exact item will be removed.
     *
     * The removed item will be returned. In case there were more than 1 items
     * removed, only the last one will be returned.
     *
     * @param mixed $item
     * @return void
     */
    function remove($item) {

        if (is_string($item)) {
            $children = $this->select($item);
            foreach($children as $k=>$child) {
                unset($this->children[$k]);
            }
            return $child;
        } else {
            foreach($this->children as $k => $child) {
                if ($child===$item) {
                    unset($this->children[$k]);
                    return $child;
                }
            }

            throw new \InvalidArgumentException('The item you passed to remove() was not a child of this component');

        }

    }

    /**
     * Returns an iterable list of children
     *
     * @return array
     */
    function children() {

        return $this->children;

    }

    /**
     * This method only returns a list of sub-components. Properties are
     * ignored.
     *
     * @return array
     */
    function getComponents() {

        $result = array();
        foreach($this->children as $child) {
            if ($child instanceof Component) {
                $result[] = $child;
            }
        }

        return $result;

    }

    /**
     * Returns an array with elements that match the specified name.
     *
     * This function is also aware of MIME-Directory groups (as they appear in
     * vcards). This means that if a property is grouped as "HOME.EMAIL", it
     * will also be returned when searching for just "EMAIL". If you want to
     * search for a property in a specific group, you can select on the entire
     * string ("HOME.EMAIL"). If you want to search on a specific property that
     * has not been assigned a group, specify ".EMAIL".
     *
     * Keys are retained from the 'children' array, which may be confusing in
     * certain cases.
     *
     * @param string $name
     * @return array
     */
    function select($name) {

        $group = null;
        $name = strtoupper($name);
        if (strpos($name,'.')!==false) {
            list($group,$name) = explode('.', $name, 2);
        }

        $result = array();
        foreach($this->children as $key=>$child) {

            if (
                (
                    strtoupper($child->name) === $name
                    && (is_null($group) || ( $child instanceof Property && strtoupper($child->group) === $group))
                )
                ||
                (
                    $name === '' && $child instanceof Property && strtoupper($child->group) === $group
                )
            ) {

                $result[$key] = $child;

            }
        }

        reset($result);
        return $result;

    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    function serialize() {

        $str = "BEGIN:" . $this->name . "\r\n";

        /**
         * Gives a component a 'score' for sorting purposes.
         *
         * This is solely used by the childrenSort method.
         *
         * A higher score means the item will be lower in the list.
         * To avoid score collisions, each "score category" has a reasonable
         * space to accomodate elements. The $key is added to the $score to
         * preserve the original relative order of elements.
         *
         * @param int $key
         * @param array $array
         * @return int
         */
        $sortScore = function($key, $array) {

            if ($array[$key] instanceof Component) {

                // We want to encode VTIMEZONE first, this is a personal
                // preference.
                if ($array[$key]->name === 'VTIMEZONE') {
                    $score=300000000;
                    return $score+$key;
                } else {
                    $score=400000000;
                    return $score+$key;
                }
            } else {
                // Properties get encoded first
                // VCARD version 4.0 wants the VERSION property to appear first
                if ($array[$key] instanceof Property) {
                    if ($array[$key]->name === 'VERSION') {
                        $score=100000000;
                        return $score+$key;
                    } else {
                        // All other properties
                        $score=200000000;
                        return $score+$key;
                    }
                }
            }

        };

        $tmp = $this->children;
        uksort(
            $this->children,
            function($a, $b) use ($sortScore, $tmp) {

                $sA = $sortScore($a, $tmp);
                $sB = $sortScore($b, $tmp);

                return $sA - $sB;

            }
        );

        foreach($this->children as $child) $str.=$child->serialize();
        $str.= "END:" . $this->name . "\r\n";

        return $str;

    }

    /**
     * This method returns an array, with the representation as it should be
     * encoded in json. This is used to create jCard or jCal documents.
     *
     * @return array
     */
    function jsonSerialize() {

        $components = array();
        $properties = array();

        foreach($this->children as $child) {
            if ($child instanceof Component) {
                $components[] = $child->jsonSerialize();
            } else {
                $properties[] = $child->jsonSerialize();
            }
        }

        return array(
            strtolower($this->name),
            $properties,
            $components
        );

    }

    /**
     * This method should return a list of default property values.
     *
     * @return array
     */
    protected function getDefaults() {

        return array();

    }

    /* Magic property accessors {{{ */

    /**
     * Using 'get' you will either get a property or component.
     *
     * If there were no child-elements found with the specified name,
     * null is returned.
     *
     * To use this, this may look something like this:
     *
     * $event = $calendar->VEVENT;
     *
     * @param string $name
     * @return Property
     */
    function __get($name) {

        $matches = $this->select($name);
        if (count($matches)===0) {
            return null;
        } else {
            $firstMatch = current($matches);
            /** @var $firstMatch Property */
            $firstMatch->setIterator(new ElementList(array_values($matches)));
            return $firstMatch;
        }

    }

    /**
     * This method checks if a sub-element with the specified name exists.
     *
     * @param string $name
     * @return bool
     */
    function __isset($name) {

        $matches = $this->select($name);
        return count($matches)>0;

    }

    /**
     * Using the setter method you can add properties or subcomponents
     *
     * You can either pass a Component, Property
     * object, or a string to automatically create a Property.
     *
     * If the item already exists, it will be removed. If you want to add
     * a new item with the same name, always use the add() method.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    function __set($name, $value) {

        $matches = $this->select($name);
        $overWrite = count($matches)?key($matches):null;

        if ($value instanceof Component || $value instanceof Property) {
            $value->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $value;
            } else {
                $this->children[] = $value;
            }
        } else {
            $property = $this->root->create($name,$value);
            $property->parent = $this;
            if (!is_null($overWrite)) {
                $this->children[$overWrite] = $property;
            } else {
                $this->children[] = $property;
            }
        }
    }

    /**
     * Removes all properties and components within this component with the
     * specified name.
     *
     * @param string $name
     * @return void
     */
    function __unset($name) {

        $matches = $this->select($name);
        foreach($matches as $k=>$child) {

            unset($this->children[$k]);
            $child->parent = null;

        }

    }

    /* }}} */

    /**
     * This method is automatically called when the object is cloned.
     * Specifically, this will ensure all child elements are also cloned.
     *
     * @return void
     */
    function __clone() {

        foreach($this->children as $key=>$child) {
            $this->children[$key] = clone $child;
            $this->children[$key]->parent = $this;
        }

    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * It is also possible to specify defaults and severity levels for
     * violating the rule.
     *
     * See the VEVENT implementation for getValidationRules for a more complex
     * example.
     *
     * @var array
     */
    function getValidationRules() {

        return array();

    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   Node::REPAIR - May attempt to automatically repair the problem.
     *   Node::PROFILE_CARDDAV - Validate the vCard for CardDAV purposes.
     *   Node::PROFILE_CALDAV - Validate the iCalendar for CalDAV purposes.
     *
     * This method returns an array with detected problems.
     * Every element has the following properties:
     *
     *  * level - problem level.
     *  * message - A human-readable string describing the issue.
     *  * node - A reference to the problematic node.
     *
     * The level means:
     *   1 - The issue was repaired (only happens if REPAIR was turned on).
     *   2 - A warning.
     *   3 - An error.
     *
     * @param int $options
     * @return array
     */
    function validate($options = 0) {

        $rules = $this->getValidationRules();
        $defaults = $this->getDefaults();

        $propertyCounters = array();

        $messages = array();

        foreach($this->children as $child) {
            $name = strtoupper($child->name);
            if (!isset($propertyCounters[$name])) {
                $propertyCounters[$name] = 1;
            } else {
                $propertyCounters[$name]++;
            }
            $messages = array_merge($messages, $child->validate($options));
        }

        foreach($rules as $propName => $rule) {

            switch($rule) {
                case '0' :
                    if (isset($propertyCounters[$propName])) {
                        $messages[] = array(
                            'level' => 3,
                            'message' => $propName . ' MUST NOT appear in a ' . $this->name . ' component',
                            'node' => $this,
                        );
                    }
                    break;
                case '1' :
                    if (!isset($propertyCounters[$propName]) || $propertyCounters[$propName]!==1) {
                        $repaired = false;
                        if ($options & self::REPAIR && isset($defaults[$propName])) {
                            $this->add($propName, $defaults[$propName]);
                        }
                        $messages[] = array(
                            'level' => $repaired?1:3,
                            'message' => $propName . ' MUST appear exactly once in a ' . $this->name . ' component',
                            'node' => $this,
                        );
                    }
                    break;
                case '+' :
                    if (!isset($propertyCounters[$propName]) || $propertyCounters[$propName] < 1) {
                        $messages[] = array(
                            'level' => 3,
                            'message' => $propName . ' MUST appear at least once in a ' . $this->name . ' component',
                            'node' => $this,
                        );
                    }
                    break;
                case '*' :
                    break;
                case '?' :
                    if (isset($propertyCounters[$propName]) && $propertyCounters[$propName] > 1) {
                        $messages[] = array(
                            'level' => 3,
                            'message' => $propName . ' MUST NOT appear more than once in a ' . $this->name . ' component',
                            'node' => $this,
                        );
                    }
                    break;

            }

        }
        return $messages;

    }

}
