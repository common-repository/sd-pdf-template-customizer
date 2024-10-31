<?php
/**
	@brief		A font available for the fields.
	
	@see		SD_PDF_Template_Customizer_Template_Field
	@author		Edward Plainview	edward.plainview@sverigedemokraterna.se
**/
class SD_PDF_Template_Customizer_Template_Field_Font
{
	/**
		Is this font bold?
		@var	$bold
	**/
	public $bold = false;

	/**
		Human-readable font description.
		@var	$bold
	**/
	public $description = 'Times New Roman';

	/**
		Unique, machine ID of font.
		@var	$bold
	**/
	public $id;

	/**
		Is this font italic?
		@var	$italic
	**/
	public $italic = false;

	/**
		Complete name of font.
		@var	$name
	**/
	public $name = 'times';
}

// -------------------------------------------------------------------------------------------------
// ----------------------------------------- Fields
// -------------------------------------------------------------------------------------------------

class SD_PDF_Template_Customizer_Template_Field
{
	/**
		Serialized data that is serialize()'d or unserialize()'d before DB transactions.
		@var	$data
	**/
	public $data;

	/**
		Description for user.
		@var	$description
	**/
	public $description = '';
	
	/**
		ID of template field.
		@var	$id
	**/
	public $id;

	/**
		Field name
		@var	$name
	**/
	public $name = '';
	
	/**
		Order of field in template.
		@var	$order
	**/
	public $order = 0;

	/**
		Type of field.
		
		Default is textfield, until further notice.
		
		@var	$type
	**/
	public $type = 'textfield';
	
	/**
		ID of parent template.
		@var	$t_id
	**/
	public $t_id;
	
	/**
		@brief		Shortcut to enable this class to translate strings.
		
		All parameters are automatically detected and passed on to the base's _() method.
	**/	
	public function _()
	{
		global $SD_PDF_Template_Customizer;

		$args = func_get_args();
		return call_user_func_array(array( $SD_PDF_Template_Customizer, '_' ), $args );
	}
	
	public function __construct()
	{
		$this->data = new stdClass();
		$this->description = '';
		$this->name = date( 'Y-m-d H:i:s' );		// just a default name
	}
	
	/**
		@brief		Add field specific inputs to the array of inputs shown to the admin.
		
		@param		$inputs
					Array of inputs to expand.
		
		@return		The expanded array.
	**/
	public function add_admin_inputs( $inputs )
	{
		$inputs[ 'type' ] = array(
			'name' => 'type',
			'type' => 'hidden',
			'value' => $this->type,
		);
		
		$inputs[ 'name' ] = array(
			'label' => $this->_( 'Name' ),
			'description' => $this->_( 'The name of the field, as displayed to the user.' ),
			'maxlength' => 200,
			'size' => 50,
			'type' => 'text',
			'value' => $this->name,
		);

		$inputs[ 'description' ] = array(
			'label' => $this->_( 'Description' ),
			'description' => $this->_( 'Describe the field to the user.' ),
			'maxlength' => 200,
			'size' => 50,
			'type' => 'text',
			'validation' => array( 'empty' => true ),
			'value' => $this->description,
		);

		return $inputs;
	}
	
	/**
		@brief		Add the correct type of placement to this field.
	**/
	public function add_placement()
	{
		$this->placements[] = $this->new_placement();
	}
	
	/**
		@brief		Expand the placement's input array with specific inputs.
		
		@param		$inputs
					Array of inputs to expand.
		
		@return		The expanded array.
	**/
	public function add_placement_inputs( $inputs )
	{
		$inputs[ 'x' ] = array(
			'description' => $this->_( 'Measured in mm' ),
			'label' => $this->_( 'X offset' ),
			'maxlength' => 4,
			'size' => 4,
			'type' => 'text',
		);

		$inputs[ 'y' ] = array(
			'description' => $this->_( 'Measured in mm' ),
			'label' => $this->_( 'Y offset' ),
			'maxlength' => 4,
			'size' => 4,
			'type' => 'text',
		);

		$inputs[ 'height' ] = array(
			'label' => $this->_( 'Height' ),
			'maxlength' => 4,
			'size' => 4,
			'type' => 'text',
		);

		$inputs[ 'width' ] = array(
			'label' => $this->_( 'Width' ),
			'maxlength' => 4,
			'size' => 4,
			'type' => 'text',
		);

		return $inputs;
	}
	
	/**
		@brief		Add user specific field inputs to the array of inputs shown to the user.
		
		@param		$inputs
					Array of inputs to expand.
		
		@return		The expanded array.
	**/
	public function add_user_inputs( $inputs )
	{
		return $inputs;
	}
	
	/**
		@brief		Clones the customization data from an existing customization.
		
		@param		$source_c
					The customization with the source data.
		
		@param		$target_c
					The target customization that will receive the original's data.
	**/	
	public function clone_customization_data( $source_c, $target_c )
	{
		$field_name = $this->name;
		$target_c->data->$field_name = $source_c->data->$field_name;
	}
	
	/**
		@brief		Construct the correct type of field.
		
		@param		$type
					Type of field. Default is a Textfield.
		
		Currently only textfield and textarea are supported.
	**/
	public static function construct( $type = null )
	{
		switch( $type )
		{
			case 'image':
				return new SD_PDF_Template_Customizer_Template_Image_Field(); 
				break;
			case 'textarea':
				return new SD_PDF_Template_Customizer_Template_Textarea_Field(); 
				break;
			default:
				// Default is a textfield.
				return new SD_PDF_Template_Customizer_Template_Textfield_Field(); 
				break;
		}
	}
	
	/**
		@brief		This field is about to be deleted.
	**/
	public function delete()
	{
	}
	
	/**
		@brief		Describe this field to the user.
		
		@return		An HTML string that describes the field in the field overview. 
	**/
	public function describe()
	{
		$rv = wpautop( $this->_( 'Description: <em>%s</em>', $this->description ) );
		return $rv;
	}
	
	/**
		@brief		Return a list of active properties / keys this class uses to store data.
		
		@return		Array of property names.
	**/
	public function keys()
	{
		return array(
			'description',
			'name',
			'placements',
			'type',
		);
	}
	
	/**
		@brief		Generate a new placement object that matches this type of field.
		
		@return		A subclass of SD_PDF_Template_Customizer_Template_Field_Placement.
		@see		SD_PDF_Template_Customizer_Template_Field_Placement
	**/
	public function new_placement()
	{
		return new SD_PDF_Template_Customizer_Template_Field_Placement();
	}
	
	/**
		@brief		Parses the $_POST variables and saves whatever data is of interest.
		
		@param		$post
					Array from the $_POST that corresponds with this field's names.
	**/
	public function parse_admin_post( $post )
	{
		foreach( $this->keys() as $key )
		{
			if ( ! isset( $post[ $key ] ) || is_array( $post[ $key ] ) )
				continue;
			$this->$key = $post[ $key ];
		}
	}
	
	/**
		@brief		Saves the post data to the customization.
		
		@return		The value extracted from the post.
	**/	
	public function save_post_to_customization_data( $post, $customization )
	{
		$field_name = $this->name;
		$slug = $this->slug( $field_name );

		$value = $post[ $slug ];
		
		$customization->data->$field_name = $value;
		return $value;
	}
	
	/**
		@brief		Prepare this object to be saved in the database.
		
		Serializes and base64's active properties.
	**/
	public function serialize()
	{
		$data = new stdClass();
		foreach ( $this->keys() as $key )
			$data->$key = $this->$key;
		$data = serialize( $data );
		$data = base64_encode( $data );
		$this->data = $data;
	}
	
	/**
		@brief		Wrapper method to access the Base's slug method.
	**/
	public function slug( $string )
	{
		global $SD_PDF_Template_Customizer;
		return $SD_PDF_Template_Customizer->slug( $string );
	}

	/**
		@brief		Unserializes database data into the properties.
		
		@param		Serialized string from the database.
	**/
	public function unserialize( $data )
	{
		$data = base64_decode( $data );
		$data = unserialize( $data );
		foreach ( $this->keys() as $key )
			if ( isset( $data->$key ) )
				$this->$key = $data->$key;
	}
	
	/**
		@brief		Retrieves any customization data associated to this field.
		
		@param		$customization
					An SD_PDF_Template_Customizer_Customization object.
	**/
	public function use_customization_data( $customization )
	{
	}
}

/**
	@brief		A field of type image.
**/
class SD_PDF_Template_Customizer_Template_Image_Field
	extends SD_PDF_Template_Customizer_Template_Field
{
	/**
		The filename of the image in the image directory.
		@var	$filename
	**/
	public $filename = 'empty';
	
	public $type = 'image';
	
	public function add_user_inputs( $inputs )
	{
		parent::add_user_inputs( $inputs );
		
		$slug = $this->slug( $this->name );

		if ( $this->filename == '' )
		{
			$inputs[ $slug ] = array(
				'description' => $this->description,
				'label' => $this->name,
				'type' => 'file',
			);
		}
		else
		{
			$image_filename = substr( $this->filename, 33 );
			$inputs[ md5( rand( 0, time()) ) ] = array(
				'description' => $this->_( 'Remove the uploaded image: %s%s%s',
					'<a href="' . $this->get_image_url() . '">',
					$image_filename,
					'</a>'
				),
				'label' => $this->_( 'Clear the image' ),
				'name' => $slug,
				'nameprefix' => '[clear_image]',
				'type' => 'checkbox',
			);
		}

		return $inputs;
	}

	public function clone_customization_data( $source_c, $target_c )
	{
		parent::clone_customization_data( $source_c, $target_c );
		// Make a copy of the image.
		$field_name = $this->name;

		$old_filename = $source_c->data->$field_name;
		$this->filename = $old_filename;
		$old_filepath = $this->get_image_filename();
		
		$new_filename = md5( time() . rand(0, time() ) ) . substr( $old_filename, 32 );
		$this->filename = $new_filename;
		$new_filepath = $this->get_image_filename();
		
		copy( $old_filepath, $new_filepath );
		
		$target_c->data->$field_name = $this->filename;
	}

	public function describe()
	{
		$rv = parent::describe();
		$filename = ( $this->filename == '' ? $this->_( 'No image uploaded' ) : $this->filename );
		$rv .= wpautop( $this->_( 'Image: %s', $filename ) );
		return $rv;
	}
	
	public function delete()
	{
		if ( ! $this->has_image() )
			return;
		
		unlink( $this->get_image_filename() );
	}
	
	public function get_image_filename()
	{
		return SD_PDFT_CUSTOMIZATION_IMAGES_DIR . '/' . $this->filename;
	}
	
	public function get_image_url()
	{
		return SD_PDFT_CUSTOMIZATION_IMAGES_URL . '/' . $this->filename;
	}
	
	/**
		@brief		Return whether the field has a file uploaded.
		
		@return		True, if there is an uploaded file.
	**/
	public function has_image()
	{
		return $this->filename != '';
	}
	
	/**
		@brief		Create a new SD_PDF_Template_Customizer_Template_Image_Field_Placement object.
		@see		SD_PDF_Template_Customizer_Template_Image_Field_Placement
	**/
	public function new_placement()
	{
		return new SD_PDF_Template_Customizer_Template_Image_Field_Placement();
	}
	
	public function keys()
	{
		$keys = parent::keys();
		foreach( array(
			'filename',
		) as $key )
			$keys[] = $key;
		return $keys;
	}

	/**
		@brief		Saves the post data to the customization.
		
		@return		The value extracted from the post.
	**/	
	public function save_post_to_customization_data( $post, $customization )
	{
		$slug = $this->slug( $this->name );
		if ( isset( $post[ 'clear_image' ][ $slug ] ) )
		{
			if ( $this->filename != '' )
			{
				unlink( $this->get_image_filename() );
				$this->filename = ''; 
			}
		}
		else
		{
			// Was an image uploaded?
			$file = $_FILES[ $slug ];
			if ( $file[ 'size' ] < 128 )		// No image is less than 128 bytes...
				return;
			
			// File must be an image
			if ( strpos( $file[ 'type' ], 'image' ) === false )
				return;
			
			// Move the image to its right place.
			$this->filename = md5( time() . rand( 0, time() ) ) . '_' . $file[ 'name' ];
			rename( $file[ 'tmp_name' ], $this->get_image_filename() );
		}
		$field_name = $this->name;
		$customization->data->$field_name = $this->filename;
	}
	
	public function use_customization_data( $customization )
	{
		$field_name = $this->name;
		$this->filename = $customization->data->$field_name;
	}
}

/**
	@brief		A field of type text.
	
	Acts as a superclass for the textfield and textarea classes.
**/
class SD_PDF_Template_Customizer_Template_Text_Field
	extends SD_PDF_Template_Customizer_Template_Field
{
	/**
		Font ID.
		@var	$font_id
	**/
	public $font_id = 'times';

	/**
		Font size.
		@var	$font_size
	**/
	public $font_size = 10;

	/**
		Font color as an array of RBG.
		@var	$font_color
	**/
	public $font_color = '0,0,0';

	/**
		The actual text that will be printed on the PDF.
		@var	$text
	**/
	public $text;
	
	/**
		We are the superclass that all text fields have in common.
		@var	$type
	**/
	public $type = 'text';
	
	public function add_admin_inputs( $inputs )
	{
		$inputs = parent::add_admin_inputs( $inputs );
		
		$inputs[ 'type' ] = array(
			'description' => $this->_( 'Type of text input.' ),
			'label' => $this->_( 'Type' ),
			'options' => array(
				'text' => $this->_( 'Line of text' ),
				'textarea' => $this->_( 'Block of text' ),
			),
			'type' => 'select',
			'value' => $this->type,
		);
		
		$inputs[ 'font_id' ] = array(
			'label' => $this->_( 'Font' ),
			'description' => $this->_( 'Default font.' ),
			'options' => $this->get_font_options(),
			'type' => 'select',
			'value' => $this->font_id,
		);

		$inputs[ 'font_size' ] = array(
			'label' => $this->_( 'Font size' ),
			'description' => $this->_( 'Default font size.' ),
			'maxlength' => 3,
			'size' => 3,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 2,
				'valuemax' => 999,
			),
			'value' => $this->font_size,
		);
		
		$inputs[ 'font_color' ] = array(
			'label' => $this->_( 'Font color' ),
			'description' => $this->_( 'Font color as RBG. Black is 0,0,0 and white is 255,255,255.' ),
			'maxlength' => 11,
			'size' => 11,
			'type' => 'text',
			'value' => $this->font_color,
		);
		
		return $inputs;
	}
	
	/**
		@brief		Convenience method to return a list of fonts as select options.
		@return		An array of select options. font id => font name
	**/
	public function get_font_options()
	{
		global $SD_PDF_Template_Customizer;
		return $SD_PDF_Template_Customizer->filters( 'sd_pdft_get_template_field_font_options' );
	}
	
	public function minmax( $var, $min, $max )
	{
		global $SD_PDF_Template_Customizer;
		return $SD_PDF_Template_Customizer->minmax( $var, $min, $max );
	}

	/**
		@brief		Create a new SD_PDF_Template_Customizer_Template_Text_Field_Placement object.
		@see		SD_PDF_Template_Customizer_Template_Text_Field_Placement
	**/
	public function new_placement()
	{
		return new SD_PDF_Template_Customizer_Template_Text_Field_Placement();
	}
	
	public function keys()
	{
		$keys = parent::keys();
		foreach( array(
			'font_color',
			'font_id',
			'font_size',
			'text',
		) as $key )
			$keys[] = $key;
		return $keys;
	}
	
	public function parse_admin_post( $post )
	{
		parent::parse_admin_post( $post );

		$this->font_id = $post[ 'font_id' ];
		$this->font_size = intval( $post[ 'font_size' ] );
		
		// Split and set the font color.
		$color = split( ',', $post[ 'font_color' ] );
		if ( count( $color ) != 3 )
			$color = array( 0, 0, 0 );
		else
		{
			$color = array(
				$this->minmax( $color[0], 0, 255 ),
				$this->minmax( $color[1], 0, 255 ),
				$this->minmax( $color[2], 0, 255 ),
			);
		}
		$this->font_color = implode( ',', $color );
	}

	public function use_customization_data( $customization )
	{
		$field_name = $this->name;
		$this->text = $customization->data->$field_name;
	}
}

class SD_PDF_Template_Customizer_Template_Textfield_Field
	extends SD_PDF_Template_Customizer_Template_Text_Field
{
	public $type = 'textfield';

	public function add_admin_inputs( $inputs )
	{
		$inputs = parent::add_admin_inputs( $inputs );

		$inputs[ 'text' ] = array(
			'label' => $this->_( 'Default text' ),
			'description' => $this->_( 'Default text that is used on newly-created customizations.' ),
			'maxlength' => 200,
			'size' => 50,
			'type' => 'text',
			'value' => $this->text,
		);
		
		return $inputs;
	}

	public function add_user_inputs( $inputs )
	{
		parent::add_user_inputs( $inputs );

		$inputs[ $this->slug( $this->name ) ] = array(
			'label' => $this->name,
			'description' => $this->description,
			'maxlength' => 200,
			'size' => 50,
			'type' => 'text',
			'value' => $this->text,
			'validation' => array( 'empty' => true ),
		);

		return $inputs;
	}

	public function describe()
	{
		$rv = parent::describe();
		$fonts = $this->get_font_options();
		$font = $fonts[ $this->font_id ];
		$rv .= $this->_( 'Text field. Font: %s, size %s, color %s.', $font, $this->font_size, $this->font_color );
		return $rv;
	}
	
	public function new_placement()
	{
		return new SD_PDF_Template_Customizer_Template_Textfield_Field_Placement();
	}
}

class SD_PDF_Template_Customizer_Template_Textarea_Field
	extends SD_PDF_Template_Customizer_Template_Text_Field
{
	public $type = 'textarea';

	public function add_admin_inputs( $inputs )
	{
		$inputs = parent::add_admin_inputs( $inputs );
		
		$inputs[ 'text' ] = array(
			'cols' => 160,
			'label' => $this->_( 'Default text' ),
			'description' => $this->_( 'Default text that is used on newly-created customizations.' ),
			'rows' => 10,
			'type' => 'textarea',
			'value' => $this->to_nbsp( $this->text ),
		);

		return $inputs;
	}

	public function add_user_inputs( $inputs )
	{
		parent::add_user_inputs( $inputs );
		
		$inputs[ $this->slug( $this->name ) ] = array(
			'cols' => 160,
			'label' => $this->name,
			'description' => $this->description,
			'rows' => 10,
			'type' => 'textarea',
			'validation' => array( 'empty' => true ),
			'value' => $this->text,
		);
		
		return $inputs;
	}

	public function describe()
	{
		$rv = parent::describe();
		$fonts = $this->get_font_options();
		$font = $fonts[ $this->font_id ];
		$rv .= $this->_( 'Text area. Font: %s, size %s, color %s.', $font, $this->font_size, $this->font_color );
		return $rv;
	}
	
	/**
		@brief		Fixes a textarea string by maybe prepending a non-breaking space in order to preserve empty lines.
		
		The textarea HTML element strips of empty newlines before the text. This method searches for a newline at the beginning of the string and
		appends a nbsp if necessary.
		
		The downside is that the textarea will have a space at the beginning, but that is filtered out afterwards (save_post_to_...).
		 
		@param		$string
					The textarea string to be.
		@return		The fixed string.
	**/
	public function to_nbsp( $string )
	{
		$string = preg_replace( '/^\n/', "&nbsp;\n", $string );
		$string = str_replace( "\n", "\r\n", $string );
		return $string;
	}
	
	public function from_nbsp( $string )
	{
		$string = str_replace( "\r", '', $string );
		$string = preg_replace( '/^\&nbsp\;/', '', $string );
		return $string; 
	}
	
	public function new_placement()
	{
		return new SD_PDF_Template_Customizer_Template_Textarea_Field_Placement();
	}

	public function parse_admin_post( $post )
	{
		parent::parse_admin_post( $post );
		$this->text = $this->from_nbsp( $post[ 'text' ] );
	}

	public function save_post_to_customization_data( $post, $customization )
	{
		$field_name = $this->name;
		$slug = $this->slug( $field_name );

		$value = $this->from_nbsp( $post[ $slug ] );
		$customization->data->$field_name = $value;
		return $value;
	}

	public function use_customization_data( $customization )
	{
		parent::use_customization_data( $customization );
		$field_name = $this->name;
		$this->text = $this->to_nbsp( $customization->data->$field_name );
	}
}

// -------------------------------------------------------------------------------------------------
// ----------------------------------------- Placements
// -------------------------------------------------------------------------------------------------

/**
	@brief		Object placement class for use in fields.
	
	Acts as a superclass of mer specialized placements.
**/
class SD_PDF_Template_Customizer_Template_Field_Placement
{
	/**
		Height of object.
		@var	$height
	**/
	protected $height = 0;

	/**
		Which page to write on.
		@var	$page
	**/
	protected $page = 0;

	/**
		Width of object.
		@var	$width
	**/
	protected $width = 0;

	/**
		X placement of object.
		@var	$x
	**/
	protected $x = 0;

	/**
		Y placement of object.
		@var	$y
	**/
	protected $y = 0;
	
	/**
		@brief		Shortcut to enable this class to translate strings.
		
		All parameters are automatically detected and passed on to the base's _() method.
	**/	
	public function _()
	{
		global $SD_PDF_Template_Customizer;

		$args = func_get_args();
		return call_user_func_array(array( $SD_PDF_Template_Customizer, '_' ), $args );
	}
	
	public function __get( $key )
	{
		return $this->$key;
	}
	
	public function __set( $key, $value )
	{
		$value = $this->sanitize_property( $property, $value );
		$this->$key = $value;
	}
	
	/**
		@brief		Adds placement specific inputs to the array of inputs shown to the user.
		
		@param		$inputs
					Array of inputs to expand.
		
		@return		The expanded array.
	**/
	public function add_inputs( $inputs )
	{
		$inputs[ 'page' ] = array(
			'label' => $this->_( 'Page' ),
			'maxlength' => 2,
			'size' => 2,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 99,
			),
			'value' => 0,
		);

		$inputs[ 'x' ] = array(
			'label' => $this->_( 'X' ),
			'description' => $this->_( 'X position on page in mm' ),
			'maxlength' => 5,
			'size' => 5,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 9999,
			),
			'value' => 0,
		);
		
		$inputs[ 'y' ] = array(
			'label' => $this->_( 'Y' ),
			'description' => $this->_( 'Y position on page in mm' ),
			'maxlength' => 5,
			'size' => 5,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 9999,
			),
			'value' => 0,
		);
		
		$inputs[ 'width' ] = array(
			'label' => $this->_( 'Width' ),
			'description' => $this->_( 'Width of object on page, in mm' ),
			'maxlength' => 5,
			'size' => 5,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 9999,
			),
			'value' => 0,
		);
		
		$inputs[ 'height' ] = array(
			'label' => $this->_( 'Height' ),
			'description' => $this->_( 'Height of object on page, in mm' ),
			'maxlength' => 5,
			'size' => 5,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 9999,
			),
			'value' => 0,
		);
		
		return $inputs;
	}
	
	/**
		@brief		Describe this placement to the user.
		
		@return		An HTML string that describes the field in the field overview. 
	**/
	public function describe()
	{
		return '';
	}
	
	/**
		@brief		Is this a valid placement?
		
		Used to decide whether to keep this placement in the field's placement array.
	**/
	public function is_valid()
	{
		return $this->page > 0;
	}
	
	/**
		@brief		Return a list of active properties / keys this class uses to store data.
		
		@return		Array of property names.
	**/
	public function keys()
	{
		return array(
			'page',
			'height',
			'width',
			'x',
			'y',
		);
	}
	
	public function minmax( $var, $min, $max )
	{
		global $SD_PDF_Template_Customizer;
		return $SD_PDF_Template_Customizer->minmax( $var, $min, $max );
	}

	/**
		@brief		Parses the $_POST variables and saves whatever data is of interest.
		
		@param		$post
					Array from the $_POST that corresponds with this field's names.
	**/
	public function parse_admin_post( $post )
	{
		foreach( $this->keys() as $key )
			$this->$key = $this->sanitize_property( $key, $post[ $key ] );
	}
	
	/**
		@brief		Modify the TCPDF with our (and the field's) values.
		
		@param		$field
					The SD_PDF_Template_Customizer_Field object from which to fetch data.
		
		@pdf		The TCPDF object to modify.
	**/
	public function pdf( $field, $pdf )
	{
		$value = $field->value;
		
		$pdf->setXY( $this->x, $this->y );
	}
	
	/**
		@brief		Sanitizies / cleans up / check_plains a value before inserting it into a property.
		
		@param		$property
					Name of property that will receive the new value.
		
		@param		$value
					Value to be cleaned up.
	**/
	
	public function sanitize_property( $property, $value )
	{
		switch( $property )
		{
			case 'page':
				$value = intval( trim( $value ) );
				break;
			case 'height':
			case 'width':
			case 'x':
			case 'y':
				$value = floatval( trim( $value ) );
				break;
		}
		return $value;
	}
}

/**
	@brief		Image placement.
**/
class SD_PDF_Template_Customizer_Template_Image_Field_Placement
	extends SD_PDF_Template_Customizer_Template_Field_Placement
{
	public function describe()
	{
		return $this->_( 'Page %s, x,y: (%s, %s) w: %s, h: %s',
			$this->page,
			$this->x,
			$this->y,
			$this->width,
			$this->height
		);
	}
	
	public function pdf( $field, $pdf )
	{
		parent::pdf( $field, $pdf );
		
		$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );
		
		if ( ! $field->has_image() )
			return;
		
		if ( $field->filename != 'empty' )
			$image_filename = $field->get_image_filename();
		else
		{
			$image_filename = dirname( __FILE__ ) . '/../images/image_placeholder.png';
			$image_filename = realpath( $image_filename );
		}
		
		$pdf->Image(
			$image_filename,
			$this->x,
			$this->y,
			$this->width,
			$this->height,
			'',			// type = taken automatically from file name
			'',			// link
			'',			// align
			2			// resize
		);
	}
}

/**
	@brief		Superclass for text placements.
**/
class SD_PDF_Template_Customizer_Template_Text_Field_Placement
	extends SD_PDF_Template_Customizer_Template_Field_Placement
{
	/**
		Color.
		
		'' means "use the field's color".
	
		@see	SD_PDF_Template_Customizer_Template_Text_Field::font_color
		@var	$font_color
	**/
	protected $font_color = '';

	/**
		Font ID.

		'' means "use the field's font".
		
		@var	$font_id
	**/
	protected $font_id = '';

	/**
		Font size.
		
		0 means "use the field's font size".
		
		@var	$font_size
	**/
	protected $font_size = 0;
	
	/**
		Justification: L, C, R, J
		@var	$j
	**/
	protected $j = 'L';

	public function add_inputs( $inputs )
	{
		$inputs = parent::add_inputs( $inputs );
		
		$inputs[ 'j' ] = array(
			'label' => $this->_( 'Justification' ),
			'options' => array(
				'L' => $this->_( 'Left' ),
				'R' => $this->_( 'Right' ),
				'C' => $this->_( 'Centered' ),
				'J' => $this->_( 'Justified' ),
			),
			'type' => 'select',
		);

		$inputs[ 'font_id' ] = array(
			'label' => $this->_( 'Font' ),
			'options' => array_merge(
				array( '' => $this->_( 'No font' ) ),
				$this->get_font_options()
			),
			'type' => 'select',
			'value' => '',
		);

		$inputs[ 'font_size' ] = array(
			'label' => $this->_( 'Font size' ),
			'description' => $this->_( 'Size of the font in points.' ),
			'maxlength' => 3,
			'size' => 3,
			'type' => 'text',
			'validation' => array(
				'valuemin' => 0,
				'valuemax' => 999,
			),
			'value' => 10,
		);
		
		$inputs[ 'font_color' ] = array(
			'label' => $this->_( 'Font color' ),
			'description' => $this->_( 'Font color as RBG. Black is 0,0,0 and white is 255,255,255.' ),
			'maxlength' => 11,
			'size' => 11,
			'type' => 'text',
		);
		
		return $inputs;
	}
	
	public function describe()
	{
		return $this->_( 'Page %s, x,y: (%s, %s) w: %s, h: %s, j: %s, color: %s',
			$this->page,
			$this->x,
			$this->y,
			$this->width,
			$this->height,
			$this->j,
			$this->font_color
		);
	}
	
	/**
		@brief		Convenience method to return a list of fonts as select options.
		@return		An array of select options. font id => font name
	**/
	private function get_font_options()
	{
		global $SD_PDF_Template_Customizer;
		return $SD_PDF_Template_Customizer->filters( 'sd_pdft_get_template_field_font_options' );
	}

	public function keys()
	{
		$keys = parent::keys();
		foreach( array(
			'font_color',
			'font_id',
			'font_size',
			'j',
		) as $key )
			$keys[] = $key;
		return $keys;
	}
	
	public function pdf( $field, $pdf )
	{
		parent::pdf( $field, $pdf );
		
		// Color
		$color = ( $this->font_color != '' ? $this->font_color : $field->font_color );
		$color = explode( ',', $color );
		$pdf->setTextColor( $color[0], $color[1], $color[2] );
		
		// Font handling.
		global $SD_PDF_Template_Customizer;
		$fonts = $SD_PDF_Template_Customizer->filters( 'sd_pdft_get_template_field_fonts' );
		
		$font_id = ( $this->font_id != '' ? $this->font_id : $field->font_id );
		$font_size = ( $this->font_size != '' ? $this->font_size : $field->font_size );
		$font = $fonts[ $font_id ];
		$style = '';
		$style .= ( $font->bold ? 'b' : '' );
		$style .= ( $font->italic ? 'i' : '' );
		
		if ( ! isset( $pdf->pdft_used_fonts ) )
			$pdf->pdft_used_fonts = new stdClass();
		
		if ( ! isset( $pdf->pdft_used_fonts->$font_id ) )
		{
			$pdf->pdft_used_fonts->$font_id = true;
			if ( isset( $font->fontfile ) )
				$pdf->addFont( $font->name, $style, $font->fontfile );
		}
		
		$pdf->SetFont( $font->name, $style, $font_size, true );
	}
	
	public function sanitize_property( $property, $value )
	{
		$value = parent::sanitize_property( $property, $value );
		switch( $property )
		{
			case 'font_color':
				if ( $value != '' )
				{
					$value = explode( ',', $value );
					if ( count( $value ) != 3 )
						$value = array( 0, 0, 0 );
					$value = array(
						$this->minmax( $value[ 0 ], 0, 255 ),
						$this->minmax( $value[ 1 ], 0, 255 ),
						$this->minmax( $value[ 2 ], 0, 255 ),
					);
					$value = implode( ',', $value );
				}
				break;
			case 'font_size':
				$value = intval( trim( $value ) );
				break;
			case 'j':
				// Make sure that j is limited to only these values.
				switch( $value )
				{
					case 'L':
					case 'R':
					case 'C':
					case 'J':
						break;
					default:
						$value = 'L';
						break;
				} 
				break;
		}
		return $value;
	}
}

/**
	@brief		Textfield placement object.
**/
class SD_PDF_Template_Customizer_Template_Textfield_Field_Placement
	extends SD_PDF_Template_Customizer_Template_Text_Field_Placement
{
	public function pdf( $field, $pdf )
	{
		parent::pdf( $field, $pdf );
		
		$pdf->Cell(
			$this->width,
			$this->height,
			$field->text,
			'',							// Border
			'',							// Line
			$this->j					// Align
		);
	}
}

/**
	@brief		Textarea placement object.
**/
class SD_PDF_Template_Customizer_Template_Textarea_Field_Placement
	extends SD_PDF_Template_Customizer_Template_Text_Field_Placement
{
	public function pdf( $field, $pdf )
	{
		parent::pdf( $field, $pdf );
		
		$text = $field->from_nbsp( $field->text );

		$pdf->MultiCell(
			$this->width,
			$this->height,
			$text,
			'',							// Border
			$this->j					// Align
		);
	}
}
