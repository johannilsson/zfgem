<?php

require_once 'Zend/Db/Table/Plugin/Abstract.php';
require_once 'Gem/Image.php';

class Gem_Plugin_Image extends Zend_Db_Table_Plugin_Abstract
{
    /**
     * Newly created attachment.
     *
     * @var unknown_type
     */
    private $_attachment = null;

    /**
     * creates and returnes the real path of an attachment.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $filename
     * @return string
     */
    private function _createRealPath(Zend_Db_Table_Row_Abstract $row, $filename)
    {
        // TODO: Try guessing the store_path if not set.
        // TODO: Make this path configuable, now it is hardcoded to 
        // /store/path/tableName/id/filename

        $parts = array(
            $this->_options['store_path'],
            strtolower($row->getTableClass()),
            $row->id,
            $filename
        );
        return implode('/', $parts);
    }

    private function _addStyles(Gem_File $file)
    {
        foreach ($this->_options['styles'] as $name => $options)
        {
            $file->addStyle($name, $options);
        }        
    }
    
    /**
     * Hook for getting column data, if the requested column is an attachment
     * an instance of an Gem_Image object is returned.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $columnName
     * @param string $value
     * @return string|Gem_Image
     */
    public function getColumn(Zend_Db_Table_Row_Abstract $row, $columnName, $value)
    {
        if ($this->_options['column'] == $columnName)
        {
            $value = new Gem_File($this->_createRealPath($row, $value), $value);
            $this->_addStyles($value);

        }
        return $value;
    }

    /**
     * setColumn
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $columnName
     * @param string $value
     * @return string
     */
    public function setColumn(Zend_Db_Table_Row_Abstract $row, $columnName, $value)
    {
        if ($this->_options['column'] == $columnName)
        {
            // Is it a file upload?
            if ($value instanceof App_Form_Element_FileValue)
            {
                $this->_attachment = new Gem_File($value->offsetGet('tmp_name'), $value->offsetGet('name'));
            }
            // Is it a path to an existing file?
            else if ( ($fileInfo = new SplFileInfo($value)) && $fileInfo->isFile() )
            {
                $this->_attachment = new Gem_File($fileInfo->getRealPath(), $fileInfo->getFilename());
            }
            else
            {
                // TODO: Is not working as expected.
                throw new Exception('Not a valid attachment value ' . $value);
            }

            $this->_addStyles($this->_attachment);
            $value = $this->_attachment->originalFilename();
        }
        return $value;
    }

    /**
     * If the saved row had an new attachment set, move the file and apply 
     * manipulations.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     */
    public function postSaveRow(Zend_Db_Table_Row_Abstract $row)
    {
        if (null !== $this->_attachment)
        {
            $this->_attachment->moveTo($this->_createRealPath($row, $this->_attachment->originalFilename()));
            $this->_attachment->applyManipulations();
        }
    }

}