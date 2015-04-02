<?php

require_once('licensing.php');

/*
 * Add our cron action hook
 */
add_action( 'gfpdfechecklicensekeystatus', array('GFPDFE_license_model', 'check_license_key_status') );

abstract class gfpdfeAddonFramework
{
	private $addon = array();

	public function __construct()
	{	
		/*
		 * Set up the addon details 
		 */
		$this->addon['name'] = $this->setName();	
		$this->addon['version_number'] = $this->setVersionNumber();	
		$this->addon['author'] = $this->setAuthorName();
		$this->addon['min_version'] = $this->setMinVersion();
		$this->addon['path'] = $this->setPluginPath();
		$this->addon['url'] = $this->setPluginUrl();
		$this->addon['file'] = $this->setFile();
		$this->addon['basename'] = $this->setPluginBasename();
		$this->addon['license'] = $this->setLicense();
		$this->addon['settings'] = $this->setSettings();

		$this->setID();
		$this->setLicenseKey();

		/*
		 * Set up our hooks and filters which the base plugin fires
		 */
		add_action('gfpdfe_pre_compatibility_checks', array($this, 'check_compatibility'));
		add_action('gfpdfe_addons', array($this, 'init'));

		add_filter('pdf_extended_settings_navigation', array($this, 'add_license_page'));
	}

	/*
	 * Convert our plugin name to a ID we can more easily use for post and get requests
	 */
	final private function setID()
	{
		$name = $this->addon['name'];
		$name = strtolower($name);
		$name = str_replace(' ', '_', $name);

		$this->addon['id'] = $name;
	}

	/**
	 * If a premium plugin we will pull the license key from the database
	 */
	final private function setLicenseKey()
	{
		if($this->addon['license'] === true)
		{
			/*
			 * Pull license keys from database and store in our addon
			 */
			$this->addon['license_key']     = get_option('gfpdfe_addon_' . $this->addon['id']. '_license');			
			$this->addon['license_expires'] = get_option('gfpdfe_addon_' . $this->addon['id']. '_license_expires');			
			$this->addon['license_status']  = get_option( 'gfpdfe_addon_' . $this->addon['id']. '_license_status' );
			$this->addon['download_id']     = get_option( 'gfpdfe_addon_' . $this->addon['id']. '_download_id' );
			
			/*
			 * Run our plugin update checker
			 */
			add_action( 'admin_init', array($this, 'plugin_updater') );	

		}
		return false;
	}

	final public static function add_cron_license_event()
	{		
		if ( ! wp_next_scheduled( 'gfpdfe_check_license' ) ) {
			/* run daily at midnight */						
			wp_schedule_event( mktime(0,0,0), 'daily', 'gfpdfechecklicensekeystatus');
		}		
	}

	final public function plugin_updater() {
		
		global $gfpdfe_data;

		// retrieve our license key from the DB
		$license_key = trim( $this->addon['license_key'] );

		// setup the updater
		$updater = new GFPDFE_Plugin_Updater( $gfpdfe_data->store_url, $this->addon['file'], $this->addon, array( 
				'version' 	=> $this->addon['version_number'], 				
				'license' 	=> $license_key, 		
				'item_name' => $this->addon['name'], 	
				'author' 	=> $this->addon['author']  
			)
		);

	}


	final public function check_compatibility()
	{
		global $gfpdf, $gfpdfe_data;

		/*
		 * Tell the base plugin about our addon
		 */
		GFPDF_Core::$addon[] = $this->addon;

		/*
		 * Check the compatibility
		 */
		 if(version_compare(PDF_EXTENDED_VERSION, $this->addon['min_version'], '>=') !== true)
		 {
		 	add_action($gfpdfe_data->notice_type, array($this, 'base_plugin_not_supported'));							
			return;
		 }	


		/*
		 * Assign a cron even to run every day to check the validity of the license 
		 */
		self::add_cron_license_event();		 		
	}

	/**
	 * Helper function to easily display messages below the plugin screen
	 * @param  string  $message  The error to output
	 * @param  boolean $is_error Whether it is a message or an error that should be displayed
	 */
	final private static function display_plugin_message($message, $is_error = false){

        $style = $is_error ? 'style="background-color: #ffebe8;"' : "";

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }	

    /**
     * Add our license page to the settings navigation, if it doesn't already exist
     * @param array $navigation order => array('name', 'id', 'template')
     */
    final public function add_license_page($navigation)
    {
    	/* If the plugin is licensed we will add a new settings page */
    	if($this->addon['license'] === true)
    	{
    		if(!$this->check_settings_page_exists($navigation, 'license'))
    		{
				$navigation[50] = array(
					'name' => __('License', 'pdfextended'),
					'id' => 'license',
					'template' => PDF_PLUGIN_DIR . 'view/templates/settings/license.php',
				);
    		}
    	}

    	if($this->addon['settings'] === true)
    	{
    		if(!$this->check_settings_page_exists($navigation, 'addon'))
    		{
				$navigation[40] = array(
					'name' => __('Addon', 'pdfextended'),
					'id' => 'addon',
					'template' => PDF_PLUGIN_DIR . 'view/templates/settings/addon.php',
				);
    		}    		
    	}
    	return $navigation;
    }

    final private function check_settings_page_exists($navigation, $id)
    {
   		/* check if page already exists */
		foreach($navigation as $item)
		{
			if($item['id'] == $id)
			{
				return true;
			}
		}    	
		return false;
    }

	/**
	 * Generate an error message about the base plugin not being supported
	 */
	final public function base_plugin_not_supported()
	{
		$msg = sprintf(__('%s requires version %s of Gravity Forms PDF Extended installed to run. Please upgrade the plugin.', 'pdfextended'), $this->addon['name'], $this->addon['min_version']);
		GFPDF_Notices::error($msg);
	}    

	/**
	 * Automatically triggered to run on GFPDFE's 'gfpdfe_addons' hook which fires after the plugin is successfully installed (right after WP 'init' hook)
	 * Add core plugin logic here 	 
	 */
	abstract public function init();

	/**
	 * Set the plugin name 
	 * This should be the name in EDD which we'll use for upgrades
	 * @return string the name of the plugin. If using licensing software, must be the exact name in EDD
	 */
	abstract protected function setName();

	/**
	 * Set the current version number of the addon
	 * @return string The current version number. Used for licensing updates
	 */
	abstract protected function setVersionNumber();

	/**
	 * Set the author name of the add on
	 * @return Name of plugin developer/company
	 */
	abstract protected function setAuthorName();

	/**
	 * Set the minimum version of Gravity Forms PDF Extended 
	 * needed to run the add on
	 * @return set the minimum version of GFPDFE required to run
	 */
	abstract protected function setMinVersion();

	/**
	 * Set the plugin path using the inbuilt plugin_dir_path() function 
	 * This can't be included in the abstract class as it is in the GFPDFE folder.
	 * @return string should always return plugin_dir_path( __FILE__ );
	 */
	abstract protected function setPluginPath();

	/**
	 * Set the plugin path using the inbuilt plugin_dir_path() function 
	 * This can't be included in the abstract class as it is in the GFPDFE folder.
	 * @return string should always return plugin_dir_url( __FILE__ );
	 */
	abstract protected function setPluginUrl();


	/**
	 * Set the plugin path using the inbuilt plugin_dir_path() function 
	 * This can't be included in the abstract class as it is in the GFPDFE folder.
	 * @return string should always return plugin_basename(__FILE__);
	 */
	abstract protected function setPluginBasename();

	/**
	 * Whether the plugin is a premium addon and should have a license key
	 * @return boolean Whether the software is tied to our license key system
	 */
	abstract protected function setLicense();

	/**
	 * Whether the plugin has any settings that should be added to the 'AddOn' page
	 * @return boolean Whether the addon adds any settings to the addon page
	 */
	abstract protected function setSettings();

	/**
	 * Set the current file plugin is running from
	 * @return string must always return __FILE__
	 */
	abstract protected function setFile();

}

