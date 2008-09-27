<?php
/**
 * Gem - File Uploading for Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Gem
 * @package    Gem_Db_Table_Plugin
 * @copyright  Copyright (c) 2008 Johan Nilsson. (http://www.markupartist.com)
 * @license    New BSD License
 */

require_once 'Zend/Db/Table/Plugin/Abstract.php';
require_once 'Gem/File.php';

/**
 * Attachment Plugin for db table
 *
 */
class Gem_Db_Table_Plugin_Attachment extends Zend_Db_Table_Plugin_Abstract
{
    /**
     * Newly created attachment.
     *
     * @var Gem_File
     */
    private $_attachment = null;

    /**
     * Previous created attachment if any
     *
     * @var Gem_File
     */
    private $_previousAttachment = null;

    /**
     * Marked as new?
     *
     * @var boolean
     */
    private $_isNew = false;

    /**
     * Inflector
     *
     * @var Filter_Inflector
     */
    private $_inflector = null;

    /**
     * Inflector target
     *
     * @var string
     */
    private $_defaultTarget = ':model/:id';

    /**
     * Callback to be used on the model for manipulating the store path
     *
     * @var storePath
     */
    private $_storePathCallback = "getAttachmentStorePath";

    /**
     * creates and returnes the real path of an attachment.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $filename
     * @return string
     */
    public function getRealPath(Zend_Db_Table_Row_Abstract $row, $filename)
    {
        $parts = array(
            $this->getStorePath($row),
            $filename
        );

        return implode('/', $parts);
    }

    /**
     * Get store path string
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return string
     */
    public function getStorePath(Zend_Db_Table_Row_Abstract $row)
    {
        $inflector = $this->getInflector();
        $modelName = $row->getTableClass();
        $storePath = "";
        if (method_exists($modelName, $this->_storePathCallback)) {
            $storePath = call_user_func_array(array($modelName, $this->_storePathCallback), array($inflector, $row));
        } else {
            if (false === isset($this->_options['store_path'])) {
                throw new Exception('"store_path" must be set in the configuration');
            }
            
            if (isset($this->_options['store_target'])) {
                $target = $this->_options['store_target'];
            } else {
                $target = $this->_defaultTarget;
            }

            $inflector->setTarget(implode('/', array($this->_options['store_path'], $target)));

            $source = array(
                'id'    => $row->id, 
                'model' => $modelName,
            );

            if (array_key_exists("created_on", $row->toArray())) {
                $source['year']  = date('Y', strtotime($row->created_on));
                $source['month'] = date('m', strtotime($row->created_on));
                $source['day']   = date('d', strtotime($row->created_on));
            }

            $storePath = $inflector->filter($source);
        }

        return $storePath;
    }

    /**
     * creates and returnes the real path of an attachment.
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @param string $filename
     * @return string
     */
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
            $value = new Gem_File($this->getRealPath($row, $value), $value, $this->_options['manipulator']);
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

            $path = $this->getRealPath($row, $this->_attachment->originalFilename());

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
