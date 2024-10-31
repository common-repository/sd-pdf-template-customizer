<?php

class SD_PDF_Template_Customizer_Moderation
	extends SD_PDF_Template_Customizer_Customizations
{
	private $user_moderates = array();
	
	public function __construct( $file )
	{
		parent::__construct( $file );

		add_filter( 'sd_pdft_',	array( $this, 'sd_pdft_' ) );
		add_filter( 'sd_pdft_add_moderation_request',				array( $this, 'sd_pdft_add_moderation_request' ) );
		add_filter( 'sd_pdft_delete_moderation_request',			array( $this, 'sd_pdft_delete_moderation_request' ) );
		add_filter( 'sd_pdft_get_moderation_groups_for_user',		array( $this, 'sd_pdft_get_moderation_groups_for_user' ) );
		add_filter( 'sd_pdft_get_moderation_request',				array( $this, 'sd_pdft_get_moderation_request' ) );
		add_filter( 'sd_pdft_get_moderation_requests_for_groups',	array( $this, 'sd_pdft_get_moderation_requests_for_groups' ) );
		add_filter( 'sd_pdft_send_moderation_request_notice',		array( $this, 'sd_pdft_send_moderation_request_notice' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin methods
	// --------------------------------------------------------------------------------------------
	
	public function admin_menu()
	{
		$submenus = parent::admin_menu( $submenus );
		
		if ( ! $this->admin )
		{
			$this->user_moderates = $this->filters( 'sd_pdft_user_moderates', $this->user_id() );
			if ( count( $this->user_moderates ) < 1 )
				return $submenus;
		}
		else
			$this->user_moderates = array_keys( $this->filters( 'sd_pdft_get_template_groups' ) );

		$submenus[] = array(
			'sd_pdf_template_customizer',
			$this->_('Moderation'),
			$this->_('Moderation'),
			'read',
			'sd_pdf_template_customizer_moderation',
			array( &$this, 'moderation' )
		);
		
		return $submenus;
	}
	
	public function moderation()
	{
		$tab_data = array(
			'default'	=>	'overview',
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data['page_titles']	['overview'] = $this->_( 'Moderation overview' );
		$tab_data['tabs']			['overview'] = $this->_( 'Overview' );
		$tab_data['functions']		['overview'] = 'moderation_overview';
		
		if ( isset( $_GET[ 'tab' ] ) )
		{
			switch( $_GET[ 'tab' ] )
			{
				case 'view':
					$tab_data['page_titles']	['view'] = $this->_( 'View moderation request' );
					$tab_data['tabs']			['view'] = $this->_( 'View' );
					$tab_data['functions']		['view'] = 'view_moderation_request';
				break;
			}
		}
		
		$this->tabs( $tab_data );
	}

	public function moderation_overview()
	{
		$form = $this->form();
		$rv = '';
		$t_body = '';

		$rv .= $form->start();
		
		$group_id = null;
		if ( isset( $_GET[ 'group' ] ) )
		{
			$group = $this->filters( 'sd_pdft_get_template_group', intval( $_GET[ 'group' ] ) );
			if ( $group !== false )
				$group_id = $group->id;
		}
		
		$moderation_requests = $this->filters( 'sd_pdft_get_moderation_requests_for_groups', $this->user_moderates );
		
		foreach( $moderation_requests as $r )
		{
			$r = (object) $r;
			$c = $this->filters( 'sd_pdft_get_customization', $r->c_id );

			// ACTION time.
			$row_actions = array();
			
			$url = add_query_arg( array(
				'tab' => 'view',
				'id' => $r->id
			) );
			$row_actions[] = (object)array(
				'url' => $url,
				'text' => $this->_( 'View' ),
				'title' => $this->_( 'View this customization' ),
			);
			
			$row_action_texts = array();
			foreach( $row_actions as $action )
				$row_action_texts[] = '<a title="' . $action->title . '" href="' . $action->url . '">'. $action->text . '</a>';
			$row_action_texts = implode( '&emsp;<span class="sep">|</span>&emsp;', $row_action_texts );
			
			$preview_url = $this->customization_pdf_link( $c );
			$preview_image = $this->customization_thumbnail_link( $c );
			$preview = sprintf(
				'<a href="%s"><img class="with_border" src="%s" alt="%s" /></a>',
				$preview_url,
				$preview_image,
				$this->_( 'Thumbnail image should be seen here' )
			);

			$info = array();
			
			$user = get_userdata( $c->user_id );
			$info[] = $this->_( 'Created: %s by %s', $r->created, $user->data->user_login );
			
			$info = '<p>' . implode( '</p><p>', $info ) . '</p>';
			
			$first_action = reset( $row_actions );
			if ( $first_action !== false )
				$row_text = '<a title="' . $first_action->title . '" href="'. $first_action->url .'">' . $c->name . '</a>';
			else
				$row_text = $c->name;

			$t_body .= '<tr>
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
		
		$rv .= '
			<table class="widefat">
				<thead>
					<tr>
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
	
	public function view_moderation_request()
	{
		$id = intval( $_GET[ 'id' ] );
		if ( $id < 1 )
			wp_die( $this->_( 'No ID specified.' ) );
		
		$request = $this->filters( 'sd_pdft_get_moderation_request', $id );
		if( $id < 1 )
		{
			echo ( $this->error( $this->_( 'Invalid ID specified.' ) ) );
			return;
		}
		
		$c = $this->filters( 'sd_pdft_get_customization', $request[ 'c_id' ] );
		
		// Is the user allowed to view this request?
		if ( ! $this->admin )
		{
			// Find out if the user has the right to moderate this tg_id.
			$t = $this->filters( 'sd_pdft_get_template', $c->t_id );
			if ( ! in_array( $t->tg_id, $this->user_moderates ) )
			{
				echo ( $this->error( $this->_( 'You have no moderation rights for this request.' ) ) );
				return;
			}
			
		}

		if ( isset( $_POST[ 'accept' ] ) || isset( $_POST[ 'deny' ] ) || isset( $_POST[ 'redo' ] ) )
		{
			$_POST = $this->strip_post_slashes();
			
			$user = get_userdata( $c->user_id );
			$current_user = wp_get_current_user();
			
			$mail_data = array(
				'to' => array( $user->data->user_email => $user->data->user_login ),
				'cc' => array( $current_user->data->user_email => $current_user->data->user_login ),
				'body_html' => '',
			);
			$mail_data[ 'from' ] = $mail_data[ 'cc' ];
			
			if ( isset( $_POST[ 'accept' ] ) )
			{
				$mail_data[ 'subject' ] = $this->_( 'Your customization, %s, was accepted!', $c->name );
				$mail_data[ 'body_html' ] .= wpautop( $this->_( 'Your customization, <em>%s</em>, was accepted. You can now log in and print the customized PDF!', $c->name ) );
				}

			if ( isset( $_POST[ 'redo' ] ) )
			{
				$mail_data[ 'subject' ] = $this->_( 'Your customization, %s, must be edited!', $c->name );
				$mail_data[ 'body_html' ] .= wpautop( $this->_( 'Your customization, <em>%s</em>, was not acceptable and must be edited before being accepted.', $c->name ) );
			}
	
			if ( isset( $_POST[ 'deny' ] ) )
			{
				$mail_data[ 'subject' ] = $this->_( 'Your customization, %s, was denied!', $c->name );
				$mail_data[ 'body_html' ] .= wpautop( $this->_( 'Your customization, <em>%s</em>, was denied and has been removed from the database.', $c->name ) );
			}
	
			$message = trim( $this->check_plain( $_POST[ 'message' ] ) );
			if ( $message != '' )
			{
				$mail_data[ 'body_html' ] .= wpautop( $this->_( 'The moderator left you this message:' ) );
				$mail_data[ 'body_html' ] .= '<blockquote>';
				$mail_data[ 'body_html' ] .= wpautop( $message );
				$mail_data[ 'body_html' ] .= '</blockquote>';
			}
			
			$mail_data = $this->append_email_signature( $mail_data );
			
			$result = $this->send_mail( $mail_data );
			if ( $result === true )
			{
				$this->message( $this->_( 'The message was sent to the user! The moderation request has been deleted and you can now go back to the moderation overview.' ) );
				
				if ( isset( $_POST[ 'accept' ] ) )
				{
					$c->printable = true;
					$this->filters( 'sd_pdft_update_customization', $c );
					$this->filters( 'sd_pdft_delete_moderation_request', $id );
				}

				if ( isset( $_POST[ 'deny' ] ) )
					$this->filters( 'sd_pdft_delete_customization', $c->id );
				
				if ( isset( $_POST[ 'redo' ] ) )
				{
					$c->editable = true;
					$this->filters( 'sd_pdft_update_customization', $c );
					$this->filters( 'sd_pdft_delete_moderation_request', $id );
				}

				return;
			}
			else
				$this->error( $this->_( 'The message could not be sent to the user. Please try again.' ) );
		}
		
		$t = $this->filters( 'sd_pdft_get_template', $c->t_id );
		
		$preview_pdf = $this->customization_pdf_link( $c );
		$preview_image = $this->customization_thumbnail_link( $c, array(
			'jpeg_height' => 800,
			'jpeg_width' => 800,
		) );
		$rv .= sprintf(
			'<div class="sd_pdft_template_preview"><a href="%s"><img class="with_border" alt="" src="%s" /></a></div>',
			$preview_pdf,
			$preview_image
		);
		
		$form = $this->form();
		$inputs = array(
			'accept' => array(
				'type' => 'submit',
				'name' => 'accept',
				'value' => $this->_( 'Accept this customization' ),
				'css_class' => 'button-primary',
			),
			'message' => array(
				'cols' => 40,
				'description' => $this->_( 'If you wish to include a personal message to the user that will be sent along with the notification, use this text box.' ),
				'label' => $this->_( 'Message' ),
				'name' => 'message',
				'rows' => 5,
				'type' => 'textarea',
				'validation' => array( 'empty' => true ),
				'value' => $t->moderation_text,
			),
			'deny' => array(
				'type' => 'submit',
				'name' => 'deny',
				'value' => $this->_( 'Deny this customization' ),
				'css_class' => 'button-secondary',
			),
			'redo' => array(
				'type' => 'submit',
				'name' => 'redo',
				'value' => $this->_( 'Send back to user for editing' ),
				'css_class' => 'button-secondary',
			),
		);
		
		foreach( $inputs as $index => $ignore )
			$form->use_post_value( $inputs[ $index ], $_POST );
		
		$rv .= '
			' . $form->start() . '
			
			' . wpautop( $this->_( 'Decide if the above customization is acceptable and press the appropriate button. An e-mail will be sent to the user containing your choice of action.' ) ) . '
			
			' . $this->display_form_table( array( $inputs[ 'message' ] ) ) . '

			<h3>' . $this->_( 'Accept' ) . '</h3>
			
			<p>
				' . $form->make_input( $inputs[ 'accept' ] ) . '
			</p>

			<h3>' . $this->_( 'Deny' ) . '</h3>
			
			<p>
				' . $form->make_input( $inputs[ 'deny' ] ) . '
			</p>

			<h3>' . $this->_( 'Redo' ) . '</h3>
			
			' . wpautop( $this->_( 'The customization is not acceptable and must be edited before being accepted.' ) ) . '

			<p>
				' . $form->make_input( $inputs[ 'redo' ] ) . '
			</p>

			' . $form->stop() . '
		';
		
		echo $rv;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------
	
	/**
		@brief		Adds a moderation request for a customization.
		
		@param		$customization
					A SD_PDF_Template_Customizer_Customization object.
	**/
	public function sd_pdft_add_moderation_request( $customization )
	{
		$template = $this->filters( 'sd_pdft_get_template', $customization->t_id );
		$tg_id = $template->tg_id;
		
		$query = "INSERT INTO `".$this->wpdb->base_prefix."sd_pdft_moderation_requests`
			( `c_id`, `tg_id` )
			VALUES ( '". $customization->id ."', '$tg_id' )";
		return $this->query_insert_id( $query );
	}
	
	/**
		@brief		Delete a moderation request.
		
		@param		$id
					ID of moderation request to delete.
	**/
	public function sd_pdft_delete_moderation_request( $id )
	{
		$query = "DELETE FROM `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` WHERE `id` = '$id'";
		return $this->query( $query );
	}
	
	/**
		@brief		Returns a single moderation request.
		
		@param		$id
					ID of moderation request.
		
		@return		The table row associated to this id.
	**/
	public function sd_pdft_get_moderation_request( $id )
	{
		$query = "SELECT `m`.* FROM `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` `m`
			WHERE `m`.`id` = '$id'";
		return $this->query_single( $query );
	}
	
	public function sd_pdft_get_moderation_requests_for_groups( $tg_ids )
	{
		$query = "SELECT `c`.*, `m`.* FROM `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` `m`
			INNER JOIN `".$this->wpdb->base_prefix."sd_pdft_customizations` `c`
				ON (`c`.`id` = `m`.`c_id` )
			WHERE `m`.`tg_id` IN ('" . implode( "', '", $tg_ids ) . "')
			ORDER BY `c`.`name`";
		return $this->query( $query );
	}
	
	public function sd_pdft_send_moderation_request_notice( $r )
	{
		$r = (object) $r;
		$c = $this->filters( 'sd_pdft_get_customization', $r->c_id );
		$t = $this->filters( 'sd_pdft_get_template', $c->t_id );
		
		$user = get_userdata( $c->user_id );
				
		$mail_data = array(
			'from' => array( $user->data->user_email => $user->data->user_login ),
			'to' => array(),
			'cc' => array( $user->data->user_email => $user->data->user_login ),
			'subject' => $this->_( 'Copy: Customization, %s, is waiting for approval.', $c->name ),
			'body_html' =>
				wpautop( $this->_( 'This is a copy of the mail sent to the moderator.' ) )
				. wpautop( $this->_( 'User %s has created a new customization, <em>%s</em> from the template <em>%s</em>, that is awaiting moderation. Please log in and review the request.', $user->data->user_login, $c->name, $t->name ) )
		);
		
		// Find the moderators for this template group.
		$moderators = $this->filters( 'sd_pdft_get_template_group_moderators', $t->tg_id );
		foreach( $moderators as $moderator )
			$mail_data[ 'to' ][ $moderator ['user_email'] ] = $moderator ['user_login'];

		$mail_data = $this->append_email_signature( $mail_data );
		
		$result = $this->send_mail( $mail_data );
		return $result;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------
	
}
