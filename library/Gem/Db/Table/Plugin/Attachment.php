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

    private $_previousAttachment = null;

    private $_isNew = false;

    private $_inflector = null;

    private $_defaultTarget = ':model/:id';

    /**
     * creates and returnes the real path of an attachment.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $filename
     * @return string
     */
    private function _createRealPath(Zend_Db_Table_Row_Abstract $row, $filename)
    {
        $storePath = $this->getFilteredStorePath($row);

        $parts = array(
            $storePath,
            $filename
        );

        return implode('/', $parts);
    }

    public function getFilteredStorePath(Zend_Db_Table_Row_Abstract $row)
    {
        if (false === isset($this->_options['store_path'])) {
            throw new Exception('"store_path" must be set in the configuration');
        }

        $inflector = $this->getInflector(implode('/', array($this->_options['store_path'], $this->_defaultTarget)));
        $storePath = $inflector->filter(array(
            'id'    => $row->id, 
            'model' => $row->getTableClass(),
            'year'  => date(date('Y'), time()),
            'month' => date(date('m'), time()),
            'day'   => date(date('d'), time()),
        ));

        return $storePath;
    }

    public function getInflector($target = null)
    {
        if (null === $this->_inflector) {
            $this->_inflector = new Zend_Filter_Inflector($target);
            // TODO: Fix rules
            $this->_inflector->setRules(array(
                ':model'  => array('StringToLower'),
                ':id'     => array('StringToLower'),
                ':year'   => array('StringToLower'),
                ':month'  => array('StringToLower'),
                ':day'    => array('StringToLower'),
            ));        
        }

        return $this->_inflector;    
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
        if ($this->_options['column'] == $columnName 
                && false === $value instanceof Gem_File
                && null !== $value) {
            $value = new Gem_File($this->_createRealPath($row, $value), $value, $this->_options['manipulator']);
            $value->addStyles($this->_options['styles']);
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

            // Save previous attachment, will be set to null if not set before.
            $this->_previousAttachment = $row->{$columnName};

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

            // Set magic values if possible
            if (array_key_exists("{$columnName}_filesize", $row->toArray())) {
                $name = "{$columnName}_filesize";
                $row->{$name} = $this->_attachment->size();
            }
            if (array_key_exists("{$columnName}_mime_type", $row->toArray())) {
                $name = "{$columnName}_mime_type";
                $row->{$name} = $this->_attachment->mimeContentType();
            }

            // Mark as a new upload
            $this->_isNew = true;
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
        if (null !== $this->_attachment && true === $this->_isNew) {

            if (null !== $this->_previousAttachment) {
                $this->_previousAttachment->deleteAll();
            }

            $path = $this->_createRealPath($row, $this->_attachment->originalFilename());

            $this->_attachment->moveTo($path);
            $this->_attachment->applyManipulations();
            $this->_isNew = false;
        }
    }

    /**
     * Deletes attachment and styles assocciated with it.
     *
     * @param Zend_Db_Table_Row_Abstract $row     
     */
    public function preDeleteRow(Zend_Db_Table_Row_Abstract $row)
    {
        $columnName = $this->_options['column'];
        if (array_key_exists($columnName, $row->toArray())) {
            $attachment = $row->{$columnName};
            $attachment->deleteAll();
        }
    }
}
