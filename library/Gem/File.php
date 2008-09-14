<?php

/**
 * File represention.
 *
 */
class Gem_File
{
    protected $_realPath;
    protected $_originalFilename;
    protected $_manipulator = '';
    protected $_styles = array();

    /**
     * Constructor
     *
     * @param string $realPath
     * @param string $originalFilename
     */
    public function __construct($realPath, $originalFilename, $manipulator = '')
    {
        $this->_realPath         = $realPath;
        $this->_originalFilename = $originalFilename;
        $this->_manipulator      = $manipulator;
    }

    /**
     * Adds several styles at once.
     *
     * @param array $styles
     * @return this
     */
    public function addStyles(array $styles)
    {
        foreach ($styles as $name => $options) {
            $this->addStyle($name, $options);
        }
        return $this;
    }

    /**
     * Adds a new style to this file.
     *
     * @param string $name
     * @param array $options
     * @return this
     */
    public function addStyle($name, array $options)
    {
        $realPath = $this->fileInfo()->getPath() . '/'. $this->filename($name);
        $instance = new Gem_File($realPath, $this->originalFilename());

        $this->_styles[] = array(
            'name'     => $name,
            'instance' => $instance,
            'options'  => $options,
					);
        return $this;
    }

    /**
     * Get a specific style of this file.
     *
     * Allows for calls like $file->large->url(); to performs actions on the
     * style "large".
     *
     * @param string $name
     * @return Gem_File
     */
    public function __get($name)
    {
        foreach ($this->_styles as $style) {
            if ($name == $style['name']) {
                return $style['instance'];
            }
        }
        throw new Exception($name . ' is not my style');
    }

    /**
     * Return the current filename or constructs a filename based on the spupplied
     * style.
     *
     * @return string
     */
    public function filename($style = '')
    {
        if ('' == $style) {
            return basename($this->_realPath);
        }
        return $style . '-' . $this->filename();
    }

    /**
     * Creates and returns a fileInfo object based on current realPath.
     *
     * @return SplFileInfo
     */
    public function fileInfo()
    {
        return new SplFileInfo($this->_realPath);
    }

    /**
     * The orginal filename
     *
     * @return string
     */
    public function originalFilename()
    {
        return $this->_originalFilename;
    }

    public function isUploadedFile()
    {
        if (is_uploaded_file($this->_realPath)) {
            return true;
        }
        return false;
    }

    public function moveTo($destination)
    {
        // TODO: Error handling and moving if not an upload.
        $copy = clone $this;
        $this->copyTo($destination)->touch();
        $copy->delete();

        return $this;
    }

    public function copyTo($destination)
    {
        $newDirectory = dirname($destination);
        if (is_dir($newDirectory) || @mkdir($newDirectory, 0755, true) !== false ) {
            if (is_writable($newDirectory)) {
                copy($this->_realPath, $destination);
                $this->_realPath = $destination;

                return $this;
            }
        }
        throw new RuntimeException('Could not write to ' . $newDirectory);
    }

    public function delete()
    {
        if (unlink($this->_realPath) === false) {
            throw new RuntimeException('Could not delete ' . $this->_realPath);
        }
    }

    public function touch($time = 'now')
    {
        touch($this->_realPath, strtotime($time));
        return $this;
    }

    public function exists()
    {
        if ($this->fileInfo()->isFile()) {
            return true;
        }
        return false;
    }

    public function path()
    {
        return dirname($this->_realPath);
    }

    public function realPath()
    {
        return $this->_realPath;
    }

    /**
     * Return the size of this file
     *
     * @return int
     */
    public function size()
    {
        return filesize($this->_realPath);
    }

    /**
     * Return the mime content type of this file
     *
     * @return string
     */
    public function mimeContentType()
    {
        return mime_content_type($this->_realPath);
    }

    /**
     * The url
     *
     * Strips public and everything before from the real path, hopes that public
     * is the name of the public directory and nothing is named like that before.
     *
     * @todo needs to be handle by some config instead.
     * @return string
     */
    public function url()
    {
        $path = $this->realPath();
        return substr($path, stripos($path, 'public') + 6);
    }

    /**
     * Apply manipulations specified by styles
     *
     * @return self
     */
    public function applyManipulations()
    {
        if (empty($this->_manipulator)) {
            return;
        }

        require_once 'Gem/Manipulate.php';
        $manipulator = new Gem_Manipulate($this->_manipulator);
        foreach ($this->_styles as $style) {
            $manipulator->manipulate($this->realPath(), $this->path() . '/'. $this->filename($style['name']), $style['options']);
        }
    }
}
