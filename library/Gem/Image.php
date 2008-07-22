<?php

require_once 'Gem/File.php';

class Gem_Image extends Gem_File
{
    private $_styles;

    public function __construct($realPath, $originalFilename, array $styles)
    {
        parent::__construct($realPath, $originalFilename);
        $this->_styles = $styles;
    }

    public function filename($style = '')
    {
        if ('' == $style)
        {
            return parent::filename();
        }
        return $style . '-' . $this->filename();
    }

    /**
     * The url
     *
     * Strips public and everything before from the real path, hopes that public
     * is the name of the public directory and nothing is named like that before.
     *
     * @param string $style
     * @return string
     */
    public function url($style = '')
    {
        $altImage = new SplFileInfo($this->fileInfo()->getPath() . '/'. $this->filename($style));

        $path = $this->realPath();
        if ($altImage->isFile())
        {
            $path = $altImage->getPathname();
        }

        return substr($path, stripos($path, 'public') + 6);
    }

    /**
     * Apply manipulations specified by this set styles
     *
     * @return self
     */
    public function applyManipulations()
    {
        $manipulator = new BinderClip_Manipulator_Image();
        foreach ($this->_styles as $styleName => $styleOptions)
        {
            $manipulator->manipulate($this->realPath(), $this->path() . '/'. $this->filename($styleName), $styleOptions);
        }
        return $this;
    }

}