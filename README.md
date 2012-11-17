File uploading for Zend Framework 

*Warning* This was built as a prototype for a proposed plugn model for
Zend Framework database and will not work with todays version of ZF.

## Introduction 

Gem allows for easy file uploading and control of uploaded files. Gem works 
as an plugin to Zend_Db_Table using the proposed [Zend_Db_Table_Plugin][1].

### Usage

#### The Photo Model

    class Photos extends Zend_Db_Table
    {
        protected $_attachment = array(
            'column'      => 'image', 
            'store_path'  => /my/store/path/,
            'manipulator' => 'ImageTransform',
            'styles' => array(
                'square' => array( 
                     'size' => 'c75x75'), 
                'small' => array( 
                     'size' => '200x200'), 
                'medium' => array( 
                     'size' => '500x500'), 
                'large' => array( 
                     'size' => '1000x1000'), 
            ),
        );
    
        protected function _setupPlugins()
        {
            $attachment = new Gem_Db_Table_Plugin_Attachment($this->_attachment);
            $this->addPlugin($attachment);
        }
    }

#### Upload and Save

Create a new photo from a form post (you probably want to use Zend_Form and 
Zend_Form_Element_File instead).

    $photos = new Photos();
    $photo = $this->createRow();  
    $photo->image = new ArrayObject($_FILES['userfile']); // Or a real path to an existing file 
    $photo->save();

Once we call save the image is moved to the path specified in the model, and 
the manipulator that is specified will do its manipulation. In this example 
it will give us four different version of the uploaded file including the 
original.

#### Views

Once saved all we need to to display the image so just retrieve it as usual and 
pass it to your view.

    $photos = new Photos();
    $this->view->photo = $photos->fetchRow($photos->select()->where('id = ?', 1));

In the view all you need to do to display your image in the different versions is the 
following.

    echo $photo->image->small->url();
    echo $photo->image->medium->url();
    echo $photo->image->large->url();

## Custom store path

As default gem is using the following store target, ":model/:id" for the example above it would mean "photos/1". If you are not satisfied with this you could supply your own target. If you have a column named created_on you can also use the target parts ":year", ":month" and ":day".

    public $attachment = array(
		'column'        => 'image', 
		'store_path'    => '/my/store/path/',
		'store_target'  => ':model/:year/:month/:day/:id',
    );

If this is not enough you can also create a method named "getAttachmentStorePath". If this method exists a callback will be perfomed to this, it expects you to return a full string where to store the image. If this method is available there is no need to pass the "store_path" or "store_target" in the configuration.

    /**
     * @param Zend_Filter_Inflector $inflector
     * @param Zend_Db_Table_Row $row
     * @return string Full path of where to store the uploaded file
     **/
    public function getAttachmentStorePath(Zend_Filter_Inflector $inflector, Zend_Db_Table_Row $row)

## Requirement

* Zend framework standard [incubator][2] in the include path
* PEAR package [Image_Transform][3]

## Todo

* Validation (file size, image size etc.)
* Configuration, add usage of Zend_Config
* More than images...
* Manipulator changes

## Credits

Gem is inspired by [PaperClip][4] and [UploadColumn][5] that are available for ruby on rails.

## License

New BSD license, same as Zend Framework it self. 


[1]: http://framework.zend.com/wiki/display/ZFPROP/Zend_Db_Table_Plugin+-+Simon+Mundy%2C+Jack+Sleight  "Zend_Db_Table_Plugin"
[2]: http://framework.zend.com/svn/framework/standard/incubator/ "Zend Standard Incubator"
[3]: http://pear.php.net/package/Image_Transform "Image_Transform"
[4]: http://www.thoughtbot.com/projects/paperclip "PaperClip"
[5]: http://uploadcolumn.rubyforge.org/ "UploadColumn"
