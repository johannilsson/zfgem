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
     * @param unknown_type $from
     * @param unknown_type $to
     * @param unknown_type $options
     */
	public function manipulate($from, $to, $options)
	{
		// Needs to be required here, otherwise getting "Cannot access self:: when 
		// no class scope is active" from PEAR.
		require_once 'Image/Transform.php';

		$imageTransform = Image_Transform::factory('GD');
		$imageTransform->load($from);
		$imageTransform->scaleByX($options['width']);
		$imageTransform->save($to);
	}
}