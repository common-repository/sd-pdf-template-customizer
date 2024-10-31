<?php

class SD_PDF_Template_Customizer_Template_Groups
	extends SD_PDF_Template_Customizer_0
{
	public function __construct( $file )
	{
		parent::__construct( $file );
		
		add_filter( 'sd_pdft_count_template_group_moderators',		array( $this, 'sd_pdft_count_template_group_moderators' ) );
		add_filter( 'sd_pdft_delete_template_group',				array( $this, 'sd_pdft_delete_template_group' ) );
		add_filter( 'sd_pdft_is_template_group_moderated',			array( $this, 'sd_pdft_is_template_group_moderated' ) );
		add_filter( 'sd_pdft_get_template_group',					array( $this, 'sd_pdft_get_template_group' ) );
		add_filter( 'sd_pdft_get_template_group_edit_url',			array( $this, 'sd_pdft_get_template_group_edit_url' ) );
		add_filter( 'sd_pdft_get_template_group_moderators',		array( $this, 'sd_pdft_get_template_group_moderators' ) );
		add_filter( 'sd_pdft_get_template_groups',					array( $this, 'sd_pdft_get_template_groups' ) );
		add_filter( 'sd_pdft_update_template_group',				array( $this, 'sd_pdft_update_template_group' ) );
		add_filter( 'sd_pdft_update_template_group_moderators',		array( $this, 'sd_pdft_update_template_group_moderators' ) );
		add_filter( 'sd_pdft_user_moderates',						array( $this, 'sd_pdft_user_moderates' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin methods
	// --------------------------------------------------------------------------------------------
	
	public function admin_menu()
	{
		$submenus = parent::admin_menu( $submenus );

		if ( ! $this->role_at_least( 'administrator' ) )
			return $submenus;

		$submenus[] = array(
			'sd_pdf_template_customizer',
			$this->_('Template groups'),
			$this->_('Template groups'),
			'read',
			'sd_pdft_template_groups',
			array( &$this, 'admin_template_groups' )
		);
		
		return $submenus;
	}
	
	public function admin_template_groups()
	{
		$tab_data = array(
			'default'	=>	'overview',
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data['page_titles']	['overview'] = $this->_( 'Template group overview' );
		$tab_data['tabs']			['overview'] = $this->_( 'Overview' );
		$tab_data['functions']		['overview'] = 'admin_template_groups_overview';
		
		if ( isset( $_GET[ 'tab' ] ) )
		{
			switch( $_GET[ 'tab' ] )
			{
				case 'edit':
					$tab_data['page_titles']	['edit'] = $this->_( 'Edit template group' );
					$tab_data['tabs']			['edit'] = $this->_( 'Edit' );
					$tab_data['functions']		['edit'] = 'admin_template_groups_edit';
				break;
				case 'moderators':
					$tab_data['page_titles']	['moderators'] = $this->_( 'Template group moderators' );
					$tab_data['tabs']			['moderators'] = $this->_( 'Moderators' );
					$tab_data['functions']		['moderators'] = 'admin_template_groups_moderators';
				break;
			}
		}
		
		$this->tabs( $tab_data );
	}

	public function admin_template_groups_overview()
	{
		if ( isset( $_POST['action_submit'] ) && isset( $_POST['ids'] ) )
		{
			if ( $_POST['action'] == 'delete' )
			{
				foreach( $_POST['ids'] as $id => $ignore )
				{
					$group = apply_filters( 'sd_pft_get_template_group', $id );
					if ( $group !== false )
					{
						apply_filters( 'sd_pdft_delete_template_group', $id );
						$this->message( $this->_( 'Group template <em>%s</em> deleted.', $group->name ) );
					}
				}
			}	// delete
		}
		
		if ( isset( $_POST['create'] ) )
		{
			$group = new SD_PDF_Template_Customizer_Template_Group();
			$group->name = $this->_( 'Template group created %s', $this->now() );

			$group = apply_filters( 'sd_pdft_update_template_group', $group );
			
			$edit_link = $this->filters( 'sd_pdft_get_template_group_edit_url', $group->id );
			$edit_link = $this->_( '%sEdit the template group%s', '<a href="' . $edit_link . '">', '</a>');
					
			$this->message( $this->_( 'Template group created! %s', $edit_link ) );
		}

		$form = $this->form();
		$rv = $form->start();

		$groups = $this->filters( 'sd_pdft_get_template_groups' );
		
		if ( count( $groups ) < 1 )
			$this->message( $this->_( 'There are no template groups available.' ) );
		else
		{
			$t_body = '';
			foreach( $groups as $group )
			{
				$input_select = array(
					'type' => 'checkbox',
					'checked' => false,
					'label' => $group->name,
					'name' => $group->id,
					'nameprefix' => '[ids]',
				);
				
				$edit_link = add_query_arg( array(
					'tab' => 'edit',
					'id' => $group->id
				) );
				
				// ACTION time.
				$row_actions = array();
				
				$row_actions[] = '<a href="'.$edit_link.'">'. $this->_('Edit') . '</a>';
				
				$moderators_link = add_query_arg( array(
					'tab' => 'moderators',
					'id' => $group->id
				) );
				$row_actions[] = '<a title="' . $this->_('Edit the moderators associated with this template group') . '" href="' . $moderators_link . '">'. $this->_('Moderators') . '</a>';
				
				$row_actions = implode( '&emsp;<span class="sep">|</span>&emsp;', $row_actions );
				
				$info = array();
				
				$moderator_count = $this->filters( 'sd_pdft_count_template_group_moderators', $group->id );
				if ( $moderator_count > 0 )
					$info[] = $this->_( 'The group has %s moderators.', $moderator_count );

				$info = implode( '</div><div>', $info );
				
				$t_body .= '<tr>
					<th scope="row" class="check-column">' . $form->make_input($input_select) . ' <span class="screen-reader-text">' . $form->make_label($input_select) . '</span></th>
					<td>
						<div>
							<a
							title="' . $this->_('Edit this template group') . '"
							href="'. $edit_link .'">' . $group->name . '</a>
						</div>
						<div class="row-actions">' . $row_actions . '</a>
					</td>
					<td><div>' . $info . '</div></td>
				</tr>';
			}
			
			$input_actions = array(
				'type' => 'select',
				'name' => 'action',
				'label' => $this->_('With the selected rows'),
				'options' => array(
					array( 'value' => '', 'text' => $this->_('Do nothing') ),
					array( 'value' => 'delete', 'text' => $this->_('Delete') ),
				),
			);
			
			$input_action_submit = array(
				'type' => 'submit',
				'name' => 'action_submit',
				'value' => $this->_('Apply'),
				'css_class' => 'button-secondary',
			);
			
			$selected = array(
				'type' => 'checkbox',
				'name' => 'check',
			);
			
			$rv .= '
				<div>
					' . $form->make_label( $input_actions ) . '
					' . $form->make_input( $input_actions ) . '
					' . $form->make_input( $input_action_submit ) . '
				</div>
				<table class="widefat">
					<thead>
						<tr>
							<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>
							<th>' . $this->_('Name') . '</th>
							<th>' . $this->_('Info') . '</th>
						</tr>
					</thead>
					<tbody>
						'.$t_body.'
					</tbody>
				</table>
			';
		}
		
		$rv .= '<h3>' . $this->_( 'Create a new template group' ) . '</h3>';

		$input_create = array(
			'type' => 'submit',
			'name' => 'create',
			'value' => $this->_( 'Create a new template group' ),
			'css_class' => 'button-primary',
		);
		
		$rv .= '<p>' . $form->make_input( $input_create ) . '</p>';

		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_template_groups_edit()
	{
		$id = intval( $_GET[ 'id' ] );
		$group = $this->filters( 'sd_pdft_get_template_group', $id );
		if ( $group === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.', $id ) );
			return;
		}
		
		$form = $this->form();
		
		$inputs = array(
			'name' => array(
				'type' => 'text',
				'name' => 'name',
				'label' => $this->_( 'Name' ),
				'size' => 50,
				'maxlength' => 200,
			),
		);
		
		if ( isset( $_POST['update'] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$_POST = $this->strip_post_slashes();
				$group = $this->filters( 'sd_pdft_get_template_group', $id );
				$group->name = $_POST[ 'name' ];
				
				$this->filters( 'sd_pdft_update_template_group', $group );
				
				$this->message( $this->_('The template group has been updated!') );
			}
			else
			{
				$this->error( implode('<br />', $result) );
			}
		}
		
		$inputs['name']['value'] = $group->name;
		
		$input_update = array(
			'type' => 'submit',
			'name' => 'update',
			'value' => $this->_( 'Update' ),
			'css_class' => 'button-primary',
		);


		$rv .= '
			' . $form->start() . '
			
			' . $this->display_form_table( $inputs ).'

			<p>
				' . $form->make_input( $input_update ) . '
			</p>

			' . $form->stop() . '
		';
		
		echo $rv;
	}

	public function admin_template_groups_moderators()
	{
		$id = intval( $_GET[ 'id' ] );
		$group = $this->filters( 'sd_pdft_get_template_group', $id );
		if ( $group === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.', $id ) );
			return;
		}
		
		$form = $this->form();
		
		if ( isset( $_POST['update'] ) )
		{
			$options = new stdClass();
			$options->table_name = $this->wpdb->base_prefix . "sd_pdft_template_group_moderators";
			$options->index_column = 'tg_id';
			$options->index_value = $id;
			$options->primary_column = 'id';
			$options->value_column = 'user_id';
			$options->values = $_POST[ 'users' ];
			$this->keep_table_rows( $options );
			$this->message( $this->_( 'The moderators for this template group have been updated!' ) ); 
		}
		
		$users = get_users();
		$moderators = $this->filters( 'sd_pdft_get_template_group_moderators', $id );
		$moderators = $this->array_rekey( $moderators, 'user_id' );
		$inputs = array();
		
		foreach( $users as $user )
		{
			$input = array(
				'checked' => isset( $moderators[ $user->ID ] ),
				'label' => $user->user_login,
				'name' => $user->ID,
				'nameprefix' => '[users]',
				'type' => 'checkbox',
				'value' => $user->ID,
			);
			$inputs[] = $input;
		}
		
		$input_update = array(
			'type' => 'submit',
			'name' => 'update',
			'value' => $this->_( 'Update' ),
			'css_class' => 'button-primary',
		);

		$rv .= '
			<p>' . $this->_( 'Editing the moderators for the template group <em>%s</em>.' , $group->name ) . '</p>

			<p>' . $this->_( 'Select the users which will moderate all the customizations for the templates placed in this group.' , $group->name ) . '</p>

			' . $form->start() . '
			
			' . $this->display_form_table( $inputs ).'

			<p>
				' . $form->make_input( $input_update ) . '
			</p>
			
			' . $form->stop() . '
		';
		
		echo $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Return a count of how many moderators a template group has.
		
		@param		$id
					ID of template group to count.
		
		@return		An int of moderators for this group.
	**/
	function sd_pdft_count_template_group_moderators( $id )
	{
		$query = "SELECT COUNT(*) as count FROM `".$this->wpdb->base_prefix."sd_pdft_template_group_moderators`
			WHERE `tg_id` = '$id'";
		$result =  $this->query_single( $query );
		return $result[ 'count' ];
	}
	
	/**
		@brief		Delete a template group.
		
		@param		$id
					ID of group to delete.
	**/
	function sd_pdft_delete_template_group( $id )
	{
		$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_templates` SET `tg_id` WHERE `tg_id` = '$id'";
		$this->query( $query );
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_pdft_template_groups` WHERE `id` = '$id'";
		return $this->query( $query );
	}
	
	/**
		@brief		Get the edit URL of a template group.
		
		@param		$id
					ID of template group.
		
		@return		The URL to edit the template group.
	**/
	function sd_pdft_get_template_group_edit_url( $id )
	{
		return add_query_arg( array(
			'id' => $id,
			'page' => 'sd_pdft_template_groups',
			'tab' => 'edit',
		), '' );
	}
	
	/**
		@brief		Return a specific template group.
		
		@param		$id
					ID of group to return.
		
		@return		False, if the group doesn't exist, or an SD_PDF_Template_Customizer_Template_Group object.
	**/
	function sd_pdft_get_template_group( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_pdft_template_groups` g WHERE `id` = '$id'";
		return $this->template_group_row_to_object( $this->query_single( $query ) );
	}
	
	/**
		@brief		Return an array of template group moderators.
		
		@return		The array of template group moderators (users).
	**/
	function sd_pdft_get_template_group_moderators( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_pdft_template_group_moderators` g
			INNER JOIN `".$this->wpdb->base_prefix."users` u
			ON ( `user_id` = `u`.`ID` )
			WHERE `tg_id` = '$id'";
		return $this->query( $query );
	}
	
	/**
		@brief		Get a list of all the template groups.
		
		@return		An array of SD_PDF_Template_Customizer_Template_Group objects.
	**/
	function sd_pdft_get_template_groups()
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_pdft_template_groups` g ORDER BY name";
		$groups = $this->query( $query );
		$rv = array();
		foreach( $groups as $group )
			$rv[ $group[ 'id' ] ] = $this->template_group_row_to_object( $group );
		return $rv;
	}
	
	/**
		@brief		Convenience method to quickly return whether a template group has moderators.
		
		@param		$id
					ID of template group to check.
		
		@return		True, if the template group has moderators.
	**/
	function sd_pdft_is_template_group_moderated( $id )
	{
		if ( $id === null )
			return false;
		return count( $this->sd_pdft_get_template_group_moderators( $id ) ) > 0;
	}
	
	/**
		@brief		Add or update a template group.
		
		If the id is not set, a new object will be inserted into the db.
		
		@param		$template_group
					A SD_PDF_Template_Customizer_Template_Group object to add or update.
	**/
	public function sd_pdft_update_template_group( $template_group )
	{
		if ( $template_group->id === null )
		{
			$query = "INSERT INTO `".$this->wpdb->base_prefix."sd_pdft_template_groups`
				(`name`)
				VALUES
				('". $template_group->name ."')
			";
			$template_group->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_template_groups`
				SET
				`name` = '" . $template_group->name . "'
				WHERE `id` = '" . $template_group->id . "'";
			$this->query( $query );
		}

		return $template_group;
	}
	
	/**
		@brief		Which template groups does this user moderate?
		
		@param		$user_id
					ID of user to look up.
		
		@return		An array of template group IDs that this user moderates.
	**/
	public function sd_pdft_user_moderates( $user_id )
	{
		$query = "SELECT `tg_id` FROM `".$this->wpdb->base_prefix."sd_pdft_template_group_moderators`
			WHERE `user_id` = '$user_id'";
		$results = $this->query( $query );
		$rv = array();
		foreach( $results as $result )
			$rv[ $result[ 'tg_id' ] ] = $result[ 'tg_id' ];
		return $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Convert an SQL row to a template group object.
		
		@param		$row
					SQL row as an array.
		
		@return		A SD_PDF_Template_Customizer_Template_Group object.
	**/
	
	public function template_group_row_to_object( $row )
	{
		if ( $row === false )
			return $row;
		$group = new SD_PDF_Template_Customizer_Template_Group();
		$group->id = $row[ 'id' ];
		$group->name = $row[ 'name' ];
		return $group;
	}
	
}