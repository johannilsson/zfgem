<?php

require_once 'Gem/Manipulate/Interface.php';

/**
 * @category   Gem
 * @package    Gem_Manipulate
 */
class Gem_Manipulate_ImageTransform implements Gem_Manipulate_Interface 
{
    /**
     * Perfoms manipulation
     * 
     */
	public function manipulate($from, $to, $options)
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

        $response = $imageTransform->load($from);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->scaleByX($options['width']);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->save($to);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
	}
}