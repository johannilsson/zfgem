<?php

/**
 * @category   Gem
 * @package    Gem_Manipulator
 */
interface Gem_Manipulate_Interface
{

    /**
     * Perfoms manipulation
     *
     * @param string $from
     * @param string $to
     * @param string $options
     */
    public function manipulate($from, $to, $options);
}
