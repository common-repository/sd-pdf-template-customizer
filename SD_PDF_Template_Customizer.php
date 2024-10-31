<?php
/*                                                                                                                                                                                                                                                             
Plugin Name: SD PDF Template Customizer
Plugin URI: https://it.sverigedemokraterna.se/program/anpassade-pdf-mallar/
Description: Allow users to customize existing PDF templates with their own data.
Version: 1.1
Author: Sverigedemokraterna IT
Author URI: https://it.sverigedemokraterna.se
Author Email: it@sverigedemokraterna.se
License: GPLv3
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

require_once('SD_PDF_Template_Customizer_Base.php');
require_once('classes/SD_PDF_Template_Customizer_0.php');
require_once('classes/SD_PDF_Template_Customizer_Template_Groups.php');
require_once('classes/SD_PDF_Template_Customizer_Templates.php');
require_once('classes/SD_PDF_Template_Customizer_Customizations.php');
require_once('classes/SD_PDF_Template_Customizer_Moderation.php');

require_once('classes/Customization.php');
require_once('classes/Template.php');
require_once('classes/Template_Field.php');
require_once('classes/Template_Group.php');

class SD_PDF_Template_Customizer
	extends SD_PDF_Template_Customizer_Moderation
{
	protected $site_options = array(
		'database_version' => 100,												// Version of database. Starts at version 1.00
		'email_signature' => 'Sent from SD PDF Template Customizer',			// E-mail signature, if any.
		'role_use' => 'administrator',											// Role needed to see / use the customizer
		'role_view_all' => 'administrator',										// Role needed to see customizations of other users
	);
	
	protected $admin;								// Conv: is this user the admin?

	public function __construct()
	{
		parent::__construct( __FILE__ );
		
		add_action( 'admin_menu', array(&$this, 'admin_menu') );
		add_action( 'network_admin_menu', array(&$this, 'network_admin_menu') );
		
		add_filter( 'sd_pdft_get_all_customizations', array( $this, 'sd_pdft_get_all_customizations' ) );
		add_filter( 'sd_pdft_get_user_customizations', array( $this, 'sd_pdft_get_user_customizations' ) );
		add_filter( 'sd_pdft_get_template_groups', array( $this, 'sd_pdft_get_template_groups' ) );
		add_filter( 'sd_pdft_get_templates', array( $this, 'sd_pdft_get_templates' ) );
		
		add_filter( 'sd_pdft_overview_info', array( $this, 'sd_pdft_overview_info_main' ) );
		add_filter( 'threewp_activity_monitor_list_activities', array(&$this, 'threewp_activity_monitor_list_activities') );		
	}
	
	public function activate()
	{
		parent::activate();

		$this->query('SET FOREIGN_KEY_CHECKS=0;');
		$this->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
		$this->query('SET AUTOCOMMIT=0;');
		$this->query('START TRANSACTION;');
		$this->query('SET time_zone = "+00:00";');

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_customizations` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
		  `t_id` int(11) NOT NULL COMMENT 'ID of template used',
		  `user_id` int(11) NOT NULL COMMENT 'User that created this customization',
		  `created` datetime NOT NULL COMMENT 'When the customization was created',
		  `name` text NOT NULL COMMENT 'Name of customization',
		  `editable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'C may be edited',
		  `printable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'May be printed by the user',
		  `data` text COMMENT 'Field data',
		  PRIMARY KEY (`id`),
		  KEY `user_id` (`user_id`),
		  KEY `printable` (`printable`),
		  KEY `editable` (`editable`),
		  KEY `t_id` (`t_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Customizations created by the users' ;
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_moderation_requests` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
		  `c_id` int(11) NOT NULL COMMENT 'Customization ID',
		  `tg_id` int(11) NOT NULL COMMENT 'Template group ID',
		  PRIMARY KEY (`id`),
		  KEY `c_id` (`c_id`,`tg_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Which customizations have requested moderation';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_templates` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
		  `tg_id` int(11) DEFAULT NULL COMMENT 'Template group',
		  `name` text CHARACTER SET latin1 NOT NULL COMMENT 'Name of the template',
		  `created` datetime NOT NULL COMMENT 'When the template was created',
		  `source_pdf` int(11) DEFAULT NULL COMMENT 'ID of PDF template attachment',
		  `description` text COMMENT 'Description of this template',
		  PRIMARY KEY (`id`),
		  KEY `tgid` (`tg_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Templates';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_template_fields` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
		  `t_id` int(11) NOT NULL COMMENT 'Template this field belongs to',
		  `order` int(11) NOT NULL DEFAULT '0' COMMENT 'Order of template field in the template',
		  `data` longtext NOT NULL COMMENT 'Serialized data of field',
		  PRIMARY KEY (`id`),
		  KEY `t_id` (`t_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Fields of the template';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_template_groups` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Template group ID',
		  `name` text CHARACTER SET latin1 NOT NULL COMMENT 'Template group name',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Which template groups exist';
		");

		$this->query("CREATE TABLE IF NOT EXISTS `".$this->wpdb->base_prefix."sd_pdft_template_group_moderators` (
		  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Row ID',
		  `tg_id` int(11) NOT NULL COMMENT 'Template group ID',
		  `user_id` int(11) NOT NULL COMMENT 'User ID',
		  PRIMARY KEY (`id`),
		  KEY `tgid` (`tg_id`,`user_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Which users moderate which template groups';
		");

		$this->query("ALTER TABLE `".$this->wpdb->base_prefix."sd_pdft_customizations`
			ADD CONSTRAINT `wp_sd_pdft_customizations_ibfk_1` FOREIGN KEY (`t_id`) REFERENCES `wp_sd_pdft_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");
		$this->query("ALTER TABLE `".$this->wpdb->base_prefix."sd_pdft_templates`
			ADD CONSTRAINT `wp_sd_pdft_templates_ibfk_1` FOREIGN KEY (`tg_id`) REFERENCES `wp_sd_pdft_template_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");
		$this->query("ALTER TABLE `".$this->wpdb->base_prefix."sd_pdft_template_fields`
			ADD CONSTRAINT `wp_sd_pdft_template_fields_ibfk_1` FOREIGN KEY (`t_id`) REFERENCES `wp_sd_pdft_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");
		$this->query("ALTER TABLE `".$this->wpdb->base_prefix."sd_pdft_template_group_moderators`
			ADD CONSTRAINT `wp_sd_pdft_template_group_moderators_ibfk_1` FOREIGN KEY (`tg_id`) REFERENCES `wp_sd_pdft_template_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");

		$this->query('SET FOREIGN_KEY_CHECKS=1;');
		$this->query('COMMIT;');
		

		if ($this->get_site_option( 'database_version') < 110 )
		{
			// Version 1.1 gets a moderation_text
			$this->query("ALTER TABLE `".$this->wpdb->base_prefix."sd_pdft_templates` ADD `moderation_text` TEXT NULL DEFAULT NULL COMMENT 'Optional moderation e-mail text' AFTER `description` ;");
			// And remove annots is not used anymore.
			$this->delete_site_option( 'pdf_remove_annots' );
			$this->update_site_option( 'database_version', 110 );
		}
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin methods
	// --------------------------------------------------------------------------------------------

	public function wp_head()
	{
		echo '
			<style>
				img.with_border
				{
					border-width: thin;
					border-style: solid;
					border-color: #dddddd;
					padding: 1px;
				}
				
				.sd_pdft_template_preview
				{
					text-align: center;
				}
			</style>
		';
	}
	
	public function admin_menu()
	{
		if ( ! $this->role_at_least( $this->get_site_option('role_use') ) )
			return;
			
		add_action( 'admin_head', array( $this, 'wp_head' ) );

		$this->load_language();
		
		$this->admin = $this->role_at_least( 'administrator' );

		add_menu_page(
			$this->_('PDF Template Customizer'),
			$this->_('PDF Template Customizer'),
			'read',
			'sd_pdf_template_customizer',
			array( &$this, 'overview' ),
			null
		);
		
		$submenus = array();
		
		$submenus = parent::admin_menu( $submenus );
		
		if ( $this->admin )
		{
			$submenus[] = array(
				'sd_pdf_template_customizer',
				$this->_('Admin'),
				$this->_('Admin'),
				'read',
				'sd_pdft_admin',
				array( &$this, 'admin' )
			);
		}

		// Submenus are all done. Sort them.
		$submenus_to_add = array();
		foreach( $submenus as $submenu )
			$submenus_to_add[ $submenu[1] ] = $submenu;
		ksort( $submenus_to_add );
		foreach( $submenus_to_add as $submenu )
			add_submenu_page( $submenu[0], $submenu[1], $submenu[2], $submenu[3], $submenu[4], $submenu[5] );
	}
	
	public function admin()
	{
		$tab_data = array(
			'tabs'		=>	array(),
			'functions' =>	array(),
		);
				
		$tab_data['default'] = 'own';

		$tab_data['page_titles']	['general'] = $this->_( 'General settings' );
		$tab_data['tabs']			['general'] = $this->_( 'General' );
		$tab_data['functions']		['general'] = 'admin_general';
		
		$tab_data['page_titles']	['fonts'] = $this->_( 'Fonts' );
		$tab_data['tabs']			['fonts'] = $this->_( 'Fonts' );
		$tab_data['functions']		['fonts'] = 'admin_fonts';
		
		$tab_data['tabs']			['uninstall'] = $this->_( 'Uninstall' );
		$tab_data['functions']		['uninstall'] = 'admin_uninstall';
		
		$this->tabs( $tab_data );
	}
	
	public function admin_fonts()
	{
		if ( isset( $_POST[ 'generate' ] ) )
		{
			$path = dirname( __FILE__ ) . '/classes/font_import';
			$fonts_to_import = glob( $path . '/*' );
			$pdf = $this->filters( 'sd_pdft_create_pdf' );
			foreach( $fonts_to_import as $font )
			{
				$fontname = $pdf->addTTFfont( $font, 'TrueTypeUnicode', '', 32, $path . '/' );
				$this->message( $this->_( 'Imported font %s!', $fontname ) );
			}
			$this->message( $this->_( 'All fonts in the %s directory have been imported.', 'classes/font_import' ) );
		}
		
		$rv = '';
		$form = $this->form();
		
		$inputs = array(
			'generate' => array(
				'css_class' => 'button-primary',
				'name' => 'generate',
				'type' => 'submit',
				'value' => $this->_( 'Import fonts' ),
			),
		);

		$rv .= $form->start();
		
		$rv .= wpautop( $this->_( 'Fonts can be imported / converted by placing them in the <em>classes/font_import</em> directory.' ) );
		
		$rv .= wpautop( $form->make_input( $inputs[ 'generate' ] ) );
		
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	public function admin_general()
	{
		$rv = '';
		$form = $this->form();
		
		$inputs = array(
			'email_signature' => array(
				'cols' => 60,
				'description' => $this->_( 'This is the signature text that is appended to all outgoing e-mails.' ),
				'label' => $this->_( 'E-mail signature' ),
				'name' => 'email_signature',
				'type' => 'textarea',
				'rows' => 5,
				'setting' => true,
				'validation' => array( 'empty' => true ),
			),
			'role_use' => array(
				'description' => $this->_( 'User level needed to access the plugin.' ),
				'label' => $this->_( 'See the plugin' ),
				'name' => 'role_use',
				'options' => $this->roles_as_options(),
				'setting' => true,
				'type' => 'select',
			),
			'role_view_all' => array(
				'description' => $this->_( "User level needed to see all customizations, not just the user's own." ),
				'label' => $this->_( 'See all customizations' ),
				'name' => 'role_view_all',
				'options' => $this->roles_as_options(),
				'setting' => true,
				'type' => 'select',
			),
			'save' => array(
				'css_class' => 'button-primary',
				'name' => 'save',
				'type' => 'submit',
				'value' => $this->_( 'Save settings' ),
			),
		);
		
		if ( isset( $_POST[ 'save' ] ) )
		{
			$result = $form->validate_post( $inputs, array_keys( $inputs ), $_POST );

			if ($result === true)
			{
				$this->update_site_option( 'role_use', $this->check_plain( $_POST[ 'role_use' ] ) ); 
				$this->update_site_option( 'role_view_all', $this->check_plain( $_POST[ 'role_view_all' ] ) ); 
				$this->update_site_option( 'email_signature', $this->check_plain( $_POST[ 'email_signature' ] ) ); 
				$this->message( $this->_( 'The settings have been updated.' ) );
			}
			else
				$this->error( implode('<br />', $result) );
		}
		
		foreach( $inputs as $index => $input )
		{
			if ( ! isset( $input[ 'setting' ] ) )
				continue;
			$inputs[ $index ][ 'value' ] = $this->get_site_option( $index );
			$form->use_post_value( $inputs[ $index ] );
		}
		
		$rv .= $form->start();
		
		$rv .= '<h3>' . $this->_( 'Access roles' ) . '</h3>';
		$rv .= $this->display_form_table( array(
			$inputs[ 'role_use' ],
			$inputs[ 'role_view_all' ],
		) );
		
		$rv .= '<h3>' . $this->_( 'E-mail' ) . '</h3>';
		$rv .= $this->display_form_table( array(
			$inputs[ 'email_signature' ],
		) );
		
		$rv .= wpautop( $form->make_input( $inputs[ 'save' ] ) );
		
		$rv .= $form->stop();
		
		echo $rv;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- User methods
	// --------------------------------------------------------------------------------------------
	
	public function overview()
	{
		// Collect a list of things to display to the user.
		$infos = $this->filters( 'sd_pdft_overview_info' );
		
		$this->wrap( $this->_( 'Overview' ), implode( '</div><div>', $infos ) );
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Filters
	// --------------------------------------------------------------------------------------------

	public function threewp_activity_monitor_list_activities($activities)
	{
		$this->load_language();
		
		$this->activities = array(
			'sdpdft_c_created' => array(
				'name' => $this->_('A new customization was created.'),
			),
			'sdpdft_t_created' => array(
				'name' => $this->_('A new template was created.'),
			),
		);
		
		foreach( $this->activities as $index => $activity )
		{
			$activity['plugin'] = 'SD PDF Template Customizer';
			$activities[ $index ] = $activity;
		} 
		
		return $activities;
	}

	public function sd_pdft_overview_info_main( $infos )
	{
		if ( ! $this->role_at_least( $this->get_site_option( 'role_view_all' ) ) )
			return;
		
		$activities = array(
			'sdpdft_c_created',
			'sdpdft_t_created',
		);
		$options = array(
			'count' => true,
			'where' => array( "activity_id IN ('" . implode( "', '", $activities ) . "')" ),
		);
		
		$max = $this->filters( 'threewp_activity_monitor_find_activities', $options );
		
		if ( is_array( $max ) )
		{
			$infos[] = $this->_( 'If ThreeWP Activity Monitor was installed a list of template activity could be displayed.' );
			return $infos;
		}
		
		$per_page = 10;
		
		$max_pages = floor( $max / $per_page);
		$page = (isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0 );
		$page = $this->minmax( $page, 0, $max_pages );

		unset( $options[ 'count' ] );
		$options[ 'limit' ] = $per_page;
		$options[ 'page' ] = $page;
		
		$activities = $this->filters( 'threewp_activity_monitor_find_activities', $options );
		
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'current' => $page,
			'total' => $max_pages,
		));
		
		if ($page_links)
			$page_links = '<div style="width: 50%; float: right;" class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
		
		$rv = $page_links;
		
		$t_body = '';
		foreach( $activities as $activity )
		{
			$activity[ 'data' ] = $this->sql_decode( $activity[ 'data' ] );
			$display = $this->filters( 'threewp_activity_monitor_display_activity', $activity );
			$t_body .= '<tr>
				<td>' . $this->ago( $activity[ 'i_datetime' ] ) . '</td>
				<td>' . implode( '<br />', $display['data']['activity_strings'] )  . '</td>
			</tr>';
		}

		$rv .= ' 
			<table class="widefat">
				<caption>' . $this->_( 'Latest activity' ) . '</caption>
				<thead>
					<tr>
						<th>' . $this->_('Timestamp') . '</th>
						<th>' . $this->_('Activity') . '</th>
					</tr>
				</thead>
				<tbody>
					'.$t_body.'
				</tbody>
			</table>
		';
		
		$rv .= '<h3>' . $this->_( 'Quick guide' ) . '</h3>
		
		<p>' . $this->_( 'This plugin allows users to create custom, printable, PDFs from admin-designed templates.' ). '</p>

		<p>' . $this->_( 'The templates are available in the templates menu. Browse the template groups and then preview a template to see it in a larger format.' ). '</p>

		<p>' . $this->_( 'Create the customization and then edit it to fill in your own values on the resulting PDF.' ). '</p>
		
		<p>' . $this->_( 'Some templates require moderation before being allowed to be downloaded / viewed as a PDF.' ). '</p>

		<p>' . $this->_( 'For more help, read the instructions on each screen.' ). '</p>
		';
		
		$infos[] = $rv;
		return $infos;
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc methods
	// --------------------------------------------------------------------------------------------
	
	public function append_email_signature( $mail_data )
	{
		$signature = $this->get_site_option( 'email_signature' );
		if ( $signature != '' )
			$mail_data[ 'body_html' ] .= '<div>--</div>' . wpautop( $signature );
		return $mail_data;
	}

	/**
		@brief		Extended ajax nonce check.

		@param		$action
					Nonce action name
		
		@param		$key
					Key in POST where nonce is stored.
		
		@return					True if the nonce checks out.
	**/
	public static function check_admin_referrer( $action, $key = 'ajaxnonce' )
	{
		if ( !isset( $_POST[ $key ] ) )
			return false;
		return check_admin_referer( $action, $key );
	}
	
	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- SQL methods
	// --------------------------------------------------------------------------------------------
}

$SD_PDF_Template_Customizer = new SD_PDF_Template_Customizer();
