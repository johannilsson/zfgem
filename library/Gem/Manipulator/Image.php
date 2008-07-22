<?php

class Gem_Manipulator_Image
{
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