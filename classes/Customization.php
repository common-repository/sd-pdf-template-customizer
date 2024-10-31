<?php

class SD_PDF_Template_Customizer_Customization
{
	public $created;	
	public $data;
	public $editable = 1;
	public $id;
	public $name;
	public $printable = 1;
	public $t_id;
	public $user_id;
	
	public function __construct()
	{
		$this->data = new stdClass();
	}
	
	/**
		@brief		Return a list of keys this template contains.
		
		For use in saving and restoring data into the class.
		
		@return		Array of strings.
	**/
	public function keys()
	{
		return array(
			'data',
			'created',
			'editable',
			'id',
			'name',
			'printable',
			't_id',
			'user_id',
		);
	}

	public function serialize()
	{
		$this->data = serialize( $this->data );
		$this->data = base64_encode( $this->data );
	}
	
	public function unserialize()
	{
		$this->data = base64_decode( $this->data );
		$this->data = unserialize( $this->data );
	}
}
