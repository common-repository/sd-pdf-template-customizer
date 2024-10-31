<?php

class SD_PDF_Template_Customizer_Templates
	extends SD_PDF_Template_Customizer_Template_Groups
{
	public function __construct( $file )
	{
		parent::__construct( $file );

		add_filter( 'sd_pdft_count_templates',				array( $this, 'sd_pdft_count_templates' ) );
		add_filter( 'sd_pdft_count_templates_in_group',		array( $this, 'sd_pdft_count_templates_in_group' ) );
		add_filter( 'sd_pdft_delete_template',				array( $this, 'sd_pdft_delete_template' ), 1000 );
		add_filter( 'sd_pdft_get_template',					array( $this, 'sd_pdft_get_template' ) );
		add_filter( 'sd_pdft_get_template_edit_url',		array( $this, 'sd_pdft_get_template_edit_url' ) );
		add_filter( 'sd_pdft_get_template_preview_url',		array( $this, 'sd_pdft_get_template_preview_url' ) );
		add_filter( 'sd_pdft_get_templates',				array( $this, 'sd_pdft_get_templates' ) );
		add_filter( 'sd_pdft_get_templates_in_group',		array( $this, 'sd_pdft_get_templates_in_group' ) );
		add_filter( 'sd_pdft_update_template',				array( $this, 'sd_pdft_update_template' ) );
		
		add_filter( 'sd_pdft_create_pdf',							array( $this, 'sd_pdft_create_pdf' ), 5 );
		add_filter( 'sd_pdft_get_template_field_fonts',				array( $this, 'sd_pdft_get_template_field_fonts' ) );
		add_filter( 'sd_pdft_get_template_field_fonts',				array( $this, 'sd_pdft_get_template_field_fonts_sort' ), 1000 );
		add_filter( 'sd_pdft_get_template_field_font_options',		array( $this, 'sd_pdft_get_template_field_font_options' ) );
		
		// Fields
		add_filter( 'sd_pdft_delete_template_field',		array( $this, 'sd_pdft_delete_template_field' ), 1000 );
		add_filter( 'sd_pdft_get_template_field',			array( $this, 'sd_pdft_get_template_field' ) );
		add_filter( 'sd_pdft_get_template_fields',			array( $this, 'sd_pdft_get_template_fields' ) );
		add_filter( 'sd_pdft_update_template_field',		array( $this, 'sd_pdft_update_template_field' ) );

		// Ajax
		add_action('wp_ajax_sd_pdft_templates_ajax_admin',	array( &$this, 'sd_pdft_templates_ajax_admin') );		
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin methods
	// --------------------------------------------------------------------------------------------
	
	public function admin_init()
	{
		parent::admin_init();
		
		if ( isset( $_GET[ 'sd_pdft_generate' ] ) )
		{
			$this->generate( $_GET );
			exit;
		}
	}
	
	public function admin_menu()
	{
		$submenus = parent::admin_menu( $submenus );

		$submenus[] = array(
			'sd_pdf_template_customizer',
			$this->_('Templates'),
			$this->_('Templates'),
			'read',
			'sd_pdf_template_customizer_templates',
			array( &$this, 'templates' )
		);
		
		return $submenus;
	}
	
	public function templates()
	{
		$tab_data = array(
			'default'	=>	'overview',
			'tabs'		=>	array(),
			'functions' =>	array(),
			'valid_get_keys' => array( 'group' ),
		);
				
		$tab_data['page_titles']	['overview'] = $this->_( 'Template overview' );
		$tab_data['tabs']			['overview'] = $this->_( 'Overview' );
		$tab_data['functions']		['overview'] = 'template_overview';
		
		if ( isset( $_GET[ 'tab' ] ) )
		{
			switch( $_GET[ 'tab' ] )
			{
				case 'edit':
					if ( ! $this->admin )
						break;
					$tab_data['page_titles']	['edit'] = $this->_( 'Edit template' );
					$tab_data['tabs']			['edit'] = $this->_( 'Edit' );
					$tab_data['functions']		['edit'] = 'admin_edit_template';
				break;
				case 'edit_field':
					if ( ! $this->admin )
						break;
					$tab_data['page_titles']	['edit_field'] = $this->_( 'Edit template field' );
					$tab_data['tabs']			['edit_field'] = $this->_( 'Edit field' );
					$tab_data['functions']		['edit_field'] = 'admin_edit_template_field';
				break;
				case 'manage_fields':
					if ( ! $this->admin )
						break;
					$tab_data['page_titles']	['manage_fields'] = $this->_( 'Managing template fields' );
					$tab_data['tabs']			['manage_fields'] = $this->_( 'Manage fields' );
					$tab_data['functions']		['manage_fields'] = 'admin_edit_template_fields';
				break;
				case 'preview':
					$tab_data['page_titles']	['preview'] = $this->_( 'Preview template' );
					$tab_data['tabs']			['preview'] = $this->_( 'Preview' );
					$tab_data['functions']		['preview'] = 'preview_template';
				break;
			}
		}
		
		$this->tabs( $tab_data );
	}
	
	public function template_overview()
	{
		$groups = $this->filters( 'sd_pdft_get_template_groups' );
		
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
			'get_key'	=>	'group',
			'display_before_tab_name'	=> '<h3>',
			'display_after_tab_name'	=> '</h3>',
		);
		
		// Add no group
		$count = $this->filters( 'sd_pdft_count_templates_in_group', null );
		if ( $count > 0 )
		{
			$tab_data['count']				['0'] = $count;
			$tab_data['page_titles']		['0'] = $this->_('Showing templates in group:' ) . ' ' . $this->_( 'Uncategorized' );
			$tab_data['tabs']				['0'] = $this->_( 'Uncategorized' );
			$tab_data['functions']			['0'] = 'template_overview_data';
		}
		
		foreach( $groups as $group )
		{
			$count = $this->filters( 'sd_pdft_count_templates_in_group', $group->id );
			$tab_data['count']			[ $group->id ] = $count;
			$tab_data['page_titles']	[ $group->id ] = $this->_('Showing templates in group:' ) . ' ' . $group->name;
			$tab_data['tabs']			[ $group->id ] = $group->name;
			$tab_data['functions']		[ $group->id ] = 'template_overview_data';
		}
		
		$tab_data[ 'default' ] = reset( $groups );
		$tab_data[ 'default' ] = $tab_data[ 'default' ]->id;
		
		if ( ! isset( $_GET[ 'group' ] ) )
			$_GET[ 'group' ] = $tab_data[ 'default' ];
		
		$this->tabs( $tab_data );
	}

	public function template_overview_data()
	{		
		$group_id = null;
		if ( isset( $_GET[ 'group' ] ) )
		{
			$group = $this->filters( 'sd_pdft_get_template_group', intval( $_GET[ 'group' ] ) );
			if ( $group !== false )
				$group_id = $group->id;
			else
				unset( $_GET[ 'group' ] );
		}
		
		if ( $this->admin )
		{
			if ( isset( $_POST['action_submit'] ) && isset( $_POST['ids'] ) )
			{
				if ( $_POST['action'] == 'clone' )
				{
					foreach( $_POST['ids'] as $temp_id => $ignore )
					{
						$template = $this->filters( 'sd_pdft_get_template', $temp_id );
						if ( $template !== false )
						{
							$template->id = null;
							$template->name = $this->_( 'Clone of %s', $template->name );
							$template = $this->filters('sd_pdft_update_template', $template );
							
							// Clone the fields
							$fields = $this->filters( 'sd_pdft_get_template_fields', $temp_id );
							foreach( $fields as $field )
							{
								$field->id = null;
								$field->t_id = $template->id;
								$this->filters( 'sd_pdft_update_template_field', $field );
							}
						}
					}
				}	// clone
				if ( $_POST['action'] == 'delete' )
				{
					foreach( $_POST['ids'] as $temp_id => $ignore )
					{
						$template = $this->filters( 'sd_pdft_get_template', $temp_id );
						if ( $template !== false )
						{
							$this->filters( 'sd_pdft_delete_template', $temp_id );
							$this->message( $this->_( 'Template <em>%s</em> deleted.', $template->name ) );
						}
					}
				}	// delete
			}
			
			if ( isset( $_POST['create'] ) )
			{
				$template = new SD_PDF_Template_Customizer_Template();
				$template->name = $this->_( 'Template created %s' ,$this->now() );
				$template->created = $this->now();
				if ( isset( $_GET[ 'group'] ) )
					$template->tg_id = $_GET[ 'group'];
				$template = $this->filters( 'sd_pdft_update_template', $template );

				$edit_link = $this->filters( 'sd_pdft_get_template_edit_url', $template->id );
				$edit_link = $this->_( '%sEdit the template%s', '<a href="' . $edit_link . '">', '</a>');
					
				$this->message( $this->_( 'Template created! %s', $edit_link ) );
			}
		}
		
		$form = $this->form();
		$rv = '';

		$rv .= $form->start();
		
		$templates = $this->filters( 'sd_pdft_get_templates_in_group', $group_id );
		
		if ( $group_id !== null )
			$moderators = $this->filters( 'sd_pdft_count_template_group_moderators', $group_id );
		
		$t_body = '';
		foreach( $templates as $template )
		{
			if ( ! $this->admin && $template->source_pdf == '' )
				continue;
			
			$input_select = array(
				'type' => 'checkbox',
				'checked' => false,
				'label' => $template->name,
				'name' => $template->id,
				'nameprefix' => '[ids]',
			);
						
			// ACTION time.
			$row_actions = array();
			
			$preview_link = add_query_arg( array(
				'tab' => 'preview',
				'id' => $template->id
			) );
			$row_actions[] = '<a title="' . $this->_('Show a preview of this template') . '" href="' . $preview_link . '">'. $this->_('Preview') . '</a>';
			
			if ( $this->admin )
			{
				$fields_link = add_query_arg( array(
					'tab' => 'manage_fields',
					'id' => $template->id
				) );
				$row_actions[] = '<a href="'.$fields_link.'">'. $this->_('Manage fields') . '</a>';

				$edit_link = add_query_arg( array(
					'tab' => 'edit',
					'id' => $template->id
				) );
				$row_actions[] = '<a href="'.$edit_link.'">'. $this->_('Edit') . '</a>';
			}
			
			$row_actions = implode( '&emsp;<span class="sep">|</span>&emsp;', $row_actions );
			
			$info = array();
			
			$info[] = wpautop( $template->description );
			
			$info = '<p>' . implode( '</p><p>', $info ) . '</p>';
			
			$preview = '';
			if ( $template->source_pdf != null )
			{
				$preview_image = $this->template_thumbnail_link( $template );
				$preview = sprintf(
					'<a href="%s"><img class="with_border" src="%s" alt="%s" /></a>',
					$preview_link,
					$preview_image,
					$this->_( 'Thumbnail image should be seen here' )
				);
			}
			
			$check_column = '';
			if ( $this->admin )
				$check_column = '<th scope="row" class="check-column">' . $form->make_input($input_select) . ' <span class="screen-reader-text">' . $form->make_label($input_select) . '</span></th>';

			$t_body .= '<tr>
				' . $check_column . '
				<td>
					<div>
						<a
						title="' . $this->_('Show a preview of this template') . '"
						href="'. $preview_link .'">' . $template->name . '</a>
					</div>
					<div class="row-actions">' . $row_actions . '</a>
				</td>
				<td>
					' . $preview . '
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
				array( 'value' => 'clone', 'text' => $this->_('Clone') ),
				array( 'value' => 'delete', 'text' => $this->_('Delete') ),
			),
		);
		
		$check_column = '';
		if ( $this->admin )
		{
			$input_action_submit = array(
				'type' => 'submit',
				'name' => 'action_submit',
				'value' => $this->_('Apply'),
				'css_class' => 'button-secondary',
			);
			
			$rv .= '
				<div>
					' . $form->make_label( $input_actions ) . '
					' . $form->make_input( $input_actions ) . '
					' . $form->make_input( $input_action_submit ) . '
				</div>
			';

			$selected = array(
				'type' => 'checkbox',
				'name' => 'check',
			);
			
			$check_column = '<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>';
		}		
		
		$group_name = ( $group_name == '' ? $this->_( 'Uncategorized' ) : $group_name );
		
		if ( count( $moderators ) > 0 )
			$rv .= wpautop( $this->_( 'Customizations created from the templates below require moderation before being converted into PDFs.' ) );
		
		$rv .= '
			<table class="widefat">
				<caption>' . $group_name . '</caption>
				<thead>
					<tr>
						' . $check_column . '
						<th>' . $this->_('Name') . '</th>
						<th>' . $this->_('Thumbnail') . '</th>
						<th>' . $this->_('Info') . '</th>
					</tr>
				</thead>
				<tbody>
					'.$t_body.'
				</tbody>
			</table>
		';

		// Only admins are allowed to create new templates
		if ( $this->admin )
		{
			$rv .= '<h3>' . $this->_( 'Create a new template' ) . '</h3>';
			
			$input_create = array(
				'type' => 'submit',
				'name' => 'create',
				'value' => $this->_( 'Create a new template' ),
				'css_class' => 'button-primary',
			);
			
			$rv .= '<p>' . $form->make_input( $input_create ) . '</p>';
		}
		
		$rv .= $form->stop();
		
		echo $rv;
	}

	public function admin_edit_template()
	{
		$id = intval( $_GET[ 'id' ] );
		$template = $this->filters( 'sd_pdft_get_template', $id );
		
		if ( $template === false )
		{
			$this->error( $this->_( 'Could not find id %s.',	$id ) );
			return;
		}
		
		$form = $this->form();
		
		$inputs = array(
			'name' => array(
				'label' => $this->_( 'Name' ),
				'maxlength' => 200,
				'name' => 'name',
				'size' => 50,
				'type' => 'text',
				'value' => $template->name,
			),
			'description' => array(
				'label' => $this->_( 'Description' ),
				'cols' => 80,
				'name' => 'description',
				'rows' => 5,
				'type' => 'textarea',
				'value' => $template->description,
				'validation' => array( 'empty' => true ),
			),
			'moderation_text' => array(
				'description' => $this->_( 'This text is automatically inserted in all moderation e-mails to the user.' ),
				'label' => $this->_( 'Moderation text' ),
				'cols' => 80,
				'name' => 'moderation_text',
				'rows' => 5,
				'type' => 'textarea',
				'value' => $template->moderation_text,
				'validation' => array( 'empty' => true ),
			),
			'tg_id' => array(
				'label' => $this->_( 'Template group' ),
				'name' => 'tg_id',
				'options' => array( '' => $this->_( 'No template group' ) ),
				'type' => 'select',
				'value' => ' ' . $template->tg_id,		// ' ' to make it a string.
			),
			'source_pdf' => array(
				'label' => $this->_( 'Source PDF' ),
				'name' => 'source_pdf',
				'type' => 'select',
				'value' => ' ' . $template->source_pdf,		// ' ' to make it a string.
			),
		);
		
		// Put the template groups as options.
		$template_groups = $this->filters( 'sd_pdft_get_template_groups' );
		foreach( $template_groups as $template_group )
			$inputs[ 'tg_id' ][ 'options' ][ ' ' . $template_group->id ] = $template_group->name;
		
		// PDF's
		$inputs[ 'source_pdf' ][ 'options' ] = array_merge(
			array( '' => $this->_( 'No source PDF selected' ) ),
			$this->get_pdf_attachments()
		);
		
		foreach( $inputs as $index => $ignore )
			$form->use_post_value( $inputs[ $index ], $_POST );
		
		if ( isset( $_POST['update'] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$_POST = $this->strip_post_slashes();
				$template = $this->filters( 'sd_pdft_get_template', $id );
				foreach( $inputs as $index => $input )
				{
					$value = ( $input[ 'value' ] == '' ? null : $input[ 'value' ] );
					$template->$index = $value;
				}
				
				$this->filters( 'sd_pdft_update_template', $template );

				$this->message( $this->_('The template has been updated!') );
			}
			else
			{
				$this->error( implode('<br />', $result) );
			}
		}
		
		if ( isset( $_POST[ 'update_activity_log' ] ) )
		{
			$preview_url = $this->filters( 'sd_pdft_get_template_preview_url', $template->id );
			$url = sprintf( '<a href="%s">%s</a>', $preview_url, $template->name ); 
			$this->filters( 'threewp_activity_monitor_new_activity', array(
				'activity_id' => 'sdpdft_t_created',
				'activity_strings' => array(
					'' => $this->_( 'New template created: %s', $url )
				) 
			) );
			$this->message( $this->_( 'If you have ThreeWP Activity Monitor installed, the newest item in the log informs users of this template.' ) );
		}
		
		$input_update = array(
			'type' => 'submit',
			'name' => 'update',
			'value' => $this->_( 'Update' ),
			'css_class' => 'button-primary',
		);
		
		$input_update_activity_log = array(
			'type' => 'submit',
			'name' => 'update_activity_log',
			'value' => $this->_( 'Update activity log' ),
			'css_class' => 'button-secondary',
		);

		$rv .= '
			' . $form->start() . '
			
			' . $this->display_form_table( $inputs ) . '

			<p>
				' . $form->make_input( $input_update ) . '
			</p>

			<h3>' . $this->_( 'Activity log' ) . '</h3>
		
			<p>
				' . $this->_( "If ThreeWP Activity Monitor is installed and users are allowed to view each other's customizations, a new log entry can be inserted by using this button." )  . '
			</p>
			
			<p>
				' . $this->_( "This will inform the users visiting the main menu of SD PDF Template Customizer that there is a new template available for use." )  . '
			</p>
			
			<p>
				' . $form->make_input( $input_update_activity_log ) . '
			</p>
			
			' . $form->stop() . '
		';
		
		echo $rv;
	}
	
	public function admin_edit_template_fields()
	{
		$id = intval( $_GET[ 'id' ] );
		$template = $this->filters( 'sd_pdft_get_template', $id );
		
		if ( $template === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.',	$id ) );
			return;
		}
		
		$rv = ''; 
		
		$rv .= wpautop( $this->_( 'Editing fields for template: <em>%s</em>', $template->name ) );
		
		$form = $this->form();
		$rv .= $form->start();

		if ( isset( $_POST['action_submit'] ) && isset( $_POST['ids'] ) )
		{
			if ( $_POST['action'] == 'clone' )
			{
				foreach( $_POST['ids'] as $temp_id => $ignore )
				{
					$field = $this->filters( 'sd_pdft_get_template_field', $temp_id );
					if ( $field !== false )
					{
						$field->id = null;
						$field->name = $this->_( 'Clone of %s', $field->name );
						$this->filters( 'sd_pdft_update_template_field', $field );
					}
				}
			}	// clone
			if ( $_POST['action'] == 'delete' )
			{
				foreach( $_POST['ids'] as $temp_id => $ignore )
				{
					$field = $this->filters( 'sd_pdft_get_template_field', $temp_id );
					if ( $field !== false )
					{
						$this->filters( 'sd_pdft_delete_template_field', $temp_id );
						$this->message( $this->_( 'Template field <em>%s</em> deleted.', $field->name ) );
					}
				}
			}	// delete
		}
		
		if ( isset( $_POST['create'] ) )
		{
			$field = SD_PDF_Template_Customizer_Template_Field::construct( $_POST[ 'create_type' ] );
			$field->name = $this->_( 'Template field created %s', $this->now() );
			$field->t_id = $id;

			$field = $this->filters( 'sd_pdft_update_template_field', $field );
			
			$this->message( $this->_( 'Template field created!' ) );
		}

		$fields = $this->filters( 'sd_pdft_get_template_fields', $id );
		$fonts = $this->filters( 'sd_pdft_get_template_field_fonts' );

		$t_body = '';
		foreach( $fields as $field )
		{
			$input_select = array(
				'type' => 'checkbox',
				'checked' => false,
				'label' => $field->name,
				'name' => $field->id,
				'nameprefix' => '[ids]',
			);
			
			$edit_link = add_query_arg( array(
				'tab' => 'edit_field',
				'id' => $field->id
			) );
			
			// ACTION time.
			$row_actions = array();
			
			$row_actions[] = '<a href="'.$edit_link.'">'. $this->_('Edit') . '</a>';
			
			$row_actions = implode( '&emsp;<span class="sep">|</span>&emsp;', $row_actions );
			
			$info = array();
			
			$info[] = $field->describe();
			
			if ( count( $field->placements ) > 0 )
			{
				$text = '<ul>';
				$placement_info = array();
				foreach( $field->placements as $placement )
					$placement_info[] = $placement->describe();
				$text .= $this->implode_html( '<li>', '</li>', $placement_info ); 
				$text .= '</ul>';
				$info[] = $text;
			}
			
			$info = implode( '</div><div>', $info );
			
			$t_body .= '<tr>
				<th scope="row" class="check-column">' . $form->make_input($input_select) . ' <span class="screen-reader-text">' . $form->make_label($input_select) . '</span></th>
				<td>
					<div>
						<a
						title="' . $this->_('Edit this template field') . '"
						href="'. $edit_link .'">' . $field->name . '</a>
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
				array( 'value' => 'clone', 'text' => $this->_('Clone') ),
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
			<table class="widefat template_fields">
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
		
		$rv .= wpautop( $this->_( 'If javascript is enabled: fields can be reordered by dragging them up and down.' ) );

		$rv .= '<h3>' . $this->_( 'Create a new template field' ) . '</h3>';

		$create_type = array(
			'label' => $this->_( 'Type of field to create' ),
			'name' => 'create_type',
			'options' => array(
				'image' => $this->_( 'Image' ),
				'textarea' => $this->_( 'Text area' ),
				'textfield' => $this->_( 'Text field' ),
			),
			'type' => 'select',
			'value' => 'textfield',
		);
		
		$input_create = array(
			'type' => 'submit',
			'name' => 'create',
			'value' => $this->_( 'Create a new template field' ),
			'css_class' => 'button-primary',
		);
		
		$rv .= '<p>' . $form->make_label( $create_type ) . ' ' . $form->make_input( $create_type ) . '</p>';
		
		$rv .= '<p>' . $form->make_input( $input_create ) . '</p>';

		$rv .= $form->stop();
		
		$rv .= '<h3>' . $this->_( 'Preview' ) . '</h3>';

		$rv .= wpautop( $this->_( 'Click on the preview image below to view the PDF.' ) );

		$preview_pdf = $this->template_pdf_link( $template );
		$preview_image = $this->template_thumbnail_link( $template, array(
			'jpeg_height' => 800,
			'jpeg_width' => 800,
		) );
		$rv .= sprintf(
			'<div class="sd_pdft_template_preview"><a href="%s"><img class="with_border" alt="' . $this->_( 'Thumbnail image should be seen here' ) . '" src="%s" /></a></div>',
			$preview_pdf,
			$preview_image
		);
		
		$random_id = rand( 0, PHP_INT_MAX );
		$rv .= '
			<script type="text/javascript">
				jQuery(document).ready(function($){
					var sd_pdft_templates_'. $random_id .' = new sd_pdft_templates();
					sd_pdft_templates_'. $random_id .'.init_admin(
					{
						"ajaxurl" : "'. admin_url("admin-ajax.php") . '",
						"ajaxnonce" : "' . wp_create_nonce( 'sd_pdft_templates_ajax_admin' ) . '",
						"action" : "sd_pdft_templates_ajax_admin", 
						"template_id" : "'. $template->id . '",
					},
					{}
					);
				});
			</script>
		';
		
		echo $rv;

		wp_enqueue_script(
			'sd_pdft_templates',
			$this->paths[ 'url' ] . '/js/sd_pdft_templates.js',
			array( 'jquery-ui-sortable' )
		);
	}
	
	public function admin_edit_template_field()
	{
		$rv = '';
		$form = $this->form();
		$id = intval( $_GET[ 'id' ] );
		$field = $this->filters( 'sd_pdft_get_template_field', $id );
		
		if ( $field === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.',	$id ) );
			return;
		}
		
		$inputs = $field->add_admin_inputs( array() );
		
		if ( isset( $_POST[ 'update' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$_POST = $this->strip_post_slashes();
				
				$field = $this->filters( 'sd_pdft_get_template_field', $id );
				$field->parse_admin_post( $_POST );
				
				$field->placements = array();
				foreach( $_POST[ 'placements' ] as $index => $post )
				{
					$placement = $field->new_placement();
					$placement->parse_admin_post( $post );
					
					if ( ! $placement->is_valid() )
						continue; 

					$field->placements[] = $placement;
				}
				
				$field = $this->filters( 'sd_pdft_update_template_field', $field );
				$this->message( $this->_('The template field has been updated!') );
				$_POST = array();
				
				// In case the inputs have changed upon updating.
				$inputs = $field->add_admin_inputs( array() );
			}
			else
			{
				$this->error( implode('<br />', $result) );
			}
		}
				
		// What we're editing.
		$template = $this->filters( 'sd_pdft_get_template', $field->t_id ); 
		$rv .= wpautop( $this->_( 'Editing a field from template: <em>%s</em>', $template->name ) );
		
		// Back link
		$fields_url = add_query_arg( array(
			'tab' => 'manage_fields',
			'id' => $template->id,
		) );
		$rv .= wpautop( $this->_( '<a href="%s">Back to the fields overview.</a>', $fields_url ) );
		
		$rv .= $form->start();
		
		foreach( $inputs as $index => $ignore )
		{
			$inputs[ $index ][ 'name' ] = $index;
			//$inputs[ $index ][ 'value' ] = $field->$index;
			$form->use_post_value( $inputs[ $index ], $_POST );
		}
		
		$rv .= $this->display_form_table( $inputs ) ;
		
		$rv .= '<h3>' . $this->_( 'Placements' ) . '</h3>';

		$rv .= wpautop( $this->_( 'A placement is where on the PDF the text is placed. To remove a placement, set the page to zero.' ) );
		
		// And now display the placements:
		// Make sure there are at least 4 empty placements
		if ( count( $field->placements ) % 5 == 0 )
			$field->add_placement();
		while ( count( $field->placements ) % 5 != 0 )
			$field->add_placement();
		$th = array();
		$t_body = array();
		foreach( $field->placements as $index => $placement )
		{
			$nameprefix = "[placements][$index]";
			$placement_inputs = array();
			
			$placement_inputs = $placement->add_inputs( $placement_inputs );

			$th = array();
			$tr = array();
			$tr[] = '<tr>';
			
			$th[ 'id' ] = '<th title="' . $this->_('Placement ID') . '" >ID</th>'; 
			$tr[] = '<th scope="row" id="placement_'.$index.'">
				<div class="screen-reader-text">Placement</div> ' . ($index+1) . '
			</th>';
			foreach( $placement_inputs as $placement_input_index => $placement_input )
			{
				$placement_inputs[ $placement_input_index ][ 'name' ] = $placement_input_index;
				$placement_inputs[ $placement_input_index ][ 'nameprefix' ] = $nameprefix;
				$placement_inputs[ $placement_input_index ][ 'value' ] = $field->placements[ $index ]->$placement_input_index;
				$form->use_post_value( $placement_inputs[ $placement_input_index ], $_POST );
				
				$th[ $placement_input_index ] = '<th id="' . $placement_input_index . '" title="' . $placement_inputs[ $placement_input_index ][ 'description' ] . '" >
					' . $placement_inputs[ $placement_input_index ][ 'label' ] . '
				</th>'; 

				$tr[] = '<td headers="' . $placement_input_index . ' placement_'.$index.'">
					<div class="screen-reader-text">' . $form->make_label( $placement_inputs[ $placement_input_index ] ) . '</div>
					' . $form->make_input( $placement_inputs[ $placement_input_index ] ) . '
				</td>';
			}
			$tr[] = '</tr>';
			$t_body[] = implode( '', $tr );
		}
		$rv .= '
			<table class="widefat">
				<thead>
					<tr>
						' . implode( '', $th ) . '
					</tr>
				</thead>
				<tbody>
						' . implode( '', $t_body ) . '
				</tbody>
			</table>
		';

		$input_update = array(
			'type' => 'submit',
			'name' => 'update',
			'value' => $this->_( 'Update' ),
			'css_class' => 'button-primary',
		);
		
		$rv .= '<p>' . $form->make_input( $input_update ) . '</p>';
		
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function preview_template()
	{
		$id = intval( $_GET[ 'id' ] );
		$template = $this->filters( 'sd_pdft_get_template', $id );
		
		if ( $template === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.',	$id ) );
			return;
		}
		
		if ( isset( $_POST[ 'create' ] ) )
		{
			$c = $this->filters( 'sd_pdft_create_customization', $template );
			$c->user_id = $this->user_id();
			$c = $this->filters( 'sd_pdft_update_customization', $c );
			$edit_link = $this->filters( 'sd_pdft_get_customization_edit_url', $c->id );
			$edit_link = $this->_( '%sEdit the customization%s', '<a href="' . $edit_link . '">', '</a>');
			$this->message( $this->_( 'A customization based on this template has been created! %s.', $edit_link ) );
			
			if ( $c->printable )
			{
				$c_preview_url = $this->filters( 'sd_pdft_get_customization_preview_url', $c->id );
				$t_preview_url = $this->filters( 'sd_pdft_get_template_preview_url', $t->id );
				$t_preview_url = sprintf( '<a href="%s">%s</a>', $t_preview_url, $template->name );
				$this->filters( 'threewp_activity_monitor_new_activity', array(
					'activity_id' => 'sdpdft_c_created',
					'activity_strings' => array(
						'' => $this->_( '<a href="%s">A new customization</a> based on %s was created by %s', $c_preview_url, $t_preview_url, '%user_login%' ) 
					)
				) );
			}
		}
		
		$preview_image = $this->template_thumbnail_link( $template, array(
			'jpeg_height' => 800,
			'jpeg_width' => 800,
		) );
		$rv .= sprintf(
			'<div class="sd_pdft_template_preview"><img class="with_border" alt="' . $this->_( 'Thumbnail image should be seen here' ) . '" src="%s" /></div>',
			$preview_image
		);
		
		$rv .= wpautop( $template->description );
		
		$rv .= wpautop( $this->_( 'The following fields can be modified:' ) );
		
		$rv .= '<ul>';
		$fields = $this->filters( 'sd_pdft_get_template_fields', $id );
		foreach( $fields as $field )
		{
			$rv .= '<li>' . $field->name . '</li>';
		}
		$rv .= '</ul>';
		
		$form = $this->form();
		$input_create = array(
			'type' => 'submit',
			'name' => 'create',
			'value' => $this->_( 'Create a new customization using this template' ),
			'css_class' => 'button-primary',
		);
		
		$rv .= $form->start();
		$rv .= '<p>' . $form->make_input( $input_create ) . '</p>';
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Ajax
	// --------------------------------------------------------------------------------------------
	
	function sd_pdft_templates_ajax_admin()
	{
		if ( ! $this->check_admin_referrer( 'sd_pdft_templates_ajax_admin' ) )
			die();
		
		switch ( $_POST['type'] )
		{
			case 'template_fields_reorder':
				$template_id = intval( $_POST[ 'template_id' ] );
				$template = $this->filters( 'sd_pdft_get_template', $template_id );
				if ( $template === false )
					wp_die( "No template $template_id found!" );

				$items = $_POST['order'];
		
				$order = 1;
				foreach ( $_POST['order'] as $item_id )
				{
					$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_template_fields`
					SET
					`order` = '" . $order . "'
					WHERE `id` = '" . $item_id . "'";
					$this->query( $query );
					$order++;
				}

				echo json_encode( array( 'result' => 'ok' ) );
				break;
		}
		die();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Counts all of the templates in a group.
		
		@return		A count.
	**/
	function sd_pdft_count_templates_in_group( $tg_id )
	{
		$tg_id = ( $tg_id === null ? 'IS NULL' : " = '$tg_id'" );
		
		$query = "SELECT COUNT(*) as count
			FROM `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
			WHERE `tg_id` $tg_id
			ORDER BY `t`.`name`";

		$result =  $this->query_single( $query );
		return $result[ 'count' ];
	}
	
	function sd_pdft_create_pdf()
	{
		// Make the default font one that is not a core font, and therefore one that must be subsetted.
		// print24.com gives an error when using standard, non-embeddable core fonts, so the solution is to not use any core fonts.
		
		if ( !defined( 'PDF_FONT_NAME_MAIN' ) )
			define( 'PDF_FONT_NAME_MAIN', 'freesans' );
		
		if ( ! class_exists( 'TCPDF' ) )
		{
			require_once( dirname( __FILE__ ) . '/tcpdf/config/tcpdf_config.php');
			require_once( dirname( __FILE__ ) . '/tcpdf/tcpdf.php');
		}
		if ( ! class_exists( 'FPDI' ) )
			require_once( dirname( __FILE__ ) . '/fpdi/fpdi.php');

		if ( ! class_exists( 'SD_PDFT_PDF' ) )
			require_once( dirname( __FILE__ ) . '/SD_PDFT_PDF.php');
		
		$pdf = new SD_PDFT_PDF();
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->SetAutoPageBreak( false );
		$pdf->SetMargins( 0, 0, 0, true );

		$pdf->setCellHeightRatio( 1.1 );
		
		return $pdf;
	}
	
	/**
		@brief		Delete a template.
		
		@param		$id
					ID of template to delete.
	**/
	function sd_pdft_delete_template( $id )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_pdft_templates` WHERE `id` = '$id'";
		return $this->query( $query );
	}
	
	/**
		@brief		Delete a template field.
		
		@param		$id
					ID of field to delete.
	**/
	function sd_pdft_delete_template_field( $id )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_pdft_template_fields` WHERE `id` = '$id'";
		return $this->query( $query );
	}
	
	/**
		@brief		Return a specific template group.
		
		@param		$id
					ID of group to return.
		
		@return		False, if the group doesn't exist, or an SD_PDF_Template_Customizer_Template_Group object.
	**/
	function sd_pdft_get_template( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_pdft_templates` g WHERE `id` = '$id'";
		return $this->template_row_to_object( $this->query_single( $query ) );
	}
	
	/**
		@brief		Get the edit URL of a template.
		
		@param		$id
					ID of template.
		
		@return		The URL to edit the template.
	**/
	function sd_pdft_get_template_edit_url( $id )
	{
		return add_query_arg( array(
			'id' => $id,
			'page' => 'sd_pdf_template_customizer_templates',
			'tab' => 'edit',
		), '' );
	}
	
	/**
		@brief		Get the preview URL of a template.
		
		@param		$id
					ID of template.
		
		@return		The URL to preview the template.
	**/
	function sd_pdft_get_template_preview_url( $id )
	{
		return add_query_arg( array(
			'id' => $id,
			'page' => 'sd_pdf_template_customizer_templates',
			'tab' => 'preview',
		), '' );
	}
	
	/**
		@brief		Get a list of all the template groups.
		
		@return		An array of SD_PDF_Template_Customizer_Template objects.
	**/
	function sd_pdft_get_templates()
	{
		$query = "SELECT `t`.*, `g`.`name` AS `g_name`
			FROM `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
			LEFT JOIN `".$this->wpdb->base_prefix."sd_pdft_template_groups` `g`
			ON `t`.`tg_id` = `g`.`id`
			ORDER BY `g`.`name`, `t`.`name`";

		$templates = $this->query( $query );
		
		$rv = array();
		foreach( $templates as $template )
			$rv[ $template[ 'id' ] ] = $this->template_row_to_object( $template );
		return $rv;
	}
	
	/**
		@brief		Get a list of all templates in a template group.
		
		@return		An array of SD_PDF_Template_Customizer_Template objects.
	**/
	function sd_pdft_get_templates_in_group( $tg_id )
	{
		$tg_id = ( $tg_id === null ? 'IS NULL' : " = '$tg_id'" );
		
		$query = "SELECT `t`.*
			FROM `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
			WHERE `tg_id` $tg_id
			ORDER BY `t`.`name`";

		$templates = $this->query( $query );
		
		$rv = array();
		foreach( $templates as $template )
			$rv[ $template[ 'id' ] ] = $this->template_row_to_object( $template );
		return $rv;
	}
	
	/**
		@brief		Retrieve a template field.
		
		@return		A SD_PDF_Template_Customizer_Template_Field object.
	**/
	function sd_pdft_get_template_field( $id )
	{
		$query = "SELECT `f`.*
			FROM `".$this->wpdb->base_prefix."sd_pdft_template_fields` `f`
			WHERE `id` = '$id'";

		$field = $this->query_single( $query );
		
		return $this->template_field_row_to_object( $field );
	}
	
	/**
		@brief		Retrieve the fields of this template.
		
		@return		An array of SD_PDF_Template_Customizer_Template_Field objects.
	**/
	function sd_pdft_get_template_fields( $id )
	{
		$query = "SELECT `f`.*
			FROM `".$this->wpdb->base_prefix."sd_pdft_template_fields` `f`
			WHERE `t_id` = '$id'
			ORDER BY `order`";

		$fields = $this->query( $query );
		
		$rv = array();
		foreach( $fields as $field )
			$rv[ $field[ 'id' ] ] = $this->template_field_row_to_object( $field );
		return $rv;
	}
	
	/**
		@brief		Return all supported TCPDF fonts.

		@param		$fonts
					Array of SD_PDF_Template_Customizer_Template_Field_Font objects.

		@return		An larger array of SD_PDF_Template_Customizer_Template_Field_Font objects.
	**/
	
	function sd_pdft_get_template_field_fonts( $fonts )
	{
		/** courier **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Courier';
		$font->id = 'courier';
		$font->name = 'courier';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Courier Bold';
		$font->id = 'courierb';
		$font->name = 'courier';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Courier Italic';
		$font->id = 'courieri';
		$font->italic = true;
		$font->name = 'courier';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Courier Bold Italic';
		$font->id = 'courierbi';
		$font->italic = true;
		$font->name = 'courier';
		$fonts[ $font->id ] = $font;
		
		/** helvetica **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Helvetica';
		$font->id = 'helvetica';
		$font->name = 'helvetica';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Helvetica Bold';
		$font->id = 'helveticab';
		$font->name = 'helvetica';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Helvetica Italic';
		$font->id = 'helveticai';
		$font->italic = true;
		$font->name = 'helvetica';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Helvetica Bold Italic';
		$font->id = 'helveticabi';
		$font->italic = true;
		$font->name = 'helvetica';
		$fonts[ $font->id ] = $font;
		
		/** symbol **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Symbol';
		$font->id = 'symbol';
		$font->name = 'symbol';
		$fonts[ $font->id ] = $font;
		
		/** times new roman **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Times New Roman';
		$font->id = 'times';
		$font->name = 'times';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Times New Roman Bold';
		$font->id = 'timesb';
		$font->name = 'times';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Times New Roman Italic';
		$font->id = 'timesi';
		$font->italic = true;
		$font->name = 'times';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Times New Roman Bold Italic';
		$font->id = 'timesbi';
		$font->italic = true;
		$font->name = 'times';
		$fonts[ $font->id ] = $font;
		
		/** FreeSans **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Freesans';
		$font->id = 'freesans';
		$font->name = 'freesans';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Freesans Bold';
		$font->id = 'freesansb';
		$font->name = 'freesans';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Freesans Italic';
		$font->id = 'freesansi';
		$font->italic = true;
		$font->name = 'freesans';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Freesans Italic Bold';
		$font->id = 'freesansbi';
		$font->italic = true;
		$font->name = 'freesans';
		$fonts[ $font->id ] = $font;
		
		/** FreeSerif **/
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Freeserif';
		$font->id = 'freeserif';
		$font->name = 'freeserif';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Freeserif Bold';
		$font->id = 'freeserifb';
		$font->name = 'freeserif';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->description = 'Freeserif Italic';
		$font->id = 'freeserifi';
		$font->italic = true;
		$font->name = 'freeserif';
		$fonts[ $font->id ] = $font;
		
		$font = new SD_PDF_Template_Customizer_Template_Field_Font();
		$font->bold = true;
		$font->description = 'Freeserif Italic Bold';
		$font->id = 'freeserifbi';
		$font->italic = true;
		$font->name = 'freeserif';
		$fonts[ $font->id ] = $font;
		
		return $fonts;
	}
	
	/**
		@brief		Sort the fonts before returning them to the user to view. Sorts by description.
		
		@param		$fonts
					Array of fonts.
		
		@return		Sorted array of fonts.
	**/
	function sd_pdft_get_template_field_fonts_sort( $fonts )
	{
		$fonts = $this->array_sort_subarrays( $fonts, 'description' );
		return $fonts;
	}

	/**
	**/
	public function sd_pdft_get_template_field_font_options()
	{
		if ( $this->cache_font_options === null )
		{
			$this->cache_font_options = array();
			$fonts = $this->filters( 'sd_pdft_get_template_field_fonts' );
			foreach( $fonts as $font )
				$this->cache_font_options[ $font->id ] = $font->description;
		}
		return $this->cache_font_options;
	}
	
	/**
		@brief		Add or update a template.
		
		If the id is not set, a new object will be inserted into the db.
		
		@param		$template
					A SD_PDF_Template_Customizer_Template object to add or update.
	**/
	public function sd_pdft_update_template( $template )
	{
		$keys = $template->keys();
		
		if ( $template->id === null )
		{
			$values = array();
			foreach( $keys as $key )
			{
				$values[] = ( $template->$key === null ? 'null' : "'" . mysql_real_escape_string( $template->$key ) . "'" );
			}
			$query = "INSERT INTO `".$this->wpdb->base_prefix."sd_pdft_templates`
				(`" . implode( '`, `', $keys ) . "`)
				VALUES
				(" . implode( ',', $values ) . ")
			";
			$template->id = $this->query_insert_id( $query );
		}
		else
		{
			$sets = array();
			foreach( $keys as $key )
			{
				$value = ( $template->$key === null ? 'null' : "'" . mysql_real_escape_string( $template->$key ) . "'" );
				$sets[] = "`$key` = $value";
			}
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_templates`
				SET
				" . implode( ',', $sets ) . "
				WHERE `id` = '" . $template->id . "'";
			$this->query( $query );
		}

		return $template;
	}

	/**
		@brief		Add or update a template field.
		
		If the id is not set, a new object will be inserted into the db.
		
		@param		$template_field
					A SD_PDF_Template_Customizer_Template_Field object to add or update.
	**/
	public function sd_pdft_update_template_field( $template_field )
	{
		$template_field->serialize();
		if ( $template_field->id === null )
		{
			$query = "INSERT INTO `".$this->wpdb->base_prefix."sd_pdft_template_fields`
				(`id`, `t_id`, `order`, `data`)
				VALUES
				(
					'" . $template_field->id . "',
					'" . $template_field->t_id . "',
					'" . $template_field->order . "',
					'" . $template_field->data . "'
				)
			";
			$template_field->id = $this->query_insert_id( $query );
		}
		else
		{
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_template_fields`
				SET
				`t_id` = '" . $template_field->t_id . "',
				`order` = '" . $template_field->order . "',
				`data` = '" . $template_field->data . "'
				WHERE `id` = '" . $template_field->id . "'";
			$this->query( $query );
		}

		return $template_field;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	public function generate( $options )
	{
		$options = array_merge( array(
			'dpi' => 150,
			'c_id' => null,
			'jpeg_width' => 200,
			'jpeg_height' => '',
			'output' => 'pdf',
			't_id' => false,
		), $options );
		$o = $this->array_to_object( $options );		// $o is shorthand
		
		// Check: does the user even have access to the plugin? 
		if ( ! $this->role_at_least( $this->get_site_option('role_use') ) )
			wp_die( 'No access to SD PDF Template Customizer' );
		
		$view_all = $this->role_at_least( $this->get_site_option( 'role_view_all' ) );

		// Does this template exist?
		$o->t_id = intval( $o->t_id );
		$o->template = $this->filters( 'sd_pdft_get_template', $o->t_id );
		if ( $o->template === false )
			wp_die( 'Template does not exist.' );
		
		// Does the template have a source pdf?
		$o->pdf_source_filename = get_attached_file( $o->template->source_pdf );
		if ( ! is_readable( $o->pdf_source_filename ) )
			wp_die( 'Source PDF is not readable!' );
		
		// Sanitize the output format.
		switch( $o->output )
		{
			case 'jpeg':
			case 'pdf':
				break;
			default:
				$o->output = 'pdf';
				break;
		}
		
		// Sanitize dpi
		$o->dpi = intval( $o->dpi );
		$o->dpi = max( 150, $o->dpi );		// Minimum is 150.
		
		// Higher DPI than 150? Admins are allowed, and maybe everyone. Maybe.
		if ( $o->dpi > 150 && ! $this->admin && ! $view_all )
		{
			// The only time the user is allowed > 150 is when he owns this customization.
			if ( ! $o->c )
				wp_die( 'No access to dpi > 150.' );
		}
		
		// Sanitize jpeg width
		$o->jpeg_height = intval( $o->jpeg_height );
		$o->jpeg_height = max( 0, $o->jpeg_height );		// Minimum is 0
		$o->jpeg_width = intval( $o->jpeg_width );
		$o->jpeg_width = max( 0, $o->jpeg_width );			// Minimum is 0
		if ( ! $this->admin )
		{
			$o->jpeg_height = min( $o->jpeg_height, 800 );
			$o->jpeg_width = min( $o->jpeg_width, 800 );
		}
		if( $o->jpeg_height < 1 )
			$o->jpeg_height = '';
		
		// Did the user request a customization?
		$o->c_id = intval( $o->c_id );
		if ( $o->c_id > 0 )
		{
			$o->customization = $this->filters( 'sd_pdft_get_customization', $o->c_id );

			// Is this user a moderator for this template group?
			$moderators = $this->filters( 'sd_pdft_get_template_group_moderators', $o->template->tg_id );
			$is_moderator = false;
			foreach( $moderators as $moderator )
				if ( $this->user_id() == $moderator[ 'ID' ] )
				{
					$is_moderator = true;
					break;
				}
			if ( ! $this->admin && ! $is_moderator )
				if ( ! $o->customization->printable && $o->output == 'pdf' )
					wp_die( 'Not printable.' );
		}
		
		$o->fields = $this->filters( 'sd_pdft_get_template_fields', $o->template->id );
		
		$hash = hash( 'md5', var_export( $o, true ) );
		$o->pdf_filename = sprintf(
			'%s/sd_pdf_template_customizer_%s_%s_%s_%s.pdf',
			sys_get_temp_dir(),
			date('Y-m'),
			$o->t_id,
			$o->c_id,
			$hash
		);
		
		// Does the PDF exist?
		if ( ! is_readable( $o->pdf_filename ) )
			$this->generate_pdf( $o );
		
		// Convert the PDF to a jpeg?
		if ( $o->output == 'jpeg' )
		{
			$o->jpeg_filename = $o->pdf_filename . '.jpg';
			if ( ! is_readable( $o->jpeg_filename ) )
				$this->generate_jpeg( $o );
			$file_to_download = $o->jpeg_filename;
		}
		else
			$file_to_download = $o->pdf_filename;
		
		$this->load_language();
		
		// Allow the user to download or view the file.
		try
		{
			$download_filename = $o->template->name;
			if ( $o->c_id !== null )
				$download_filename .= ' - ' . $o->customization->name;
			$this->download( $file_to_download, array(
				'filename' => $this->_( '%s.%s', $download_filename, $o->output ),
				'content_disposition' => 'inline',
			) );
		}
		catch( Exception $e )
		{
			wp_die( $e->getMessage() );
		}

		exit;
	}
	
	public function generate_jpeg( $options )
	{
		$o = $options;		// Conv

		$command = sprintf(
			'convert -density %sx%s -quality 70 "%s" "%s"',
			$o->dpi,
			$o->dpi,
			$o->pdf_filename,
			$o->jpeg_filename
		);
		exec( $command );
		
		// Multipage PDF?
		$files = glob( $o->pdf_filename . '*.jpg' );
		
		if ( count ( $files ) == 1 )
		{
			$command = sprintf(
				'convert "%s" -resize %sx%s "%s"',
				$o->jpeg_filename,
				$o->jpeg_width,
				$o->jpeg_height,
				$o->jpeg_filename
			);
			exec( $command );
			return;
		}
		
		// Yepp. Several images were created. Merge them into one.
		$command = sprintf(
			'convert "%s" +append -resize %sx%s "%s"',
			implode( '" "', $files ),
			$o->jpeg_width,
			$o->jpeg_height,
			$o->jpeg_filename
		);
		exec( $command );
		
		// And now remove the separate pages
		foreach( $files as $file )
			unlink( $file );
	}
	
	/**
	**/
	public function generate_pdf( $options )
	{
		$o = $options;		// Conv
		
		$fields = $this->filters( 'sd_pdft_get_template_fields', $o->template->id );
		if ( count( $fields ) < 1 )
			wp_die( 'Template has no fields!' );
		
		$fonts = $this->filters( 'sd_pdft_get_template_field_fonts' );
		
		$pdf = $this->filters( 'sd_pdft_create_pdf' );
		$pages = $pdf->setSourceFile( $o->pdf_source_filename );
		
		for( $counter = 0; $counter < $pages ; $counter++ )
		{
			$current_page = $counter + 1;	// Conv.
			
			$template_index = $pdf->importPage( $current_page, '/MediaBox' );
			$page_size = array_values( $pdf->getTemplateSize( $template_index ) );
			$page_size[0] = round( $page_size[0] );
			$page_size[1] = round( $page_size[1] );
			$orientation = ( $page_size[0] < $page_size[1] ? 'P' : 'L' ); 
			$pdf->addPage($orientation, $page_size );
			$pdf->useTemplate( $template_index );
			
			// Go through all the fields and look for something that wants to be written on this page.
			foreach( $fields as $field )
			{
				$field_name = $field->name;
				
				if ( $o->customization !== null )
					$field->use_customization_data( $o->customization );
				
				// Each field has a bunch of placements.
				foreach( $field->placements as $placement )
				{
					if( $placement->page != $current_page )
						continue;
					$placement->pdf( $field, $pdf );
				}
			}

		}
		
		$pdf->Output( $o->pdf_filename, 'F' );
	}
	
	/**
		@brief Returns a list of all attachments that are PDFs.
		
		Incidentally, the return format is an array that fits in perfectly as an options array in a select...
		
		@return		Array of post->ID => url, containing a list of all attachments that are PDFs.
	**/
	private function get_pdf_attachments()
	{
		$rv = array();
		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_parent' => null ); 
		$attachments = get_posts( $args );
		if ($attachments)
		{
			foreach ( $attachments as $post )
			{
				if ( strpos( $this->strtolower( $post->guid ), '.pdf' ) !== false )
					$rv[ ' ' . $post->ID ] = preg_replace( '/.*\//', '', $post->guid );
			}
		}
		return $rv;
	}

	/**
		@brief		Convert an SQL row to a template object.
		
		@param		$row
					SQL row as an array.
		
		@return		A SD_PDF_Template_Customizer_Template object.
	**/
	public function template_row_to_object( $row )
	{
		if ( $row === false )
			return $row;
		$template = new SD_PDF_Template_Customizer_Template();
		foreach( $template->keys() as $key )
			$template->$key = $row[ $key ];
		
		$extra_keys = array(
			'g_name'
		);
		foreach( $extra_keys as $extra_key )
			if ( isset( $row[ $extra_key ] ) )
				$template->$extra_key = $row [ $extra_key ]; 

		return $template;
	}

	/**
		@brief		Convert an SQL row to a template field object.
		
		@param		$row
					SQL row as an array.
		
		@return		A SD_PDF_Template_Customizer_Template_Field object.
	**/
	public function template_field_row_to_object( $row )
	{
		if ( $row === false )
			return $row;
		
		// These three lines are needed to extract the field type, which is in the serialized data string.
		$field = new SD_PDF_Template_Customizer_Template_Field();
		$field->unserialize( $row[ 'data' ] );
		$type = $field->type;
		
		$field = SD_PDF_Template_Customizer_Template_Field::construct( $type );
		$field->id = $row[ 'id' ];
		$field->order = $row[ 'order' ];
		$field->t_id = $row[ 't_id' ];
		$field->unserialize( $row[ 'data' ] );
		return $field;
	}
	
	public function template_link( $template, $options = array() )
	{
		$o = (object) array_merge( array(
			'customization' => false,
			'jpeg_height' => 200,
			'jpeg_width' => 200,
			'output' => 'pdf',
		), (array) $options );
		
		$args = array(
			'sd_pdft_generate' => true,
			't_id' => $template->id,
		);
		
		if( $o->customization != false )
			$args[ 'c_id' ] = $o->customization->id;
		
		if ( $o->output == 'jpeg' )
		{
			$args[ 'output' ] = $o->output;

			if ( $o->jpeg_height > 0 )
				$args[ 'jpeg_height' ] = $o->jpeg_height;
			if ( $o->jpeg_width > 0 )
				$args[ 'jpeg_width' ] = $o->jpeg_width;
		}
		
		$fields = $this->filters( 'sd_pdft_get_template_fields', $template->id );
		$args[ 'hash' ] = hash( 'md5', serialize( $template ) . serialize( $o->fields ) . serialize( $o->customization ) );
		
		$url = add_query_arg( $args, get_admin_url() );
		return $url;
	}
	
	public function template_thumbnail_link( $template, $options = array() )
	{
		$o = (object) array_merge( array(
			'jpeg_width' => 200,
			'output' => 'jpeg',
		), $options );
		
		return $this->template_link( $template, $o );		
	}

	public function template_pdf_link( $template, $options = array() )
	{
		$o = (object) array_merge( array(
		), $options );
		
		return $this->template_link( $template, $o );		
	}
}
