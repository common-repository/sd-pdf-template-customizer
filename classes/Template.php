<?php

class SD_PDF_Template_Customizer_Template
{
	public $created;

	public $description;

	public $id;
	
	public $moderation_text;

	public $name;

	public $source_pdf;

	public $tg_id;
	
	/**
		@brief		Return a list of keys this template contains.
		
		For use in saving and restoring data into the class.
		
		@return		Array of strings.
	**/
	public function keys()
	{
		return array(
			'created',
			'description',
			'id',
			'moderation_text',
			'name',
			'source_pdf',
			'tg_id',
		);
	}
}