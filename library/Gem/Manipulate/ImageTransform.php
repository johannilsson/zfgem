<?php

require_once 'Gem/Manipulate/Interface.php';

/**
 * @category   Gem
 * @package    Gem_Manipulate
 */
class Gem_Manipulate_ImageTransform implements Gem_Manipulate_Interface 
{
    /**
     * Path to the image that should be manipulated 
     *
     * @var string
     */
    protected $_from;

    /**
     * Path to save the manipulated image
     *
     * @var string
     */
    protected $_to;

    protected $_width;
    
    /**
     * Constructs a new manipulator.
     * 
     * @todo Need to add options to be more flexible, decide image lib max width
     * height etc. kiss for now...
     *
     * @param string $from
     * @param string $to
     * @param int $width
     */
    public function __construct($from, $to, $width)
    {
        $this->_from  = $from;
        $this->_to    = $to;
        $this->_width = $width;
    }

    /**
     * Perfoms manipulation
     * 
     */
	public function manipulate()
	{
		/*
		 * Needs to be required here, otherwise getting "Cannot access self:: when 
		 * no class scope is active" from PEAR, probably only in autoload context.
		 */
		require_once 'Image/Transform.php';

        /*
         * Here comes the ugly pear integration...
         */
        $imageTransform = Image_Transform::factory('GD');
        if (PEAR::isError($imageTransform)) {
            throw new Gem_Manipulator_Exception($imageTransform->getMessage());
        }

        $response = $imageTransform->load($this->_from);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->scaleByX($this->_width);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->save($this->_to);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
	}
}