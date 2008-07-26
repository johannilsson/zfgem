<?php

/**
 * File represention.
 *
 */
class Gem_File
{
	protected $_realPath;
	protected $_originalFilename;
	protected $_styles = array();

	/**
	 * Constructor
	 *
	 * @param string $realPath
	 * @param string $originalFilename
	 */
	public function __construct($realPath, $originalFilename)
	{
		$this->_realPath         = $realPath;
		$this->_originalFilename = $originalFilename;
	}

	/**
	 * Adds a new style to this file.
	 *
	 * @param string $name
	 * @param array $options
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
        foreach ($this->_styles as $style)
        {
            if ($name == $style['name'])
            {
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
        if ('' == $style)
        {
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
		if (is_uploaded_file($this->_realPath))
		{
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
		if (is_dir($newDirectory) || mkdir($newDirectory, 0755, true) !== false )
		{
			if (is_writable($newDirectory))
			{
				copy($this->_realPath, $destination);
				$this->_realPath = $destination;
		
				return $this;
			}
		}
		throw new RuntimeException('Could not write to ' . $newDirectory);
	}

	public function delete()
	{
		if (unlink($this->_realPath) === false)
		{
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
		if ($this->fileInfo()->isFile())
		{
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

	public function size()
	{
		filesize($this->_realPath);
	}

	public function contentType()
	{

	}

    /**
     * The url
     *
     * Strips public and everything before from the real path, hopes that public
     * is the name of the public directory and nothing is named like that before.
     *
     * @param  string $style
     * @return string
     */
    public function url($style = '')
    {
        $path = $this->realPath();
        return substr($path, stripos($path, 'public') + 6);
    }

    /**
     * Apply manipulations specified by this set styles
     *
     * @return self
     */
    public function applyManipulations()
    {
        $manipulator = new Gem_Manipulate();
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Gem_Manipulate_', 'Gem/Manipulate/');
        foreach ($this->_styles as $style) {
            $manipulatorName = $loader->load($style['options']['manipulator']);
            
            //$manipulator = Zend_Loader::loadClass($manipulatorName);

            $manipulation = self::getManipulatorInstance(array($manipulatorName, $this->realPath(), $this->path() . '/'. $this->filename($style['name']), $style['options']['width']));
            $manipulator->addManipulator($manipulation);
        }
        
        $manipulator->manipulate();
        
        //die('dsd');
        
        /*
        $manipulator = new Gem_Manipulate_ImageTransform();
        foreach ($this->_styles as $styleName => $styleOptions)
        {
            $manipulator->manipulate($this->realPath(), $this->path() . '/'. $this->filename($styleName), $styleOptions);
        }
        return $this;
        */
    }
    
    static public function getManipulatorInstance($plugin)
    {
        $args = array();

        if (is_array($plugin)) {
            $args = $plugin;
            $plugin = array_shift($args);
        }
        
        //$className = self::getPluginLoader()->load(ucfirst($plugin));
        $class = new ReflectionClass($plugin);

        /*
        if (!$class->implementsInterface('Zend_Db_Table_Plugin_Interface')) {
            require_once 'Zend/Db/Table/Plugin/Exception.php';
            throw new Zend_Db_Table_Plugin_Exception('Plugin must implement interface "Zend_Db_Table_Plugin_Interface".');
        }
        */
        if ($class->hasMethod('__construct')) {
            $object = $class->newInstanceArgs($args);
        } else {
            $object = $class->newInstance();
        }

        return $object;
    }
}