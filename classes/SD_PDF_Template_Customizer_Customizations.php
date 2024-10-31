<?php

class SD_PDF_Template_Customizer_Customizations
	extends SD_PDF_Template_Customizer_Templates
{
	private $viewing_all = false;
	
	/**
		Directory for the uploaded customization images.
		@var	$images_dir
	**/
	public $images_dir;

	public function __construct( $file )
	{
		parent::__construct( $file );
		
		add_filter( 'sd_pdft_clone_customization',					array( $this, 'sd_pdft_clone_customization' ) );
		add_filter( 'sd_pdft_count_customizations_per_group',		array( $this, 'sd_pdft_count_customizations_per_group' ) );
		add_filter( 'sd_pdft_count_user_customizations_per_group',	array( $this, 'sd_pdft_count_user_customizations_per_group' ) );
		add_filter( 'sd_pdft_create_customization',					array( $this, 'sd_pdft_create_customization' ) );
		add_filter( 'sd_pdft_delete_customization',					array( $this, 'sd_pdft_delete_customization' ) );
		add_filter( 'sd_pdft_get_customization',					array( $this, 'sd_pdft_get_customization' ) );
		add_filter( 'sd_pdft_get_customization_edit_url',			array( $this, 'sd_pdft_get_customization_edit_url' ) );
		add_filter( 'sd_pdft_get_customization_preview_url',		array( $this, 'sd_pdft_get_customization_preview_url' ) );
		add_filter( 'sd_pdft_get_customizations_from_template',		array( $this, 'sd_pdft_get_customizations_from_template' ) );
		add_filter( 'sd_pdft_get_customizations_in_group',			array( $this, 'sd_pdft_get_customizations_in_group' ) );
		add_filter( 'sd_pdft_get_user_customizations_in_group',		array( $this, 'sd_pdft_get_user_customizations_in_group' ), 10, 2 );
		add_filter( 'sd_pdft_update_customization',					array( $this, 'sd_pdft_update_customization' ) );
		
		add_filter( 'sd_pdft_delete_template',						array( $this, 'customizations_sd_pdft_delete_template' ) );

		define( 'SD_PDFT_CUSTOMIZATION_IMAGES_DIR', WP_CONTENT_DIR . '/uploads/sd_pdft/customization_images' );
		define( 'SD_PDFT_CUSTOMIZATION_IMAGES_URL', WP_CONTENT_URL . '/uploads/sd_pdft/customization_images' );
	}
	
	public function activate()
	{
		parent::activate();
		
		mkdir( SD_PDFT_CUSTOMIZATION_IMAGES_DIR, 0777, true );
	}

	public function uninstall()
	{
		parent::uninstall();
		
		$this->rmdir( SD_PDFT_CUSTOMIZATION_IMAGES_DIR );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin methods
	// --------------------------------------------------------------------------------------------
	
	public function admin_menu()
	{
		$submenus = parent::admin_menu( $submenus );

		$submenus[] = array(
			'sd_pdf_template_customizer',
			$this->_('Customizations'),
			$this->_('Customizations'),
			'read',
			'sd_pdf_template_customizer_customizations',
			array( &$this, 'customizations' )
		);
		
		return $submenus;
	}
	
	public function customizations()
	{
		$tab_data = array(
			'default'	=>	'0',
			'functions' =>	array(),
			'get_key'	=>	'view',
			'tabs'		=>	array(),
		);
		
		// Own
		$tab_data['page_titles']		['own'] = $this->_( 'Customizations' );
		$tab_data['tabs']				['own'] = $this->_( 'Customizations' );
		$tab_data['functions']			['own'] = 'customization_overview';
		
		// All
		if ( $this->role_at_least( $this->get_site_option( 'role_view_all' ) ) )
		{
			$tab_data['page_titles']		['all'] = $this->_( "Everybody's customizations" );
			$tab_data['tabs']				['all'] = $this->_( "Everybody's customizations" );
			$tab_data['functions']			['all'] = 'customization_overview';
		}
		
		$this->tabs( $tab_data );
	}
	
	public function customization_overview()
	{
		$tab_data = array(
			'default'	=>	'overview',
			'display_after_tab_name'	=> '</h3>',
			'display_before_tab_name'	=> '<h3>',
			'functions' =>	array(),
			'tabs'		=>	array(),
			'valid_get_keys' => array( 'group', 'view' ),
		);
				
		$tab_data['page_titles']	['overview'] = $this->_( 'Customization overview' );
		$tab_data['tabs']			['overview'] = $this->_( 'Overview' );
		$tab_data['functions']		['overview'] = 'customization_overview_group';
		
		if ( isset( $_GET[ 'tab' ] ) )
		{
			switch( $_GET[ 'tab' ] )
			{
				case 'edit':
					$tab_data['page_titles']	['edit'] = $this->_( 'Edit customization' );
					$tab_data['tabs']			['edit'] = $this->_( 'Edit' );
					$tab_data['functions']		['edit'] = 'edit_customization';
				break;
				case 'preview':
					$tab_data['page_titles']	['preview'] = $this->_( 'Preview customization' );
					$tab_data['tabs']			['preview'] = $this->_( 'Preview' );
					$tab_data['functions']		['preview'] = 'preview_customization';
				break;
			}
		}
		
		$this->tabs( $tab_data );
	}

	public function customization_overview_group()
	{
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
			'get_key'	=>	'group',
			'display_before_tab_name'	=> '<h3>',
			'display_after_tab_name'	=> '</h3>',
			'valid_get_keys' => array( 'group', 'view' ),
		);
		
		$this->viewing_all = ( $_GET[ 'view' ] == 'all' && $this->role_at_least( $this->get_site_option( 'role_view_all' ) ) );
		
		if ( $this->viewing_all )
			$c_groups = $this->filters( 'sd_pdft_count_customizations_per_group' );
		else
			$c_groups = $this->filters( 'sd_pdft_count_user_customizations_per_group', $this->user_id() );
		
		if( count( $c_groups ) < 1 )
		{
			if ( $this->viewing_all )
				echo $this->_( 'No one has created any customizations yet.' );
			else
				echo $this->_( 'You have not created any customizations yet. Browse the templates for a suitable template from which to create a customization.' );
			return;
		}
		
		foreach( $c_groups as $c_group )
		{
			$c_group = (object) $c_group;
			$group_name = $c_group->name == '' ? $this->_( 'Uncategorized' ) : $c_group->name;
			$tab_data['count']			[ $c_group->id ] = $c_group->count;
			$tab_data['page_titles']	[ $c_group->id ] = $this->_('Showing customizations in group:' ) . ' ' . $group_name;
			$tab_data['functions']		[ $c_group->id ] = 'customization_overview_data';
			$tab_data['tabs']			[ $c_group->id ] = $group_name;
		}

		if ( ! isset( $_GET ['group' ] ) )
		{
			$first = reset( $c_groups );
			$_GET[ 'group' ] = $first[ 'id' ];
			$tab_data[ 'default' ] = $_GET[ 'group' ];
		}

		$this->tabs( $tab_data );
	}

	public function customization_overview_data()
	{		
		$group_id = null;
		if ( isset( $_GET[ 'group' ] ) )
		{
			$group = $this->filters( 'sd_pdft_get_template_group', intval( $_GET[ 'group' ] ) );
			if ( $group !== false )
				$group_id = $group->id;
		}
		
		if ( $group_id > 0 )
			$this->customization_editor = $this->is_customization_editor( $group_id );
		
		if ( ( $this->customization_editor || ! $this->viewing_all )
			&& isset( $_POST['action_submit'] )
			&& isset( $_POST['ids'] )
		)
		{
			if ( $_POST['action'] == 'delete' )
			{
				foreach( $_POST['ids'] as $temp_id => $ignore )
				{
					$c = $this->filters( 'sd_pdft_get_customization', $temp_id );
					if ( $c !== false )
						if( $this->admin || $c->user_id == $this->user_id() || $this->customization_editor )
						{
							$this->filters( 'sd_pdft_delete_customization', $temp_id );
							$this->message( $this->_( 'Customization <em>%s</em> deleted.', $c->name ) );
						}
				}
			}	// delete
			if ( $_POST['action'] == 'make_editable' )
			{
				foreach( $_POST['ids'] as $temp_id => $ignore )
				{
					$c = $this->filters( 'sd_pdft_get_customization', $temp_id );
					if ( $c !== false )
						if( $this->admin || $this->customization_editor )
						{
							$c->editable = true;
							$c->printable = false;
							$this->filters( 'sd_pdft_update_customization', $c );
							$this->message( $this->_( 'Customization <em>%s</em> has been made editable.', $c->name ) );
						}
				}
			}	// make_editable

		}
		
		$form = $this->form();
		$rv = '';
		$t_body = '';

		$rv .= $form->start();
		
		if( ! $this->viewing_all )
			$customizations = $this->filters( 'sd_pdft_get_user_customizations_in_group', $this->user_id(), $group_id );
		else
			$customizations = $this->filters( 'sd_pdft_get_customizations_in_group', $group_id );
		
		foreach( $customizations as $c )
		{
			$broken = ( $c->source_pdf == null );

			$input_select = array(
				'type' => 'checkbox',
				'checked' => false,
				'label' => $c->name,
				'name' => $c->id,
				'nameprefix' => '[ids]',
			);
						
			// ACTION time.
			$row_actions = array();
			
			if ( $c->printable && ! $broken )
			{
				$url = $this->customization_pdf_link( $c );
				$row_actions[] = (object)array(
					'url' => $url,
					'text' => $this->_( 'PDF' ),
					'title' => $this->_( 'Download PDF' ),
				);

				$url = $this->filters( 'sd_pdft_get_customization_preview_url', $c->id );
				$row_actions[] = (object)array(
					'url' => $url,
					'text' => $this->_( 'Preview' ),
					'title' => $this->_( 'Preview PDF as image' ),
				);
			}
			
			if ( ( ! $this->viewing_all && $c->editable && ! $broken ) || $this->customization_editor )
			{
				$url = add_query_arg( array(
					'tab' => 'edit',
					'id' => $c->id
				) );
				$row_actions[] = (object)array(
					'url' => $url,
					'text' => $this->_( 'Edit' ),
					'title' => $this->_( 'Edit the customization' ),
				);
			}
			
			$row_action_texts = array();
			foreach( $row_actions as $action )
				$row_action_texts[] = '<a title="' . $action->title . '" href="' . $action->url . '">'. $action->text . '</a>';
			$row_action_texts = implode( '&emsp;<span class="sep">|</span>&emsp;', $row_action_texts );
			
			$info = array();
			
			$template = $this->filters( 'sd_pdft_get_template', $c->t_id );
			$template_url = $this->filters( 'sd_pdft_get_template_preview_url', $c->t_id );
			$template_name_and_url = sprintf( '<a title="%s" href="%s">%s</a>',
				$this->_( 'Preview the original template' ),
				$template_url,
				$template->name
			);
			
			if ( $this->viewing_all )
			{
				$user = get_userdata( $c->user_id );
				$info[] = $this->_( 'Created on <em>%s</em> by <em>%s</em> from the template <em>%s</em>', $c->created, $user->user_login, $template_name_and_url );
			}
			else
				$info[] = $this->_( 'Created on <em>%s</em> from the template <em>%s</em>', $c->created, $template_name_and_url );
			
			if ( ! $c->printable )
			{
				if ( $c->editable )
					$info[] = $this->_( 'This customization has not yet been approved.' );
				else
					$info[] = $this->_( 'A moderation request has been sent.' );
			}
			
			$info = '<p>' . implode( '</p><p>', $info ) . '</p>';
			
			$preview = $this->_( 'This template is currently broken. Please inform the administrator that the source PDF for template %s is missing!', $c->t_id );
			if ( ! $broken )
			{
				$preview_image = $this->customization_thumbnail_link( $c );
				$preview = sprintf(
					'<img class="with_border" src="%s" alt="%s" />',
					$preview_image,
					$this->_( 'Thumbnail image should be seen here' )
				);
			}
			
			$first_action = reset( $row_actions );
			if ( $first_action !== false )
				$row_text = '<a title="' . $first_action->title . '" href="'. $first_action->url .'">' . $c->name . '</a>';
			else
				$row_text = $c->name;
			
			$check_column = '';
			if ( ! $this->viewing_all || $this->customization_editor )
				$check_column = '<th scope="row" class="check-column">' . $form->make_input($input_select) . ' <span class="screen-reader-text">' . $form->make_label($input_select) . '</span></th>';

			$t_body .= '<tr>
				' . $check_column . '
				<td>
					<div>' . $row_text . '</div>
					<div class="row-actions">' . $row_action_texts . '</a>
				</td>
				<td>
					' . $preview . '
				</td>
				<td><div>' . $info . '</div></td>
			</tr>';
		}
		
		$check_column = '';
		if ( ! $this->viewing_all || $this->customization_editor )
		{
			$input_actions = array(
				'type' => 'select',
				'name' => 'action',
				'label' => $this->_('With the selected rows'),
				'options' => array(
					array( 'value' => '', 'text' => $this->_('Do nothing') ),
					array( 'value' => 'delete', 'text' => $this->_('Delete') ),
					array( 'value' => 'make_editable', 'text' => $this->_('Make editable') ),
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
			';
			$check_column = '<th class="check-column">' . $form->make_input( $selected ) . '<span class="screen-reader-text">' . $this->_('Selected') . '</span></th>';
		}
		
		$rv .= '
			<table class="widefat">
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

		$rv .= $form->stop();
		
		echo $rv;
	}

	public function edit_customization()
	{
		$id = intval( $_GET[ 'id' ] );
		$c = $this->filters( 'sd_pdft_get_customization', $id );
		
		if ( $c === false )
		{
			$this->error( $this->_( 'Could not find id %s.',	$id ) );
			return;
		}
		
		// Does this c belong to us?
		if ( ! $this->admin && $c->user_id != $this->user_id() )
		{
			$template = $this->filters( 'sd_pdft_get_template', $c->t_id );
			$group_id = $template->tg_id;
			if ( $group_id > 0 )
				$this->customization_editor = $this->is_customization_editor( $group_id );
			
			if ( ! $this->customization_editor )
			{
				$this->error( $this->_( 'Could not find id %s.',	$id ) );
				return;
			}
		}
		
		// Is the c editable?
		if ( ! $c->editable && ! $this->admin && ! $this->customization_editor )
		{
			$this->error( $this->_( 'Customization #%s is not editable.',	$id ) );
			return;
		}
		
		if ( isset( $_POST[ 'send_for_approval' ] ) )
		{
			$c->editable = false;
			$this->filters( 'sd_pdft_update_customization', $c );
			$r_id = $this->filters( 'sd_pdft_add_moderation_request', $c );
			$r = $this->filters( 'sd_pdft_get_moderation_request', $r_id );
			$result = $this->filters( 'sd_pdft_send_moderation_request_notice', $r );
			$this->message( $this->_('A moderation request has been sent to the moderators. You can now return to the overview.') );
			return;
		}

		$form = $this->form();
		
		$original_inputs = array(
			'name' => array(
				'description' => $this->_( 'A short description to help you remember what this customization is for.' ),
				'label' => $this->_( 'Name' ),
				'maxlength' => 200,
				'name' => 'name',
				'size' => 50,
				'type' => 'text',
				'value' => $c->name,
			),
		);
		
		// This part needs to be done twice.
		// First to get all the inputs required for validation.
		// Second to recreate any changed inputs. The file field, for example, generates different inputs depending on whether it has an associated file or not.
		$inputs = $original_inputs;
		$fields = $this->filters( 'sd_pdft_get_template_fields', $c->t_id );
		foreach( $fields as $field )
		{
			$field->use_customization_data( $c );
			$inputs = $field->add_user_inputs( $inputs );
		}
		
		foreach( $inputs as $index => $ignore )
		{
			if ( ! isset( $inputs[ $index ][ 'name' ] ) )
				$inputs[ $index ][ 'name' ] = $index;
			$form->use_post_value( $inputs[ $index ], $_POST );
		}
		
		if ( isset( $_POST['update'] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$_POST = $this->strip_post_slashes();
				
				$c->name = trim( $_POST[ 'name' ] );
				
				foreach( $fields as $field )
					$field->save_post_to_customization_data( $_POST, $c );
				
				$this->filters( 'sd_pdft_update_customization', $c );

				$this->message( $this->_('The customization has been updated!') );
				unset( $_POST );
			}
			else
			{
				$this->error( implode('<br />', $result) );
			}
		}
		
		// Repeat here.
		$inputs = $original_inputs;
		$fields = $this->filters( 'sd_pdft_get_template_fields', $c->t_id );
		foreach( $fields as $field )
		{
			$field->use_customization_data( $c );
			$inputs = $field->add_user_inputs( $inputs );
		}
		
		$inputs[ 'name' ][ 'value' ] = $c->name;
		foreach( $inputs as $index => $ignore )
		{
			if ( ! isset( $inputs[ $index ][ 'name' ] ) )
				$inputs[ $index ][ 'name' ] = $index;
			$form->use_post_value( $inputs[ $index ], $_POST );
		}

		$input_update = array(
			'type' => 'submit',
			'name' => 'update',
			'value' => $this->_( 'Update' ),
			'css_class' => 'button-primary',
		);

		$preview_url = '';
		$preview_url_end = '';
		if ( $c->printable )
		{
			$preview_url = $this->customization_pdf_link( $c );
			$preview_url = sprintf( '<a href="%s">', $preview_url );
			$preview_url_end = '</a>'; 
		}
		$preview_image = $this->customization_thumbnail_link( $c, array(
			'jpeg_height' => 800,
			'jpeg_width' => 800,
		) );
		$rv .= sprintf(
			'<div class="sd_pdft_template_preview">%s<img alt="%s" class="with_border" src="%s" />%s</div>',
			$preview_url,
			$this->_( 'Loading image...' ),
			$preview_image,
			$preview_url_end
		);
		
		$rv .= '
			' . $form->start() . '
			
			' . $this->display_form_table( $inputs ) . '

			<p>
				' . $form->make_input( $input_update ) . '
			</p>

			' . $form->stop() . '
		';
		
		if ( ! $c->printable )
		{
			$rv .= '<h3>' . $this->_( 'Send for approval' ) . '</h3>';

			$input_request_moderation = array(
				'type' => 'submit',
				'name' => 'send_for_approval',
				'value' => $this->_( 'Finish editing and send moderation request' ),
				'css_class' => 'button-secondary',
			);
	
			$rv .= '
				' . $form->start() . '
				
				' . wpautop( $this->_( 'When you have finished editing and previewing the customization, you can request that a moderator approves it. This will disable any further editing.' ) ) . '
	
				<p>
					' . $form->make_input( $input_request_moderation ) . '
				</p>
	
				' . $form->stop() . '
			';
			
		}
		
		echo $rv;
	}

	public function preview_customization()
	{
		$id = intval( $_GET[ 'id' ] );
		$c = $this->filters( 'sd_pdft_get_customization', $id );
		
		if ( $c === false )
		{
			$this->error( $this->_( 'Error. Could not find id %s.',	$id ) );
			return;
		}
		
		// Does this c belong to us?
		if ( ! $this->admin )
			if ( ($c->user_id != $this->user_id()) && ! $this->role_at_least( $this->get_site_option( 'role_view_all' ) ) )
			{
				$this->error( $this->_( 'Error. Could not preview id %s.',	$id ) );
				return;
			}
		
		if ( $c->printable )
		{
			if ( isset( $_POST[ 'clone' ] ) )
			{
				$new_c = $this->filters( 'sd_pdft_clone_customization', $c );
				$new_c->name = $this->_( 'Clone of %s', $c->name );
				$new_c->user_id = $this->user_id();
				$new_c = $this->filters( 'sd_pdft_update_customization', $new_c );

				$edit_link = $this->filters( 'sd_pdft_get_customization_edit_url', $new_c->id );
				$edit_link = $this->_( '%sEdit the customization%s', '<a href="' . $edit_link . '">', '</a>');
				$this->message( $this->_( 'A clone of this customization has been created! %s.', $edit_link ) );
			}
		}
		
		if ( $this->admin || $c->printable )
			$preview_pdf = $this->customization_pdf_link( $c );
		$preview_image = $this->customization_thumbnail_link( $c, array(
			'jpeg_height' => 800,
			'jpeg_width' => 800,
		) );
		$rv .= sprintf(
			'<div class="sd_pdft_template_preview"><a href="%s"><img alt="%s" class="with_border" src="%s" /></a></div>',
			$preview_pdf,
			$this->_( 'Loading image...' ),
			$preview_image
		);
		
		// Display the settings used to create this customization.
		
		if ( $c->printable )
		{
			$form = $this->form();
			
			$rv .= '<h3>' . $this->_( 'Actions' ) . '</h3>';
			
			$rv .= $form->start();
			
			$actions = array(
				'clone' => array(
					'css_class' => 'button-secondary',
					'description' => $this->_( 'Create a copy of this finished customization and make it your own.' ),
					'name' => 'clone',
					'type' => 'submit',
					'value' => $this->_( 'Clone this customization' ),
				),
			);
			
			foreach( $actions as $action )
				$rv .= $form->make_input( $action ) . wpautop( $action['description'] );

			$rv .= $form->stop();
		}

		$rv .= '<h3>' . $this->_( 'Customization fields' ) . '</h3>';

		$rv .= wpautop( $this->_( 'The settings below were used to create this customization.' ) );

		$fields = $this->filters( 'sd_pdft_get_template_fields', $c->t_id );
		foreach( $fields as $field )
		{
			$field->use_customization_data( $c );
			$inputs = $field->add_user_inputs( $inputs );
		}
		
		foreach( $inputs as $index => $input )
			$inputs[ $index ][ 'readonly' ] = true;
		
		$rv .= $this->display_form_table( $inputs );
				
		echo $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	function sd_pdft_clone_customization( $c )
	{
		// In order to clone this customization we have to retrieve the template first.
		$template = $this->filters( 'sd_pdft_get_template', $c->t_id );
		$new_c = $this->filters( 'sd_pdft_create_customization', $template );
		
		$fields = $this->filters( 'sd_pdft_get_template_fields', $template->id );
		
		foreach( $fields as $field )
			$field->clone_customization_data( $c, $new_c );
		
		return $new_c;
	}
	
	function sd_pdft_count_customizations_per_group()
	{
		$query = "SELECT COUNT( * ) as count, `g`.`name`, `g`.`id`
			FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
			INNER JOIN `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
				ON (`c`.`t_id` = `t`.`id` )
			LEFT JOIN `".$this->wpdb->base_prefix."sd_pdft_template_groups` `g`
				ON (`t`.`tg_id` = `g`.`id` )
			GROUP BY `g`.`id`
			ORDER BY `g`.`name`";
		
		return $this->query( $query );
	}
	
	function sd_pdft_count_user_customizations_per_group( $user_id )
	{
		$query = "SELECT COUNT( * ) as count, `g`.`name`, `g`.`id`
			FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
			INNER JOIN `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
				ON (`c`.`t_id` = `t`.`id` )
			LEFT JOIN `".$this->wpdb->base_prefix."sd_pdft_template_groups` `g`
				ON (`t`.`tg_id` = `g`.`id` )
			WHERE `c`.`user_id` = '$user_id'
			GROUP BY `g`.`id`
			ORDER BY `g`.`name`";

		return $this->query( $query );
	}
	
	/**
		@brief		Create a customization object with basic setting up.
		
		@param		$template
					Template from which to create the customization.
		
		@return		The customization object.
	**/
	
	function sd_pdft_create_customization( $template )
	{
		$c = new SD_PDF_Template_Customizer_Customization();
		$c->created = $this->now();
		$c->editable = 1;
		$c->name = $this->_( 'Customization created %s', $this->now() );
		// If the group IS moderated, then the customization ISN'T printable.
		$c->printable = ! $this->filters( 'sd_pdft_is_template_group_moderated', $template->tg_id );
		$c->t_id = $template->id;
		return $c;
	}
	
	/**
		@brief		Delete a customization.
		
		@param		$id
					ID of customization to delete.
	**/
	function sd_pdft_delete_customization( $id )
	{
		$c = $this->filters( 'sd_pdft_get_customization', $id );
		$t = $this->filters( 'sd_pdft_get_template', $c->t_id );
		$fields = $this->filters( 'sd_pdft_get_template_fields', $c->t_id );
		foreach( $fields as $field )
		{
			$field->use_customization_data( $c );
			$field->delete();
		}

		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` WHERE `id` = '$id'";
		return $this->query( $query );
	}
	
	function customizations_sd_pdft_delete_template( $t_id )
	{
		// Find all the customizations that use this template.
		$customizations = $this->filters( 'sd_pdft_get_customizations_from_template', $t_id );
		foreach( $customizations as $c )
			$this->filters( 'sd_pdft_delete_customization', $c->id );

		return $t_id;
	}
	
	
	/**
		@brief		Return a specific customization.
		
		@param		$id
					ID of customization to return.
		
		@return		False, if the customization doesn't exist, or an SD_PDF_Template_Customizer_Customization object.
	**/
	function sd_pdft_get_customization( $id )
	{
		$query = "SELECT * FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` WHERE `id` = '$id'";
		return $this->customization_row_to_object( $this->query_single( $query ) );
	}
	
	/**
		@brief		Returns the link to edit the customization.
		
		@param		$id
					ID of customization base url on.
		
		@return		The complete link to the editing window of this customization.
	**/
	function sd_pdft_get_customization_edit_url( $id )
	{
		$url = add_query_arg( array(
			'id' => $id,
			'page' => 'sd_pdf_template_customizer_customizations',
			'tab' => 'edit',
		), '' );
		return $url; 
	}
	
	/**
		@brief		Returns the link to preview the customization.
		
		@param		$id
					ID of customization base url on.
		
		@return		The complete link to the preview of this customization.
	**/
	function sd_pdft_get_customization_preview_url( $id )
	{
		$url = add_query_arg( array(
			'id' => $id,
			'page' => 'sd_pdf_template_customizer_customizations',
			'tab' => 'preview',
		), '' );
		return $url; 
	}
	
	function sd_pdft_get_customizations_from_template( $t_id )
	{
		$query = "
			SELECT `c`.*
			FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
			WHERE `t_id` = '$t_id'
		";
		
		$customizations = $this->query( $query );
		foreach( $customizations as $index => $customization )
			$customizations[ $index ] = $this->customization_row_to_object( $customization );

		return $customizations;
	}
	
	function sd_pdft_get_customizations_in_group( $tg_id )
	{
		$tg_id = ( $tg_id === null ? 'IS NULL' : " = '$tg_id'" );

		$query = "
			SELECT
				`t`.*,
				`c`.*,
				`m`.`c_id` AS needs_moderation
			FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
			INNER JOIN `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
				ON (`c`.`t_id` = `t`.`id` )
			LEFT JOIN `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` `m`
				ON (`c`.`id` = `m`.`c_id` )
			WHERE `t`.`tg_id` $tg_id
			ORDER BY `c`.`name`";
		
		$customizations = $this->query( $query );
		foreach( $customizations as $index => $customization )
		{
			// Convert the row to a customization. 
			$customizations[ $index ] = $this->customization_row_to_object( $customization );
			// Put the rest of the row (for the template and moderation columns) into the same object.
			foreach( (array)$customization as $key => $value )
				$customizations[ $index ]->$key = $value;
		}

		return $customizations;
	}
	
	function sd_pdft_get_user_customizations_in_group( $user_id, $tg_id )
	{
		$tg_id = ( $tg_id === null ? 'IS NULL' : " = '$tg_id'" );

		$query = "
			SELECT
				`t`.*,
				`c`.*,
				`m`.`c_id` AS needs_moderation
			FROM `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
			INNER JOIN `".$this->wpdb->base_prefix."sd_pdft_templates` `t`
				ON (`c`.`t_id` = `t`.`id` )
			LEFT JOIN `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` `m`
				ON (`c`.`id` = `m`.`c_id` )
			WHERE `c`.`user_id` = '$user_id'
			AND `t`.`tg_id` $tg_id
			ORDER BY `c`.`name`";
		
		$customizations = $this->query( $query );
		foreach( $customizations as $index => $customization )
		{
			// Convert the row to a customization. 
			$customizations[ $index ] = $this->customization_row_to_object( $customization );
			// Put the rest of the row (for the template and moderation columns) into the same object.
			foreach( (array)$customization as $key => $value )
				$customizations[ $index ]->$key = $value;
		}

		return $customizations;
	}
	
	/**
		@brief		Add or update a customization.
		
		If the id is not set, a new object will be inserted into the db.
		
		@param		$customization
					A SD_PDF_Template_Customizer_Customization object to add or update.
	**/
	public function sd_pdft_update_customization( $customization )
	{
		$customization->serialize();
		
		$keys = $customization->keys();
		
		if ( $customization->id === null )
		{
			$values = array();
			foreach( $keys as $key )
			{
				$values[] = ( $customization->$key === null ? 'null' : "'" . mysql_real_escape_string( $customization->$key ) . "'" );
			}
			$query = "INSERT INTO `".$this->wpdb->base_prefix."sd_pdft_customizations`
				(`" . implode( '`, `', $keys ) . "`)
				VALUES
				(" . implode( ',', $values ) . ")
			";
			$customization->id = $this->query_insert_id( $query );
		}
		else
		{
			$sets = array();
			foreach( $keys as $key )
			{
				$value = ( $customization->$key === null ? 'null' : "'" . mysql_real_escape_string( $customization->$key ) . "'" );
				$sets[] = "`$key` = $value";
			}
			$query = "UPDATE `".$this->wpdb->base_prefix."sd_pdft_customizations`
				SET
				" . implode( ',', $sets ) . "
				WHERE `id` = '" . $customization->id . "'";
			$this->query( $query );
		}

		$customization->unserialize();

		return $customization;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Convert an SQL row to a customization object.
		
		@param		$row
					SQL row as an array.
		
		@return		A SD_PDF_Template_Customizer_Customization object.
	**/
	public function customization_row_to_object( $row )
	{
		if ( $row === false )
			return $row;
		$customization = new SD_PDF_Template_Customizer_Customization();
		foreach( $customization->keys() as $key )
			$customization->$key = $row[ $key ];
		
		$customization->unserialize();

		return $customization;
	}
	
	public function customization_pdf_link( $customization, $options = array() )
	{
		$o = (object) array_merge( array(
			'customization' => $customization,
		), $options );
		
		$template = $this->filters( 'sd_pdft_get_template', $customization->t_id );
		
		return $this->template_link( $template, $o );		
	}

	public function customization_thumbnail_link( $customization, $options = array() )
	{
		$o = (object) array_merge( array(
			'customization' => $customization,
			'jpeg_width' => 200,
			'output' => 'jpeg',
		), $options );
		
		$template = $this->filters( 'sd_pdft_get_template', $customization->t_id );
		
		return $this->template_link( $template, $o );		
	}
	
	public function is_customization_editor( $group_id )
	{
		if ( $this->admin )
			return true;

		// Is this person a moderator for this group?
		$groups = $this->filters( 'sd_pdft_user_moderates', $this->user_id() );
		return isset( $groups[ $group_id ] );
	}
}
