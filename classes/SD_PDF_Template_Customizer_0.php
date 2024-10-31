<?php

class SD_PDF_Template_Customizer_0
	extends SD_PDF_Template_Customizer_Base
{
	public function admin_menu( $submenus )
	{
		return $submenus;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Add, and remove only modified rows in a table.
		
		The values array must have the actual value in the =>value part of the array. 
		
		@param		$options
					A stdClass containing the following
					- @b index_column Column for the search condition.
					- @b index_value Search condition value.
					- @b primary_column Column for the row ID's.
					- @b table_name The name of the table with which to work.
					- @b value_column Column for the data values.
					- @b values Array of values to be added / kept / removed.
	**/
	public function keep_table_rows( $options )
	{
		$values_to_add = array();
		$values_to_remove = array();
		
		// Find the current values.
		$query = "SELECT `" . $options->primary_column . "`, `" . $options->value_column . "` FROM `" . $options->table_name . "`
			WHERE `" . $options->index_column . "` = '" . $options->index_value . "'";
		$current_values = $this->array_rekey( $this->query( $query ), $options->value_column );
		
		// Add new values.
		foreach( $options->values as $value )
		{
			if ( ! isset( $current_values[ $value ] ) )
				$values_to_add[] = $value;
			unset( $current_values[ $value ] );
		}
		
		// Remove old ones.
		foreach( $current_values as $current_value )
			$values_to_remove[] = $current_value[ $options->primary_column ];
		
		if ( count( $values_to_add ) > 0 )
		{
			$values = array();
			foreach( $values_to_add as $value_to_add )
				$values[] = "('" . $options->index_value . "', '" . $value_to_add . "')";
			$query = "INSERT INTO `" . $options->table_name . "`
				(`" . $options->index_column . "`, `" . $options->value_column . "`)
				VALUES " . implode( ',', $values );
			$this->query( $query );
		}
		
		if ( count( $values_to_remove ) > 0 )
		{
			$query = "DELETE FROM `" . $options->table_name . "`
				WHERE `" . $options->primary_column . "` IN ('" . implode( "', '", $values_to_remove ) . "')";
			$this->query( $query );
		}
	}
	
	/**
		@brief		Is the current user a moderator? (or admin)
		
		@return		True, if the current user is a moderator.
	**/
	public function user_is_moderator()
	{
		if ( $this->role_at_least( 'administrator' ) )
			return true;
		
		$user_id = $this->user_id;
		$moderators = $this->filters( 'sd_pdft_get_all_template_group_moderators' );
		$moderators = $this->array_rekey( $moderators, 'user_id' );
		return isset( $moderators[ $user_id ] );
	}

}