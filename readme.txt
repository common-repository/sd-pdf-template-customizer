=== SD PDF Template Customizer ===
Tags: pdf, template, customizer, generate
Requires at least: 3.4.1
Tested up to: 3.4.1
Stable tag: trunk
Contributors: Sverigedemokraterna IT
Donate link: https://it.sverigedemokraterna.se/donera/

Allow users to customize existing PDF templates with own text.

== Description ==

= Templates =

A template is a combination of an existing PDF file and some objects: textfields / textareas / images.

Objects have placements: on which pages and where the objects are placed on the PDF. The same object can be duplicated several times on the same page or across several pages.

= Template groups =

Template groups are used to help organize templates: business cards, A4 flyers, posters, etc.

By assigning users as moderators of a group, all templates placed in the template group must be moderated before being allowed to be printed.

= Customizations =

A customization is the result of a user using a template and placing his own text / images on the template.

= Social customizations =

The admin can decide to allow customizations to be shared amongst users. Users can browse the different template groups and look at the customizations made by others.

Customizations can also be quickly cloned by the press of a button, and then modified by the user.

Install <a href="http://wordpress.org/extend/plugins/threewp-activity-monitor/">ThreeWP Activity Monitor</a> to allow users to see social activity: when a new template is created, when a customization is made, etc.

= Requirements =

Imagemagick must be installed.

= Languages = 

Available in

* English
* Swedish

== Installation ==

1. Download and activate.
1. Create an "empty" PDF with just a nice background.
1. Upload the PDF to the media library.
1. Create a template.
1. Set the template to use the uploaded PDF.
1. Add template fields to the template.
1. Inform users that the template is ready to use!

== Screenshots ==

1. The menu
1. Template overview. The currently viewed template group is "Flygblad" (flyers)
1. Managing the fields of a template. The two visible fields are a text field and a text area.
1. Editing a specific field. Default text, font size, color, etc are settable.
1. When viewing a customization, there is a clone customization button.
1. Editing a customization allows the user only to change the text, not color or font or anything.

== Changelog ==

= 1.1 2012-08-xx =
* New: Generated PDFs no longer have any annotations
* New: Templates have moderation messages
* Fixed: Default role to use is "administrator", not "admin"
* Obsolete: remove PDF annotations

= 1.0 2012-07-23 =
* New: Initial public release

== Upgrade Notice ==

= 1.1 =

Due to a bug, version 1.0 could not be used by people other than the super admin. To fix this, access to the database will be required.

Find the options table for the blog, find the SD_PDF_Template_Customizer_role_use key.

Change the value from "admin" to "administrator".
