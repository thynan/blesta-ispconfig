<?php
/**
 * en_us language for the ispconfig module
 */
// Basics
$lang['Ispconfig.name'] = "ISPConfig";
$lang['Ispconfig.module_row'] = "Server";
$lang['Ispconfig.module_row_plural'] = "Servers";
$lang['Ispconfig.module_group'] = "Server Group";
$lang['Ispconfig.tab_stats'] = "Statistics";
$lang['Ispconfig.tab_client_stats'] = "Statistics";

// Module management
$lang['Ispconfig.add_module_row'] = "Add Server";
$lang['Ispconfig.add_module_group'] = "Add Server Group";
$lang['Ispconfig.manage.module_rows_title'] = "Servers";
$lang['Ispconfig.manage.module_groups_title'] = "Server Groups";
$lang['Ispconfig.manage.module_rows_heading.name'] = "Server Label";
$lang['Ispconfig.manage.module_rows_heading.hostname'] = "Hostname";
$lang['Ispconfig.manage.module_rows_heading.accounts'] = "Accounts";
$lang['Ispconfig.manage.module_rows_heading.options'] = "Options";
$lang['Ispconfig.manage.module_groups_heading.name'] = "Group Name";
$lang['Ispconfig.manage.module_groups_heading.servers'] = "Server Count";
$lang['Ispconfig.manage.module_groups_heading.options'] = "Options";
$lang['Ispconfig.manage.module_rows.count'] = "%1\$s / %2\$s"; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Ispconfig.manage.module_rows.edit'] = "Edit";
$lang['Ispconfig.manage.module_groups.edit'] = "Edit";
$lang['Ispconfig.manage.module_rows.delete'] = "Delete";
$lang['Ispconfig.manage.module_groups.delete'] = "Delete";
$lang['Ispconfig.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Ispconfig.manage.module_groups.confirm_delete'] = "Are you sure you want to delete this server group?";
$lang['Ispconfig.manage.module_rows_no_results'] = "There are no servers.";
$lang['Ispconfig.manage.module_groups_no_results'] = "There are no server groups.";


$lang['Ispconfig.order_options.first'] = "First non-full server";

// Add row
$lang['Ispconfig.add_row.box_title'] = "Add Ispconfig Server";
$lang['Ispconfig.add_row.basic_title'] = "Basic Settings";
$lang['Ispconfig.add_row.notes_title'] = "Notes";
$lang['Ispconfig.add_row.name_server_host_col'] = "Hostname Master";
$lang['Ispconfig.add_row.remove_name_server'] = "Remove";
$lang['Ispconfig.add_row.add_btn'] = "Add Server";
$lang['Ispconfig.add_row.name_server_host_col'] = "Hostname";

$lang['Ispconfig.edit_row.box_title'] = "Edit ISPConfig Server";
$lang['Ispconfig.edit_row.basic_title'] = "Basic Settings";
$lang['Ispconfig.edit_row.notes_title'] = "Notes";
$lang['Ispconfig.edit_row.name_server_host_col'] = "Hostname Master";
$lang['Ispconfig.edit_row.remove_name_server'] = "Remove";
$lang['Ispconfig.edit_row.add_btn'] = "Edit Server";

$lang['Ispconfig.row_meta.server_name'] = "Server Label";
$lang['Ispconfig.row_meta.host_name'] = "Hostname Master-Server";
$lang['Ispconfig.row_meta.host_name_db'] = "Hostname Mailserver";
$lang['Ispconfig.row_meta.host_name_web'] = "Hostname DB-Server";
$lang['Ispconfig.row_meta.host_name_mail'] = "Hostname Webserver";
$lang['Ispconfig.row_meta.user_name'] = "Remote User Name";
$lang['Ispconfig.row_meta.remote_pw'] = "Remote User Password";
$lang['Ispconfig.row_meta.soap_location'] = "SOAP Location";
$lang['Ispconfig.row_meta.soap_uri'] = "SOAP URI";
$lang['Ispconfig.row_meta.multiserver'] = "Server is a Multiserver";

$lang['Ispconfig.row_meta.account_limit'] = "Account Limit";

// Package fields
$lang['Ispconfig.package_fields.type'] = "Account Type";
$lang['Ispconfig.package_fields.type_standard'] = "Standard";
$lang['Ispconfig.package_fields.template'] = "Ispconfig Client Template";
$lang['Ispconfig.package_fields.host_names_web'] = "Webserver";
$lang['Ispconfig.package_fields.host_names_mail'] = "Mailserver";
$lang['Ispconfig.package_fields.host_names_db'] = "DB-Server";
$lang['Ispconfig.package_fields.host_names_ns_primary'] = "Primary Nameserver";
$lang['Ispconfig.package_fields.host_names_ns_secondary'] = "Secondary Nameserver";

// Service fields
$lang['Ispconfig.service_field.client_id'] = "ISPConfig Client ID - ONLY ENTER IF ISPCONFIG CLIENT EXISTS!";
$lang['Ispconfig.service_field.contactname'] = "Contact Name";
$lang['Ispconfig.service_field.username'] = "Username";
$lang['Ispconfig.service_field.password'] = "Password";
$lang['Ispconfig.service_field.confirm_password'] = "Confirm Password";
$lang['Ispconfig.service_field.domain'] = "Choose your free domain";



// Service info
$lang['Ispconfig.service_info.username'] = "Username";
$lang['Ispconfig.service_info.password'] = "Password";
$lang['Ispconfig.service_info.server'] = "Server";
$lang['Ispconfig.service_info.options'] = "Options";
$lang['Ispconfig.service_info.domain'] = "Included Domain";
$lang['Ispconfig.service_info.option_login'] = "Log in";
$lang['Ispconfig.service_field.domain'] = "Enter domainname for your free domain";



// Tooltips
$lang['Ispconfig.service_field.tooltip.contactname'] = "You may leave the contactname blank to automatically generate one.";
$lang['Ispconfig.service_field.tooltip.username'] = "You may leave the username blank to automatically generate one.";
$lang['Ispconfig.service_field.tooltip.password'] = "You may leave the password blank to automatically generate one.";
$lang['Ispconfig.service_field.tooltip.client_id'] = "This should only be manually entered when creating a new Blesta service for a client that already exists in ISPConfig.
    In that case, uncheck the 'Provision using the ISPConfig module' checkbox, and enter the ID you see in the ISPConfig Panel under Client/Clients. The Blesta service will then be connected to the ISPConfig client-account";
$lang['Ispconfig.service_field.tooltip.domain'] = "If you need additional domains, you can order them via our domain order form, and configure them in your hosting-account.";
$lang['Ispconfig.service_field.existing_domain'] = "The domain already exists and should be transferred";


// Errors
$lang['Ispconfig.!error.server_name_valid'] = "You must enter a Server Label.";
$lang['Ispconfig.!error.host_name_valid'] = "The Hostname of the Master-Server appears to be invalid.";
$lang['Ispconfig.!error.host_name_db_valid'] = "The Hostname of the DB-Server appears to be invalid.";
$lang['Ispconfig.!error.host_name_mail_valid'] = "The Hostname of the Mailserver appears to be invalid.";
$lang['Ispconfig.!error.host_name_web_valid'] = "The Hostname of the Webserver appears to be invalid.";
$lang['Ispconfig.!error.user_name_valid'] = "The User Name appears to be invalid.";
$lang['Ispconfig.!error.remote_pw_valid'] = "The Remote Password appears to be invalid.";
$lang['Ispconfig.!error.remote_key_valid_connection'] = "A connection to the server could not be established. Please check to ensure that the Hostname, User Name, and Remote Key are correct.";
$lang['Ispconfig.!error.account_limit_valid'] = "Account Limit must be left blank (for unlimited accounts) or set to some integer value.";
$lang['Ispconfig.!error.meta[template].empty'] = "An ISPConfig Template is required.";
$lang['Ispconfig.!error.api.internal'] = "An internal error occurred, or the server did not respond to the request.";
$lang['Ispconfig.!error.module_row.missing'] = "An internal error occurred. The module row is unavailable.";
$lang['Ispconfig.!error.ispconfig_domain.exists'] = "Sorry, this domain is already registered.";

$lang['Ispconfig.!error.ispconfig_contactname.format'] = "The contact name may contain only letters and numbers and may not start with a number.";
$lang['Ispconfig.!error.ispconfig_contactname.test'] = "The contact name may not begin with 'test'.";
$lang['Ispconfig.!error.ispconfig_username.format'] = "The username may contain only letters and numbers and may not start with a number.";
$lang['Ispconfig.!error.ispconfig_username.test'] = "The username may not begin with 'test'.";
$lang['Ispconfig.!error.ispconfig_username.length'] = "The username must be between 1 and 8 characters in length.";
$lang['Ispconfig.!error.ispconfig_password.valid'] = "Password must be at least 8 characters in length.";
$lang['Ispconfig.!error.ispconfig_password.matches'] = "Password and Confirm Password do not match.";
$lang['Ispconfig.!error.ispconfig_domain.format'] = "The Domain name is not valid";

?>