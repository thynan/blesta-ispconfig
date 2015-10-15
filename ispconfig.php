<?php
/**
 * ISPConfig Module
 *
 * @package blesta
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */


//TODO: surround api calls with try-catch
//TODO: Check why only fist service gets set to active after customer ordered multiple services (e.g. basic + advanced) at once.
//      The cron-job gets stuck on activation of second service (maybe check logs)
//      This is rather low priority, because it doesn't make much sense for a client to order multiple packages.
//      Atm the second Service stays "pending", and can be manually enabled if need be.
//TODO: add reseller functionality
//TODO: add functions for "statistics tab". Empty at the moment


class Ispconfig extends Module {

	/**
	 * @var string The version of this module
	 */
	private static $version = "1.0.0";
	/**
	 * @var string The authors of this module
	 */
	private static $authors = array(array('name'=>"Thynan",'url'=>"http://blesta.com"));

	/**
	 * Initializes the module
	 */
	public function __construct() {
		// Load components required by this module
		Loader::loadComponents($this, array("Input"));

		// Load the language required by this module
		Language::loadLang("ispconfig", null, dirname(__FILE__) . DS . "language" . DS);
	}

	/**
	 * Returns the name of this module
	 *
	 * @return string The common name of this module
	 */
	public function getName() {
		return Language::_("Ispconfig.name", true);
	}

	/**
	 * Returns the version of this gateway
	 *
	 * @return string The current version of this gateway
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and url of the authors of this module
	 *
	 * @return array The name and url of the authors of this module
	 */
	public function getAuthors() {
		return self::$authors;
	}

	/**
	 * Returns all tabs to display to an admin when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getAdminTabs($package) {
		return array(
			'tabStats' => Language::_("Ispconfig.tab_stats", true)
		);
	}

	/**
	 * Returns all tabs to display to a client when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getClientTabs($package) {
		return array(
			'tabClientStats' => Language::_("Ispconfig.tab_client_stats", true)
		);
	}

	/**
	 * Returns a noun used to refer to a module row (e.g. "Server")
	 *
	 * @return string The noun used to refer to a module row
	 */
	public function moduleRowName() {
		return Language::_("Ispconfig.module_row", true);
	}

	/**
	 * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
	 *
	 * @return string The noun used to refer to a module row in plural form
	 */
	public function moduleRowNamePlural() {
		return Language::_("Ispconfig.module_row_plural", true);
	}

	/**
	 * Returns a noun used to refer to a module group (e.g. "Server Group")
	 *
	 * @return string The noun used to refer to a module group
	 */
	public function moduleGroupName() {
		return Language::_("Ispconfig.module_group", true);
	}

	/**
	 * Returns the key used to identify the primary field from the set of module row meta fields.
	 *
	 * @return string The key used to identify the primary field from the set of module row meta fields
	 */
	public function moduleRowMetaKey() {
		return "server_name";
	}

	/**
	 * Returns an array of available service delegation order methods. The module
	 * will determine how each method is defined. For example, the method "first"
	 * may be implemented such that it returns the module row with the least number
	 * of services assigned to it.
	 *
	 * @return array An array of order methods in key/value paris where the key is the type to be stored for the group and value is the name for that option
	 * @see Module::selectModuleRow()
	 */
	public function getGroupOrderOptions() {
		return array('first'=>Language::_("Ispconfig.order_options.first", true));
	}

	/**
	 * Determines which module row should be attempted when a service is provisioned
	 * for the given group based upon the order method set for that group.
	 *
	 * @return int The module row ID to attempt to add the service with
	 * @see Module::getGroupOrderOptions()
	 */
	public function selectModuleRow($module_group_id) {
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));

		$group = $this->ModuleManager->getGroup($module_group_id);

		if ($group) {
			switch ($group->add_order) {
				default:
				case "first":

					foreach ($group->rows as $row) {
						if ($row->meta->account_limit > (isset($row->meta->account_count) ? $row->meta->account_count : 0))
							return $row->id;
					}
					break;
			}
		}
		return 0;
	}


	/**
	 * Returns all fields used when adding/editing a package, including any
	 * javascript to execute when the page is rendered with these fields.
	 *
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containing the fields to render as well as any additional HTML markup to include
	 */
	public function getPackageFields($vars=null) {
		Loader::loadHelpers($this, array("Html"));

		$fields = new ModuleFields();

		// Fetch all packages available for the given server or server group
		$module_row = null;
		if (isset($vars->module_group) && $vars->module_group == "") {
			if (isset($vars->module_row) && $vars->module_row > 0) {
				$module_row = $this->getModuleRow($vars->module_row);
			}
			else {
				$rows = $this->getModuleRows();
				if (isset($rows[0]))
					$module_row = $rows[0];
				unset($rows);
			}
		}
		else {
			// Fetch the 1st server from the list of servers in the selected group
			$rows = $this->getModuleRows($vars->module_group);

			if (isset($rows[0]))
				$module_row = $rows[0];
			unset($rows);
		}

		$templates = array();

		if ($module_row) {
			$templates = $this->getIspconfigClientTemplates($module_row);
		}


		// Set the ISPConfig package as a selectable option
		$template = $fields->label(Language::_("Ispconfig.package_fields.template", true), "ispconfig_template");
		$template->attach($fields->fieldSelect("meta[template]", $templates,
			$this->Html->ifSet($vars->meta['template']), array('id'=>"ispconfig_template")));
		$fields->setField($template);

		$this->log($module_row->meta->host_name . "|is multi", serialize($module_row->meta->multiserver), "input", true);

		if($module_row->meta->multiserver == true) {
			//set ISPConfig webserver as selectable Option
			$servers = $this->getIspconfigServersByType($module_row, "web_server");

			$webserver = $fields->label(Language::_("Ispconfig.package_fields.host_names_web", true), "ispconfig_host_name_web");
			$webserver->attach($fields->fieldSelect("meta[web_server]", $servers,
				$this->Html->ifSet($vars->meta['web_server']), array('id' => "ispconfig_host_name_web")));
			$fields->setField($webserver);

			//set ISPConfig dbserver as selectable Option
			$servers = $this->getIspconfigServersByType($module_row, "db_server");

			$dbserver = $fields->label(Language::_("Ispconfig.package_fields.host_names_db", true), "ispconfig_host_name_db");
			$dbserver->attach($fields->fieldSelect("meta[db_server]", $servers,
				$this->Html->ifSet($vars->meta['db_server']), array('id' => "ispconfig_host_name_db")));
			$fields->setField($dbserver);

			//set ISPConfig mailserver as selectable Option
			$servers = $this->getIspconfigServersByType($module_row, "mail_server");

			$mailserver = $fields->label(Language::_("Ispconfig.package_fields.host_names_mail", true), "ispconfig_host_name_mail");
			$mailserver->attach($fields->fieldSelect("meta[mail_server]", $servers,
				$this->Html->ifSet($vars->meta['mail_server']), array('id' => "ispconfig_host_name_mail")));
			$fields->setField($mailserver);

			//set ISPConfig nameservers as selectable Option
			$servers = $this->getIspconfigServersByType($module_row, "dns_server");

			$nameserver_primary = $fields->label(Language::_("Ispconfig.package_fields.host_names_ns_primary", true), "ispconfig_host_name_ns_primary");
			$nameserver_primary->attach($fields->fieldSelect("meta[name_server_primary]", $servers,
				$this->Html->ifSet($vars->meta['name_server_primary']), array('id' => "ispconfig_host_name_ns_primary")));
			$fields->setField($nameserver_primary);

			$nameserver_secondary = $fields->label(Language::_("Ispconfig.package_fields.host_names_ns_secondary", true), "ispconfig_host_name_ns_secondary");
			$nameserver_secondary->attach($fields->fieldSelect("meta[name_server_secondary]", $servers,
				$this->Html->ifSet($vars->meta['name_server']), array('id' => "ispconfig_host_name_ns_secondary")));
			$fields->setField($nameserver_secondary);
		}

		return $fields;
	}

	/**
	 * Returns an array of key values for fields stored for a module, package,
	 * and service under this module, used to substitute those keys with their
	 * actual module, package, or service meta values in related emails.
	 *
	 * @return array A multi-dimensional array of key/value pairs where each key is one of 'module', 'package', or 'service' and each value is a numerically indexed array of key values that match meta fields under that category.
	 * @see Modules::addModuleRow()
	 * @see Modules::editModuleRow()
	 * @see Modules::addPackage()
	 * @see Modules::editPackage()
	 * @see Modules::addService()
	 * @see Modules::editService()
	 */
	public function getEmailTags() {
		return array(
			'module' => array('host_name'), //removed name_servers from array
			'package' => array('type', 'package'),
			'service' => array('ispconfig_username', 'ispconfig_password', 'ispconfig_domain', 'ispconfig_existing_domain')
		);
	}

	/**
	 * Validates input data when attempting to add a package, returns the meta
	 * data to save when adding a package. Performs any action required to add
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being added.
	 *
	 * @param array An array of key/value pairs used to add the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addPackage(array $vars=null) {

		// Set rules to validate input data
		$this->Input->setRules($this->getPackageRules($vars));
		// Build meta data to return
		$meta = array();
		if ($this->Input->validates($vars)) {


			// Return all package meta fields
			foreach ($vars['meta'] as $key => $value) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}
		return $meta;
	}

	/**
	 * Validates input data when attempting to edit a package, returns the meta
	 * data to save when editing a package. Performs any action required to edit
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array An array of key/value pairs used to edit the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editPackage($package, array $vars=null) {

		// Set rules to validate input data
		$this->Input->setRules($this->getPackageRules($vars));

		// Build meta data to return
		$meta = array();
		if ($this->Input->validates($vars)) {


			// Return all package meta fields
			foreach ($vars['meta'] as $key => $value) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}
		return $meta;
	}


	/**
	 * Returns the rendered view of the manage module page
	 *
	 * @param mixed $module A stdClass object representing the module and its rows
	 * @param array $vars An array of post data submitted to or on the manager module page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the manager module page
	 */
	public function manageModule($module, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("manage", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "ispconfig" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		$this->view->set("module", $module);

		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the add module row page
	 *
	 * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the add module row page
	 */
	public function manageAddRow(array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("add_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "ispconfig" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));


		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the edit module row page
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the edit module row page
	 */
	public function manageEditRow($module_row, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("edit_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "ispconfig" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		if (empty($vars))
			$vars = $module_row->meta;
		else {
			// Set unspecified checkboxes

		}
		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Adds the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being added. Returns a set of data, which may be
	 * a subset of $vars, that is stored for this module row
	 *
	 * @param array $vars An array of module info to add
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function addModuleRow(array &$vars) {
		//TODO: maybe later on, add functionality to order servers of multiserver group (e.g. web1 - if full web2 etc) -
		//TODO: like the existing server-group functionality, but within the multiserver server-row.) Maybe on second page?
		$meta_fields = array("server_name", "host_name", "user_name",
			 "account_limit", "account_count", "notes","remote_pw", "soap_location", "soap_uri", "multiserver"  );

		// Set unspecified checkboxes
		if (empty($vars['multiserver']))
			$vars['multiserver'] = "false";

		$encrypted_fields = array("user_name", "remote_pw");

		$this->Input->setRules($this->getRowRules($vars));

		// Validate module row
		if ($this->Input->validates($vars)) {

	// Build the meta data for this row
			$meta = array();
			foreach ($vars as $key => $value) {

				if (in_array($key, $meta_fields)) {
					$meta[] = array(
						'key'=>$key,
						'value'=>$value,
						'encrypted'=>in_array($key, $encrypted_fields) ? 1 : 0
					);
				}
			}
			return $meta;
		}
	}

	/**
	 * Edits the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being updated. Returns a set of data, which may be
	 * a subset of $vars, that is stored for this module row
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of module info to update
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function editModuleRow($module_row, array &$vars) {
		$meta_fields = array("server_name", "host_name", "user_name",
			"account_limit", "account_count","notes","remote_pw", "soap_location", "soap_uri", "multiserver"  );


		$encrypted_fields = array("user_name", "remote_pw");
		// Set unspecified checkboxes
		if (empty($vars['multiserver']))
			$vars['multiserver'] = "false";

		$this->Input->setRules($this->getRowRules($vars));

		// Validate module row
		if ($this->Input->validates($vars)) {

			// Build the meta data for this row
			$meta = array();

			foreach ($vars as $key => $value) {

				if (in_array($key, $meta_fields)) {
					$meta[] = array(
						'key'=>$key,
						'value'=>$value,
						'encrypted'=>in_array($key, $encrypted_fields) ? 1 : 0
					);
				}
			}
			return $meta;
		}
	}

	/**
	 * Deletes the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being deleted.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 */
	public function deleteModuleRow($module_row) {

	}

	/***********************************************
	 * Service Management
	************************************************/


	/**
	 * Returns the value used to identify a particular service
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @return string A value used to identify this service amongst other similar services
	 */
	public function getServiceName($service) {
		foreach ($service->fields as $field) {
			if ($field->key == "ispconfig_username")
				return $field->value;
		}
		return null;
	}

	/**
	 * Returns the value used to identify a particular package service which has
	 * not yet been made into a service. This may be used to uniquely identify
	 * an uncreated services of the same package (i.e. in an order form checkout)
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @return string The value used to identify this package service
	 * @see Module::getServiceName()
	 */
	public function getPackageServiceName($package, array $vars=null) {
		if (isset($vars['ispconfig_username']))
			return $vars['ispconfig_username'];
		return null;
	}

	/**
	 * Returns all fields to display to an admin attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getAdminAddFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		$fields = new ModuleFields();
		$fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Initialize Visibility
					var checkbox = $('input#use_module');

					if (checkbox.is(':checked')) {
						$('#ispconfig_client_id').closest('li').hide();
					}
					else {
						$('#ispconfig_client_id').closest('li').show();
					}
					checkbox.click(function() {
						if ($(this).is(':checked')) {
							$('#ispconfig_client_id').closest('li').hide();
						}
						else {
							$('#ispconfig_client_id').closest('li').show();
						}
					});
				});
			</script>
		");


		// Create clientid label
		$client_id = $fields->label(Language::_("Ispconfig.service_field.client_id", true), "ispconfig_client_id");
		// Create username field and attach to username label
		$client_id->attach($fields->fieldText("ispconfig_client_id", $this->Html->ifSet($vars->ispconfig_client_id), array('id'=>"ispconfig_client_id")));
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.client_id", true));
		$client_id->attach($tooltip);
		// Set the label as a field
		$fields->setField($client_id);

		// Create username label
		$username = $fields->label(Language::_("Ispconfig.service_field.username", true), "ispconfig_username");
		// Create username field and attach to username label
		$username->attach($fields->fieldText("ispconfig_username", $this->Html->ifSet($vars->ispconfig_username), array('id'=>"ispconfig_username")));
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.username", true));
		$username->attach($tooltip);
		// Set the label as a field
		$fields->setField($username);

		// Create password label
		$password = $fields->label(Language::_("Ispconfig.service_field.password", true), "ispconfig_password");
		// Create password field and attach to password label
		$password->attach($fields->fieldPassword("ispconfig_password", array('id'=>"ispconfig_password", 'value'=>$this->Html->ifSet($vars->ispconfig_password))));
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.password", true));
		$password->attach($tooltip);
		// Set the label as a field
		$fields->setField($password);

		// Confirm password label
		$confirm_password = $fields->label(Language::_("Ispconfig.service_field.confirm_password", true), "ispconfig_confirm_password");
		// Create confirm password field and attach to password label
		$confirm_password->attach($fields->fieldPassword("ispconfig_confirm_password", array('id'=>"ispconfig_confirm_password", 'value'=>$this->Html->ifSet($vars->ispconfig_password))));
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.password", true));
		$confirm_password->attach($tooltip);
		// Set the label as a field
		$fields->setField($confirm_password);

		// Create domain label
		$domain = $fields->label(Language::_("Ispconfig.service_field.domain", true), "ispconfig_domain");
		// Create domain field and attach to domain label
		$domain->attach($fields->fieldText("ispconfig_domain", $this->Html->ifSet($vars->ispconfig_domain, $this->Html->ifSet($vars->ispconfig_domain)), array('id'=>"ispconfig_domain")));
		// Set the label as a field
		$fields->setField($domain);
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.domain", true));
		$domain->attach($tooltip);

		// Create existing_domain label
		$existing_domain = $fields->label(Language::_("Ispconfig.service_field.existing_domain", true), "ispconfig_existing_domain");
		// Create domain field and attach to domain label
		$existing_domain->attach($fields->fieldCheckbox("ispconfig_existing_domain", "true", $this->Html->ifSet($vars->ispconfig_existing_domain, $this->Html->ifSet($vars->ispconfig_existing_domain)), array('id'=>"ispconfig_existing_domain")));
		// Set the label as a field
		$fields->setField($existing_domain);

		return $fields;
	}


	/**
	 * Returns all fields to display to a client attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getClientAddFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		$fields = new ModuleFields();

		// Create domain label
		$domain = $fields->label(Language::_("Ispconfig.service_field.domain", true), "ispconfig_domain");
		// Create domain field and attach to domain label
		$domain->attach($fields->fieldText("ispconfig_domain", $this->Html->ifSet($vars->ispconfig_domain, $this->Html->ifSet($vars->ispconfig_domain)), array('id'=>"ispconfig_domain", 'placeholder' => 'yourdomain.at')));
		// Set the label as a field
		$fields->setField($domain);
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Ispconfig.service_field.tooltip.domain", true));
		$domain->attach($tooltip);

		// Create existing_domain label
		$existing_domain = $fields->label(Language::_("Ispconfig.service_field.existing_domain", true), "ispconfig_existing_domain");
		// Create domain field and attach to domain label
		$existing_domain->attach($fields->fieldCheckbox("ispconfig_existing_domain", "true", $this->Html->ifSet($vars->ispconfig_existing_domain, $this->Html->ifSet($vars->ispconfig_existing_domain)), array('id'=>"ispconfig_existing_domain")));
		// Set the label as a field
		$fields->setField($existing_domain);




		return $fields;
	}

	/**
	 * Returns all fields to display to an admin attempting to edit a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getAdminEditFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		$fields = new ModuleFields();

		// Create username label
		$username = $fields->label(Language::_("Ispconfig.service_field.username", true), "ispconfig_username");
		// Create username field and attach to username label
		$username->attach($fields->fieldText("ispconfig_username", $this->Html->ifSet($vars->ispconfig_username), array('id'=>"ispconfig_username")));
		// Set the label as a field
		$fields->setField($username);

		// Create password label
		$password = $fields->label(Language::_("Ispconfig.service_field.password", true), "ispconfig_password");
		// Create password field and attach to password label
		$password->attach($fields->fieldPassword("ispconfig_password", array('id'=>"ispconfig_password", 'value' => $this->Html->ifSet($vars->ispconfig_password))));
		// Set the label as a field
		$fields->setField($password);

		return $fields;
	}

	/**
	 * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @return boolean True if the service validates, false otherwise. Sets Input errors when false.
	 */
	public function validateService($package, array $vars=null, $edit=false) {
		$rules = array(
			'ispconfig_username' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("matches", "/^[a-z]([a-z0-9\.\-])*$/i"),
					'message' => Language::_("Ispconfig.!error.ispconfig_username.format", true)
				),
				'test' => array(
					'if_set' => true,
					'rule' => array("matches", "/^(?!test)/"),
					'message' => Language::_("Ispconfig.!error.ispconfig_username.test", true)
				),
				'length' => array(
					'if_set' => true,
					'rule' => array("betweenLength", 1, 30),
					'message' => Language::_("Ispconfig.!error.ispconfig_username.length", true)
				)
			),
			'ispconfig_password' => array(
				'valid' => array(
					'if_set' => true,
					'rule' => array("isPassword", 8),
					'message' => Language::_("Ispconfig.!error.ispconfig_password.valid", true),
					'last' => true
				),
			),
			'ispconfig_confirm_password' => array(
				'matches' => array(
					'if_set' => true,
					'rule' => array("compares", "==", (isset($vars['ispconfig_password']) ? $vars['ispconfig_password'] : "")),
					'message' => Language::_("Ispconfig.!error.ispconfig_password.matches", true)
				)
			),
			'ispconfig_domain' => array(
				'format' => array(
					'rule' => array(array($this, "validateDomainName")),
					'message' => Language::_("Ispconfig.!error.ispconfig_domain.format", true)
				),
				'valid' => array(
					'rule' => array(array($this, "checkDomain"), $vars['ispconfig_existing_domain']),
					'message' => Language::_("Ispconfig.!error.ispconfig_domain.exists", true)
				),
			)
		);

		// Set the values that may be empty
		$empty_values = array("ispconfig_username", "ispconfig_password");

		if ($edit) {
			// If this is an edit and no password given then don't evaluate password
			// since it won't be updated
			if (!array_key_exists('ispconfig_password', $vars) || $vars['ispconfig_password'] == "") {
				unset($rules['ispconfig_password']);
			}
		}

		// Remove rules on empty fields
		foreach ($empty_values as $value) {
			if (empty($vars[$value]))
				unset($rules[$value]);
		}

		$this->Input->setRules($rules);
		return $this->Input->validates($vars);
	}


	/***********************************************************
	 * INTERACTION WITH REMOTE SERVER (over API) starts here
	 * ISPCOnfig remote-api:
     *usr/local/ispconfig/interface/lib/classes/remoting.inc.php.
     * ISPConfig Soap Connection:
     * $soap_location = 'https://servername.domain.tld:8080/remote/index.php';
     *	$soap_uri = 'https://servername.domain.tld:8080/remote/';
     *************************************************************/

	/**
	 * Adds the service to the remote server. Sets Input errors on failure,
	 * preventing the service from being added.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if the current service is an addon service service and parent service has already been provisioned)
	 * @param string $status The status of the service being added. These include:
	 * 	- active
	 * 	- canceled
	 * 	- pending
	 * 	- suspended
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addService($package, array $vars=null, $parent_package=null, $parent_service=null, $status="pending") {
		$row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Ispconfig.!error.module_row.missing", true))));
			return;
		}
		$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);

		Loader::loadModels($this, array("Clients"));

		// Use client's email address
		if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], true))){
			$vars['ispconfig_email'] = $client->email;
			$vars['ispconfig_first_name'] = $client->first_name;
			$vars['ispconfig_last_name'] = $client->last_name;
			$vars['ispconfig_company'] = $client->company;
		}

		// Generate a username
		if (empty($vars['ispconfig_username']))
			$vars['ispconfig_username'] = $this->generateUsername($vars['ispconfig_first_name'], $vars['ispconfig_last_name'],$vars['ispconfig_company']);

		// Generate a password
		if (empty($vars['ispconfig_password'])) {
			$vars['ispconfig_password'] = $this->generatePassword();
			$vars['ispconfig_confirm_password'] = $vars['ispconfig_password'];
		}

		//get params from user input (auto-generated if empty)
		$params = $this->getFieldsFromInput((array)$vars, $package);

		//add additional fields needed for ispconfig-client creation
		$params['contact_name'] = $vars['ispconfig_first_name'] . " " . $vars['ispconfig_last_name'];
		$params['company_name'] = isset($vars['ispconfig_company']) ? $vars['ispconfig_company'] : '';
		$params['country'] =  isset($client->country) ? $client->country : '';
		$params['language'] =  isset($client->settings['language']) ? $client->settings['language'] : 'en';
		$params['template'] = $package->meta->template;
		$params['added_by'] = 'Blesta ISPConfig Module';
		$params['domain'] = $vars['ispconfig_domain'];

		//TODO: check with some server (e.g. ns) empty, and check if errors occur
		try {

			$params['default_dbserver'] = reset(reset($api->getSoapClient()->server_get_serverid_by_name($api->getSessionId(), $package->meta->db_server)));
			$params['default_mailserver'] = reset(reset($api->getSoapClient()->server_get_serverid_by_name($api->getSessionId(), $package->meta->mail_server)));
			$params['default_webserver'] = reset(reset($api->getSoapClient()->server_get_serverid_by_name($api->getSessionId(), $package->meta->web_server)));
			$params['default_dnsserver'] = reset(reset($api->getSoapClient()->server_get_serverid_by_name($api->getSessionId(), $package->meta->name_server_primary)));
			$params['default_slave_dnsserver'] = reset(reset($api->getSoapClient()->server_get_serverid_by_name($api->getSessionId(), $package->meta->name_server_secondary)));
			$params['template_master'] = reset(reset($api->getSoapClient()->client_template_get_id_by_name($api->getSessionId(), $params['template'])));

		}
		catch (Exception $e){
			//log
		}

		$this->validateService($package, $vars);

		if ($this->Input->errors())
			return;


		//Ispconfig client_id - used to uniquely identify client, even if user name is changed in Blesta or Ispconfig
		//without synchronization
		$ispconfig_client_id = $vars['ispconfig_client_id'];

		//Only provision the service if 'use_module' is true
		if ($vars['use_module'] == "true") {

			$masked_params = $params;
			$masked_params['password'] = "***";
			unset($masked_params);

			//Don't use reseller. Not supported yet by this module
			$reseller = 0;

			//create the client on the ISPConfig Server
			$ispconfig_client_id = $api->clientAdd($reseller, $params);

			//Create default dns-zone for the default domain on the ISPConfig DNS-Servers
			//TODO: general error checking
			//TODO: We assume here, that the DNS-Servers are the same as configured in the package. This doesn't make much sense for a singleserver of course (has max. 1 DNS Server).
			//TODO: A better solution would be to let the client choose if he even wants to create default zonefiles, and also let him choose the dns-servers. (Use getClientAddFields() to add the fields)
			//TODO: We don't do that here, because in our case we use a multiserver setup and want all clients to use our DNS-Servers by default.
			//TODO: We use the default Ispconfig DNS Template here (id = 1). If you have multiple DNS Templates configured on your server, you may want to change this.
			if(!empty($params['domain']) && !empty($ispconfig_client_id)) {
				$ispconfig_serverIP = gethostbyname($row->meta->host_name);
				$api->dns_add_default_zone($ispconfig_client_id, 1 /*1 = default template */ ,$params['domain'], $ispconfig_serverIP, $package->meta->name_server_primary, $package->meta->name_server_secondary, $client->email);
			}


			if ($this->Input->errors())
				return;


			// Update the number of accounts on the server
			$this->updateAccountCount($row);
		}


		// Return service fields
		return array(
			array(
				'key' => "ispconfig_client_id",
				'value' => $ispconfig_client_id,
				'encrypted' => 0
			),
			array(
				'key' => "ispconfig_username",
				'value' => $params['username'],
				'encrypted' => 0
			),
			array(
				'key' => "ispconfig_password",
				'value' => $params['password'],
				'encrypted' => 1
			),
			array(
				'key' => "ispconfig_domain",
				'value' =>  $params['domain'],
				'encrypted' => 0
			),
			array(
				'key' => "ispconfig_existing_domain",
				'value' =>  $vars['ispconfig_existing_domain'],
				'encrypted' => 0
			)
		);
	}

	/**
	 * Edits the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being edited.
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editService($package, $service, array $vars=null, $parent_package=null, $parent_service=null) {
		$row = $this->getModuleRow();
		$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);
		$soapClient = $api->getSoapClient();
		$sessionId = $api->getSessionId();

		$this->validateService($package, $vars, true);

		if ($this->Input->errors())
			return;

		$service_fields = $this->serviceFieldsToObject($service->fields);

		// Remove password if not being updated
		if (isset($vars['ispconfig_password']) && $vars['ispconfig_password'] == "")
			unset($vars['ispconfig_password']);

		// Only update the service if 'use_module' is true
		if ($vars['use_module'] == "true") {

			// Check for fields that changed
			$delta = array();
			foreach ($vars as $key => $value) {
				if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key)
					$delta[$key] = $value;
			}

			// Update password (if changed)
			if (isset($delta['ispconfig_password'])) {

				$this->log($row->meta->host_name . "|passwd", "***", "input", true);
				$result = $soapClient->client_change_password($sessionId, $service_fields->ispconfig_client_id, $delta['ispconfig_password']);
			}

			// Update username (if changed), do last so we can always rely on $service_fields['ispconfig_username'] to contain the username
			if (isset($delta['ispconfig_username'])) {
				//get existing client
				$client_record = $soapClient->client_get($sessionId, $service_fields->ispconfig_client_id);
				if(!empty($client_record)) {
					$client_record['username'] = $delta['ispconfig_username'];
					//reseller not yet supported
					$reseller = 0;
					$this->log($row->meta->host_name . "|modifyacct", serialize($client_record), "input", true);
					$result = $soapClient->client_update($sessionId, $service_fields->ispconfig_client_id, $reseller, $client_record);
					$this->log($row->meta->host_name . "|changedIspconfigUser", serialize($result), "input", true);
				}
			}
		}

        // Set fields to update locally
		$fields = array( "ispconfig_username", "ispconfig_password");
		foreach ($fields as $field) {
			if (property_exists($service_fields, $field) && isset($vars[$field]))
				$service_fields->{$field} = $vars[$field];
		}

        // Set the confirm password to the password
        $service_fields->ispconfig_confirm_password = $service_fields->ispconfig_password;

        // Return all the service fields
		$fields = array();
		$encrypted_fields = array("ispconfig_password", "ispconfig_confirm_password");
		foreach ($service_fields as $key => $value)
			$fields[] = array('key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0));

		return $fields;
	}

	/**
	 * Suspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being suspended.
	 *
	 */
	//TODO: this doesn't work as expected in ISPConfig - however it's not a problem coming from this module, but with how ISPConfig handles client suspension. Maybe it will be fixed in ISPconfig soon
	public function suspendService($package, $service, $parent_package=null, $parent_service=null) {
		$row = $this->getModuleRow();

		if ($row) {
			$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);
			$soapClient = $api->getSoapClient();
			$sessionId = $api->getSessionId();

			$service_fields = $this->serviceFieldsToObject($service->fields);

			$this->log($row->meta->host_name . "|suspendacct", serialize($service_fields->ispconfig_username), "input", true);
			$client_record = $soapClient->client_get($sessionId, $service_fields->ispconfig_client_id);
			if(!empty($client_record)) {
				$client_record['locked'] = 'y';
				$client_record['canceled'] = 'y';
				//reseller not yet supported
				$reseller = 0;
				$result = $soapClient->client_update($sessionId, $service_fields->ispconfig_client_id, $reseller, $client_record);
				$this->log($row->meta->host_name . "|suspendedUser", serialize($result), "input", true);
			}
		}
		return null;
	}

	/**
	 * Unsuspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being unsuspended.
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function unsuspendService($package, $service, $parent_package=null, $parent_service=null) {

		if (($row = $this->getModuleRow())) {
			$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);
			$soapClient = $api->getSoapClient();
			$sessionId = $api->getSessionId();

			$service_fields = $this->serviceFieldsToObject($service->fields);

			$this->log($row->meta->host_name . "|unsuspendacct", serialize($service_fields->ispconfig_username), "input", true);

			$client_record = $soapClient->client_get($sessionId, $service_fields->ispconfig_client_id);
			if(!empty($client_record)) {
				$client_record['locked'] = 'n';
				$client_record['canceled'] = 'n';
				//reseller not yet supported
				$reseller = 0;
				$result = $soapClient->client_update($sessionId, $service_fields->ispconfig_client_id, $reseller, $client_record);
				$this->log($row->meta->host_name . "|suspendedUser", serialize($result), "input", true);
			}
		}
		return null;
	}

	/**
	 * Cancels the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being canceled.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being canceled (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	//TODO: ask again if client really should be deleted, and show actions that will be taken (deleted webs, mails, dbs, ns...)
	public function cancelService($package, $service, $parent_package=null, $parent_service=null) {

			if (($row = $this->getModuleRow())) {
				$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);
				$soapClient = $api->getSoapClient();
				$sessionId = $api->getSessionId();

				try {

					$service_fields = $this->serviceFieldsToObject($service->fields);
					$this->log($row->meta->host_name . "|removeacct", serialize($service_fields->ispconfig_username), "input", true);
					//client_delete function: deletes client | client_delete_everything function: delete also webs, dns, dbs etc
					$result = $soapClient->client_delete_everything($sessionId, $service_fields->ispconfig_client_id);
					$this->log($row->meta->host_name . "|deleteAcct", serialize($result), "input", true);
				}
				catch (Exception $e) {
					$this->log($row->meta->host_name . "|deleteAcctFail", serialize($e), "input", true);
				}

				// Update the number of accounts on the server
				$this->updateAccountCount($row);
			}
		return null;
	}

	/**
	 * Updates the package for the service on the remote server. Sets Input
	 * errors on failure, preventing the service's package from being changed.
	 *
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	//TODO: only allow to select packages from the same server. Otherwise an "illegal" template might be used
	public function changeServicePackage($package_from, $package_to, $service, $parent_package=null, $parent_service=null) {
		if (($row = $this->getModuleRow())) {
			$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);
			$soapClient = $api->getSoapClient();
			$sessionId = $api->getSessionId();


			// Only request a package change if it has changed
			if ($package_from->meta->template != $package_to->meta->template) {

				$service_fields = $this->serviceFieldsToObject($service->fields);

				$this->log($row->meta->host_name . "|changepackage", serialize(array($service_fields->ispconfig_username, $package_to->meta->package)), "input", true);


				$client_record = $soapClient->client_get($sessionId, $service_fields->ispconfig_client_id);
				if(!empty($client_record)) {
					$client_record['template_master'] = reset(reset($api->getSoapClient()->client_template_get_id_by_name($api->getSessionId(), $package_to->meta->template)));
					//reseller not yet supported
					$reseller = 0;
					$result = $soapClient->client_update($sessionId, $service_fields->ispconfig_client_id, $reseller, $client_record);
					$this->log($row->meta->host_name . "|suspendedUser", serialize($result), "input", true);
				}
			}
		}
		return null;
	}

	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * admin interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getAdminServiceInfo($service, $package) {
		$row = $this->getModuleRow();

		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("admin_service_info", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "ispconfig" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("module_row", $row);
		$this->view->set("package", $package);
		$this->view->set("service", $service);
		$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

		$this->log($row->meta->host_name . "|serviceInfo", serialize($service->fields), "input", true);


		return $this->view->fetch();
	}

	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * client interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getClientServiceInfo($service, $package) {
		$row = $this->getModuleRow();

		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("client_service_info", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "ispconfig" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("module_row", $row);
		$this->view->set("package", $package);
		$this->view->set("service", $service);
		$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

		return $this->view->fetch();
	}


	/**
	 * Validates that the given hostname is valid
	 *
	 * @param string $host_name The host name to validate
	 * @return boolean True if the hostname is valid, false otherwise
	 */
	public function validateHostName($host_name) {
		if (strlen($host_name) > 255)
			return false;

		return $this->Input->matches($host_name, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/");
	}

	/**
	 * Validates that the given domainname is valid or empty
	 *
	 * @param string $domainname The domainname to validate
	 * @return boolean True if the domainname is valid, false otherwise
	 */
	public function validateDomainName($domainname)
	{
		if (strlen($domainname) > 255)
			return false;

		if (empty($domainname) || $this->Input->matches($domainname, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/")){
			return true;
		}
		else return false;
	}


	/**
	 * Validates that at least 2 name servers are set in the given array of name servers
	 *
	 * @param array $name_servers An array of name servers
	 * @return boolean True if the array count is >= 2, false otherwise
	 */
	public function validateNameServerCount($name_servers) {
		if (is_array($name_servers) && count($name_servers) >= 2)
			return true;
		return false;
	}

	/**
	 * Validates that the nameservers given are formatted correctly
	 *
	 * @param array $name_servers An array of name servers
	 * @return boolean True if every name server is formatted correctly, false otherwise
	 */
	public function validateNameServers($name_servers) {
		if (is_array($name_servers)) {
			foreach ($name_servers as $name_server) {
				if (!$this->validateHostName($name_server))
					return false;
			}
		}
		return true;
	}

	/**
	 * Updates the module row meta number of accounts
	 *
	 * @param stdClass $module_row A stdClass object representing a single server
	 */
	private function updateAccountCount($module_row) {
		$api = $this->getApi($module_row->meta->host_name, $module_row->meta->user_name, $module_row->meta->remote_pw, $module_row->meta->soap_location, $module_row->meta->soap_uri);

		try {
			$count = count($api->getSoapClient()->client_get_all($api->getSessionId()));
			$this->log($module_row->meta->host_name . "|upadteAccountCount", $count, "output", false);
		}
		catch (Exception $e){
			$this->log($module_row->meta->host_name . "|upadteAccountCount", $e, "output", false);
		}

		// Get the number of accounts on the server
		if (!empty($count) && $count > 0) {
			// Update the module row account list
			Loader::loadModels($this, array("ModuleManager"));
			$vars = $this->ModuleManager->getRowMeta($module_row->id);

			if ($vars) {
				$vars->account_count = $count;
				$vars = (array)$vars;
				$this->ModuleManager->editRow($module_row->id, $vars);
			}
		}
	}

	/**
	 * Validates whether or not the connection details are valid, by checking for a set session_id in the
	 * ISPConfig API Object
	 *
	 * @return boolean True if the connection is valid, false otherwise
	 */
	public function validateConnection($remote_pw, $host_name, $user_name, $soap_location, $soap_uri) {

		$api = $this->getApi( $host_name, $user_name, $remote_pw, $soap_location, $soap_uri);

		return $api->getSessionId();
	}

	/**
	 * Generates a username from the given name of the Blesta client
	 *
	 * @param string $first_name The first name of the Blesta client
	 * @param string $last_name The second name of the Blesta client
	 * @param string $company The company name of the Blesta client
	 * @return string The username generated from the given hostname
	 *  Contact Name = Vorname Nachname
	 *  Username = companyname[Number] (if exists), else v.nachname[Number]
	 */
	private function generateUsername($first_name, $last_name, $company = NULL, $count = 1)
	{

		if ($company != null && $company != ""){
			$username = $this->cleanString($company);
		}
		else{
			$username = $this->cleanString($first_name[0]) . "." . $this->cleanString($last_name);
		}

		// Check for existing user accounts
		$accounts = $this->getUserAccounts($username);

		// Username exists, create another instead
		if (!empty($accounts)) {

			$count++;
			if ($company != null && $company != "") {
				$company =  preg_replace("/\d+$/","",$company);
				$company = $company . $count;
				$username = $this->generateUsername($first_name, $last_name, $company, $count);
			}
			else {
				$last_name =  preg_replace("/\d+$/","",$last_name);
				$last_name = $last_name . $count;
				$username = $this->generateUsername($first_name, $last_name, $company, $count);
			}
		}

		return $username;
	}

	/**
	 * Retrieves matching user accounts
	 *
	 * @param string $name The account username
	 * @return
	 */
	private function getUserAccounts($name) {

		$user = null;
		$row = $this->getModuleRow();
		if ($row)
			$api = $this->getApi($row->meta->host_name, $row->meta->user_name, $row->meta->remote_pw, $row->meta->soap_location, $row->meta->soap_uri);

		try {
			if ($api) {
				$output = $api->getSoapClient()->client_get_by_username($api->getSessionId(), $name);

				if (!empty($output))
					$user = $output['username'];
			}
		}
		catch (Exception $e) {
			$this->log($row->meta->host_name . "|getUserAccounts", $e, "output", false);

		}

		return $user;
	}

	/*************************************************************
	 * Helper Functions (Connect to API, Validation etc.
	 *********************************************************/



	/**
	 * Generates a password
	 *
	 * @param int $min_length The minimum character length for the password (5 or larger)
	 * @param int $max_length The maximum character length for the password (14 or fewer)
	 * @return string The generated password
	 */
	private function generatePassword($min_length=10, $max_length=14) {
		$pool = "abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
		$pool_size = strlen($pool);
		$length = mt_rand(max($min_length, 5), min($max_length, 14));
		$password = "";

		for ($i=0; $i<$length; $i++) {
			$password .= substr($pool, mt_rand(0, $pool_size-1), 1);
		}

		return $password;
	}

	/**
	 * Returns an array of service field to set for the service using the given input
	 *
	 * @param array $vars An array of key/value input pairs
	 * @param stdClass $package A stdClass object representing the package for the service
	 * @return array An array of key/value pairs representing service fields
	 */
	private function getFieldsFromInput(array $vars, $package) {
		$fields = array(
			'username' => isset($vars['ispconfig_username']) ? $vars['ispconfig_username']: null,
			'password' => isset($vars['ispconfig_password']) ? $vars['ispconfig_password'] : null,
			'email' => isset($vars['ispconfig_email']) ? $vars['ispconfig_email'] : null
		);

		return $fields;
	}


	/**
	 * Initializes the IspconfigAPI and returns an instance of that object with the given $host, $user, $pass,
	 * and $soap_client set. Also logs in to soap-server and sets $session_id.
	 *
	 */
	private function getApi($host, $user, $remote_pw, $soap_location, $soap_uri ) {
		Loader::load(dirname(__FILE__) . DS . "apis" . DS . "ispconfig_api.php");

		$api = new IspconfigApi($host, $user, $remote_pw, $soap_location, $soap_uri);
		$api->soap_login();
		return $api;
	}

	/**
	 * Fetches a listing of all client templates configured in Ispconfig for the given server
	 *
	 * @param stdClass $module_row A stdClass object representing a single server
	 * @return array An array of templates in key/value pair
	 */
	private function getIspconfigClientTemplates($module_row) {

		$api = $this->getApi($module_row->meta->host_name, $module_row->meta->user_name, $module_row->meta->remote_pw, $module_row->meta->soap_location, $module_row->meta->soap_uri);
		$template_list = $api->getSoapClient()->client_templates_get_all($api->getSessionId());
		$this->log($module_row->meta->host_name . "|client_templates", serialize($template_list), "output", "true");

		$templates = array();

		if(!empty($template_list)){
			foreach($template_list as $template){
				$templates[$template['template_name']] = $template['template_name'];
			}
		}
        return $templates;
	}

	private function getIspconfigServersByType($module_row, $type) {

		$api = $this->getApi($module_row->meta->host_name, $module_row->meta->user_name, $module_row->meta->remote_pw, $module_row->meta->soap_location, $module_row->meta->soap_uri);
		$server_list = $api->getSoapClient()->server_get_by_type($api->getSessionId(), $type);
		$this->log($module_row->meta->host_name . "|get_server_by_type", serialize($server_list), "output", "true");

		$servers = array();

		if(!empty($server_list)){
			foreach($server_list as $server){
				$servers[$server['server_name']] = $server['server_name'];
			}
		}
		return $servers;
	}

	/**
	 * Builds and returns the rules required to add/edit a module row (e.g. server)
	 *
	 * @param array $vars An array of key/value data pairs
	 * @return array An array of Input rules suitable for Input::setRules()
	 */
	private function getRowRules(&$vars) {
		$rules = array(
			'server_name'=>array(
				'valid'=>array(
					'rule'=>"isEmpty",
					'negate'=>true,
					'message'=>Language::_("Ispconfig.!error.server_name_valid", true)
				)
			),
			'host_name'=>array(
				'valid'=>array(
					'rule'=>array(array($this, "validateHostName")),
					'message'=>Language::_("Ispconfig.!error.host_name_valid", true)
				)
			),
//			'host_name_db'=>array(
//				'valid'=>array(
//					'rule'=>array(array($this, "validateHostName")),
//					'message'=>Language::_("Ispconfig.!error.host_name_db_valid", true)
//				)
//			),
//			'host_name_web'=>array(
//				'valid'=>array(
//					'rule'=>array(array($this, "validateHostName")),
//					'message'=>Language::_("Ispconfig.!error.host_name_web_valid", true)
//				)
//			),
//			'host_name_mail'=>array(
//				'valid'=>array(
//					'rule'=>array(array($this, "validateHostName")),
//					'message'=>Language::_("Ispconfig.!error.host_name_mail_valid", true)
//				)
//			),
			'user_name'=>array(
				'valid'=>array(
					'rule'=>"isEmpty",
					'negate'=>true,
					'message'=>Language::_("Ispconfig.!error.user_name_valid", true)
				)
			),
			'remote_pw'=>array(
				'valid'=>array(
					'last'=>true,
					'rule'=>"isEmpty",
					'negate'=>true,
					'message'=>Language::_("Ispconfig.!error.remote_pw_valid", true)
				),
				'valid_connection'=>array(
					'rule'=>array(array($this, "validateConnection"), $vars['host_name'], $vars['user_name'], $vars['soap_location'], $vars['soap_uri']),
					'message'=>Language::_("Ispconfig.!error.remote_key_valid_connection", true)
				)
			),
			'account_limit'=>array(
				'valid'=>array(
					'rule'=>array("matches", "/^([0-9]+)?$/"),
					'message'=>Language::_("Ispconfig.!error.account_limit_valid", true)
				)
			),
//			'name_servers'=>array(
//				'count'=>array(
//					'rule'=>array(array($this, "validateNameServerCount")),
//					'message'=>Language::_("Ispconfig.!error.name_servers_count", true)
//				),
//				'valid'=>array(
//					'rule'=>array(array($this, "validateNameServers")),
//					'message'=>Language::_("Ispconfig.!error.name_servers_valid", true)
//				)
//			)
		);

		return $rules;
	}

	/**
	 * Builds and returns rules required to be validated when adding/editing a package
	 *
	 * @param array $vars An array of key/value data pairs
	 * @return array An array of Input rules suitable for Input::setRules()
	 */
	private function getPackageRules($vars) {
		$rules = array(
			'meta[template]' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Ispconfig.!error.meta[template].empty", true) // package must be given
				)
			)
		);

		return $rules;
	}
	/**
	 * Cleans up strings like company names, usernames etc to guarantee validity
	 */
	function cleanString($string) {
		//Lower case everything
		$string = strtolower($string);
		//Make alphanumeric (removes all other characters)
		$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
		//Clean up multiple dashes or whitespaces
		$string = preg_replace("/[\s-]+/", " ", $string);
		//Convert whitespaces and underscore to dash
		$string = preg_replace("/[\s_]/", "-", $string);
		return $string;
	}

	/**
	 * Check if the domain is already registered or not
	 */
	public function checkDomain($domainname, $existing_domain = "true") {


		//if hostname is not valid, don't execute this check (it will throw an exception). The validateHostName rule will throw the
		//appropriate error in the validateService() function.
		if(!$this->validateHostName($domainname)){
			return true;
		}

		//if the user selected that the domain is an existing domain, we don't need to tell him that it is already registered..
		if($existing_domain === "true"){
			return true;
		}

		//if the user left the domain-field empty, don't do availability check
		if(empty($domainname)){
			return true;
		}

		//check if domain is already registered
		Loader::load(dirname(__FILE__) . DS . "lib" . DS . "availabilityService.php");
		$availabilityService = new AvailabilityService(false);
		$available = $availabilityService->isAvailable($domainname);

		return $available;
	}
}
?>
