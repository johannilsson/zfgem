<?php

/**
 * File represention.
 *
 */
class Gem_File
{
	protected $_realPath;
	protected $_originalFilename;

	/**
	 * Constructor
	 *
	 * @param string $realPath
	 * @param string $originalFilename
	 */
	public function __construct($realPath, $originalFilename)
	{
		$this->_realPath = $realPath;
		$this->_originalFilename = $originalFilename;
	}

	/**
	 * The filename
	 *
	 * @return string
	 */
	public function filename()
	{
		return basename($this->_realPath);		
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
}