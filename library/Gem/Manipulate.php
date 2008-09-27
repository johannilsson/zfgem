<?php
/**
 * Gem - File Uploading for Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Gem
 * @package    Gem_Manipulator
 * @copyright  Copyright (c) 2008 Johan Nilsson. (http://www.markupartist.com)
 * @license    New BSD License
 */
 
/**
 * Main endpoint for file manipulation
 */
class Gem_Manipulate
{
    /**
     * Manipulator
     *
     * @var string
     */
    protected $_manipulator;

    /**
     * Constructor
     *
     * @param string $manipulator
     */
    public function __construct($manipulator)
    {
        $this->_manipulator = $manipulator;
    }

    /**
     * Manipulates
     *
     * @return Gem_Manipulator
     */
    public function manipulate($from, $to, $options)
    {
        $manipulator = $this->getManipulatorInstance($this->_manipulator);
        $manipulator->manipulate($from, $to, $options);

        return $this;
    }

    /**
     * Returns an manipulator instance based on its name.
     *
     * @param string $manipulator
     * @return Gem_Manipulator_Instance
     */
    static public function getManipulatorInstance($manipulator)
    {
        $args = array();

        if (is_array($manipulator)) {
            $args = $manipulator;
            $manipulator = array_shift($args);
        }

        // TODO: Move
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Gem_Manipulate_', 'Gem/Manipulate/');
        $className = $loader->load($manipulator);

        $class = new ReflectionClass($className);

        if (!$class->implementsInterface('Gem_Manipulate_Interface')) {
            require_once 'Gem/Manipulate/Exception.php';
            throw new Gem_Manipulate_Exception('Manipulator must implement interface "Gem_Manipulate_Interface".');
        }

        if ($class->hasMethod('__construct')) {
            $object = $class->newInstanceArgs($args);
        } else {
            $object = $class->newInstance();
        }

        return $object;
    }
}
