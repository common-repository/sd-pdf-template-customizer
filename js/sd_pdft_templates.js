function sd_pdft_templates()
{
	$ = jQuery;
	
	/**
		Generic init function.
	**/
	this.init = function( ajaxoptions, settings )
	{
		this.ajaxoptions = $.extend( true, {}, ajaxoptions );
		this.settings = $.extend( true, {}, settings );
	},
	
	/**
		Generic admin init.
	**/
	this.init_admin = function( ajaxoptions, settings )
	{
		this.init( ajaxoptions, settings );
		
		// Enable sorting of the agenda items.
		var caller = this;
		$('table.template_fields tbody').sortable({
			helper: 'clone',
			stop: function(e,ui) {
				caller.save_item_order();
			}
		});
	},
	
	/**
		Reads and saves the order of the items.
	**/
	this.save_item_order = function()
	{
		var ids = {};
		$.each( $('table.template_fields tbody th.check-column input'), function (index, item){
				var item_id = $(item).attr('name');
				var item_id = item_id.replace('ids[', '').replace(']','');
				ids[ index ] = item_id;
		});

		options = this.ajaxoptions;
		options.type = "template_fields_reorder";
		options.order = ids;
		
		$.post( ajaxurl, options, function(data){
			try
			{
				result = sd_mt.parseJSON( data );
			}
			catch ( exception )
			{
			}
		} );
	}
};
