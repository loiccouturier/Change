<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Plugins;

use Change\Workspace;
use Zend\Loader\StandardAutoloader;

/**
* @name \Change\Plugins\Autoloader
*/
class Autoloader extends StandardAutoloader
{
	protected $populated = false;

	/**
	 * @var Workspace
	 */
	protected $workspace;

	/**
	 * @param Workspace $workspace
	 */
	public function setWorkspace(Workspace $workspace)
	{
		$this->workspace = $workspace;
	}

	/**
	 * @return string
	 */
	protected function getPsr0CachePath()
	{
		return $this->workspace->cachePath('.pluginsNamespace.ser');
	}

	/**
	 * @see \Change\Plugins\PluginManager::getCompiledPluginsPath()
	 * @return string
	 */
	public function getCompiledPluginsPath()
	{
		return $this->workspace->compilationPath('Change', 'Plugins.ser');
	}

	protected function populate()
	{
		$cachePath = $this->getPsr0CachePath();
		if (is_readable($cachePath))
		{
			$namespaces = unserialize(file_get_contents($cachePath));
			foreach ($namespaces as $name => $relativePath)
			{
				$this->registerNamespace($name, $this->workspace->composeAbsolutePath($relativePath));
			}
			return;
		}

		$compiledPluginsPath = $this->getCompiledPluginsPath();
		if (is_readable($compiledPluginsPath))
		{
			$workspace = $this->workspace;
			$namespaces = array();
			$plugins = unserialize(file_get_contents($compiledPluginsPath));
			foreach ($plugins as $plugin)
			{
				/** @var $plugin Plugin */
				$plugin->setWorkspace($workspace);
				$relativePath = $plugin->getRelativePath();
				$namespace = $plugin->getNamespace() . '\\';
				$namespaces[$namespace] = $relativePath;
				$this->registerNamespace($namespace, $this->workspace->composeAbsolutePath($relativePath));
			}
			$content = serialize($namespaces);
			\Change\Stdlib\File::write($cachePath, $content);
		}
	}

	/**
	 * @param string $class
	 * @param string $type
	 * @return bool|string
	 */
	protected function loadClass($class, $type)
	{
		if (false === $this->populated)
		{
			$this->populated = true;
			$this->populate();
		}
		return parent::loadClass($class, $type);
	}

	public function reset()
	{
		$callbackArray = spl_autoload_functions();
		if (is_array($callbackArray))
		{
			foreach($callbackArray as $callback)
			{
				if (is_array($callback) && ($callback[0] instanceof Autoloader))
				{
					$autoLoader = $callback[0];

					/* @var $autoLoader Autoloader */
					$autoLoader->populated = false;
					$autoLoader->namespaces = array();
				}
			}
		}

		$cachePath = $this->getPsr0CachePath();
		if (is_readable($cachePath))
		{
			unlink($cachePath);
		}
	}
}