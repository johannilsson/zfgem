<?php

class Gem_Manipulate
{
    /**
     * Attached manipulators
     *
     * @var array
     */
    protected $_manipulators = array();

    /**
     * Adds a manipulator to the end of the chain
     *
     * @param  Gem_Manipulate_Interface $manipulator
     * @return Gem_Manipulate Provides a fluent interface
     */
    public function addManipulator(Gem_Manipulate_Interface $manipulator)
    {
        $this->_manipulators[] = $manipulator;
        return $this;
    }

    /**
     * Manipulates
     *
     * @return void
     */
    public function manipulate()
    {
        foreach ($this->_manipulators as $manipulator) {
            $manipulator->manipulate();
        }
        return $this;
    }
}