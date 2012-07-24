<?php
/*
 * Copyright 2005-2013 the original author or authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Manage plugins
 */
Class PluginManager {

	/**
	 * Loads plugins and invokes Plugin::registerEvents() and Plugin::registerListeners()
	 *
	 * @param array $plugins_list List of plugins' names and configurations. For example:
	 * <code>
	 * $plugins_list = array();
	 * $plugins_list[] = array(
	 *	'name' => 'plugin_name', // Obligatory value
	 *	'config' => array( // Pass to plugin constructor
	 *		'weight' => 100,
	 *		'some_configurable_value' => 'value'
	 *	)
	 * )
	 * </code>
	 *
	 * @see Plugin::registerEvents()
	 * @see Plugin::registerListeners()
	 */
	public static function loadPlugins($plugins_list){
		// Add include path
		$include_path = get_include_path();
		$include_path .= empty($include_path) ? '' : PATH_SEPARATOR ;
		set_include_path($include_path . realpath(dirname(__FILE__) . "/../../plugins/"));

		// Load plugins
		$loading_queue = array();
		$offset = 0;
		foreach ($plugins_list as $plugin) {
			if (empty($plugin['name'])) {
				trigger_error("Plugin name undefined!", E_USER_WARNING);
				continue;
			}
			$plugin_name = $plugin['name'];
			$plugin_config = isset($plugin['config']) ? $plugin['config'] : array();
			$plugin_classname = ucfirst($plugin_name) . "Plugin";
			// Try to load plugin file
			if (! (include_once $plugin_name."/plugin.mibew.inc.php")) {
				trigger_error("Cannot load plugin file!", E_USER_ERROR);
			}
			// Check plugin class name
			if (! class_exists($plugin_classname)) {
				trigger_error(
					"Plugin class '{$plugin_classname}' does not defined!",
					E_USER_WARNING
				);
				continue;
			}
			// Check if plugin extends abstract 'Plugin' class
			if ('Plugin' != get_parent_class($plugin_classname)) {
				trigger_error(
					"Plugin class '{$plugin_classname}' does not extend " .
					"abstract 'Plugin' class!",
					E_USER_WARNING
				);
				continue;
			}
			// Add plugin to loading queue
			$plugin_instance = new $plugin_classname($plugin_config);
			if ($plugin_instance->initialized) {
				$loading_queue[$plugin_instance->getWeight() . "_" . $offset] = $plugin_instance;
				$offset++;
			} else {
				trigger_error(
					"Plugin '{$plugin_name}' does not initialized correctly!",
					E_USER_WARNING
				);
			}
		}
		// Sort queue in order to plugins' weights
		uksort($loading_queue, 'strnatcmp');
		// Add events and listeners
		foreach ($loading_queue as $plugin) {
			$plugin->registerEvents();
			$plugin->registerListeners();
		}
	}
}

?>