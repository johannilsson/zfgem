<?php

require_once 'Zend/Db/Table/Plugin/Abstract.php';
require_once 'Gem/File.php';

class Gem_Db_Table_Plugin_Attachment extends Zend_Db_Table_Plugin_Abstract
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
        if ($this->_options['column'] == $columnName) {
            $value = new Gem_File($this->_createRealPath($row, $value), $value, $this->_options['manipulator']);
            $this->_attachment->addStyles($this->_options['styles']);
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
        if ($this->_options['column'] == $columnName) {
            $filePath = '';
            $fileName = '';

            if ($value instanceof ArrayObject) {
                if (false === $value->offsetExists('tmp_name')) {
                    throw new Exception('tmp_name must be set');
                }
                $filePath = $value->offsetGet('tmp_name');

                if (true === $value->offsetExists('name')) {
                    $fileName = $value->offsetGet('name');
                }
            } else if ( ($fileInfo = new SplFileInfo($value)) && $fileInfo->isFile() ) {
                $filePath = $fileInfo->getRealPath();
                $fileName = $fileInfo->getFilename();
            } else {
                throw new Exception('Not a valid attachment value ' . $value);
            }

            $this->_attachment = new Gem_File($filePath, $fileName, $this->_options['manipulator']);
            $this->_attachment->addStyles($this->_options['styles']);

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
        if (null !== $this->_attachment) {
            $this->_attachment->moveTo($this->_createRealPath($row, $this->_attachment->originalFilename()));
            $this->_attachment->applyManipulations();
        }
    }

}
