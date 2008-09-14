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
        if (!isset($options['size'])) {
            throw new Gem_Manipulate_Exception('ImageTransform requires the \'size\' option to be set');
        }
        $matches = array();
        preg_match('/(c)?([0-9]+)x([0-9]+)/', $options['size'], $matches);

        if (empty($matches[2])) {
            throw new Gem_Manipulate_Exception('Invalid size pattern \'' . $options['size']  . '\'');
        }
        if (empty($matches[3])) {
            throw new Gem_Manipulate_Exception('Invalid size pattern \'' . $options['size'] . '\'');
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

        if (empty($matches[1])) {
            $this->_fit($imageTransform, $matches[2], $matches[3]);        
        } else {
            $this->_cropResize($imageTransform, $matches[2], $matches[3]);        
        }

        $response = $imageTransform->save($to);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
    }

    /**
     * Fit
     * 
     * @param $imageTransform Image_Transform
     * @param $width int
     * @param $height int
     */
    private function _fit($imageTransform, $width, $height)
    {
        $response = $imageTransform->fit($width, $height);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
    }

    /**
     * Crop and resize
     * 
     * @param $imageTransform Image_Transform
     * @param $width int
     * @param $height int
     */
    private function _cropResize($imageTransform, $width, $height)
    {
        $aspectRatio = $imageTransform->getImageWidth() / $imageTransform->getImageHeight();
        $newAspectRatio = $width / $height;

        if ($newAspectRatio > $aspectRatio) {
            $newWidth = round(($height * $aspectRatio));
            $newHeight = $height;
        } else {
            $newWidth = $width;
            $newHeight = round(($width * $aspectRatio));
        }

        $maxLength = $newWidth >= $newHeight ? $newWidth : $newHeight;

        $response = $imageTransform->scaleMaxLength($maxLength);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }

        $response = $imageTransform->crop($width, $height);
        if (PEAR::isError($response)) {
            throw new Gem_Manipulator_Exception($response->getMessage());
        }
    }
}
