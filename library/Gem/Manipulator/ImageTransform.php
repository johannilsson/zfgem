<?php

require_once 'Gem/Manipulator/Interface.php';

/**
 * @category   Gem
 * @package    Gem_Manipulator
 */
class Gem_Manipulator_ImageTransform implements Gem_Manipulator_Interface 
{
    /**
     * Perfoms manipulation
     * 
     * TODO: Allow options to decide with image lib to load and how to resize the
     * image.
     *
     * @param string $from
     * @param string $to
     * @param array  $options
     */
	public function manipulate($from, $to, array $options = array())
	{
	    $options = new ArrayObject($options);
	    if (false == $options->offsetExists('width'))
	    {
	        throw new Gem_Manipulator_Exception('Missing option for width');
	    }

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

        $response = $imageTransform->scaleByX($options->offsetGet('width'));
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->save($to);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
	}
}