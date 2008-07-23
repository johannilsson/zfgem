<?php

/**
 * @category   Gem
 * @package    Gem_Manipulator
 */
interface Gem_Manipulator_Interface
{
    /**
     * Perfoms manipulation
     *
     * @param string $from 
     * @param string $to
     * @param array $options
     */
    public function manipulate($from, $to, array $options = array());
}
