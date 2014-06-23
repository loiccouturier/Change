<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\AdminRoutes
 */
class AdminRoutes
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();
		$application = $event->getApplication();
		$workspace = $application->getWorkspace();

		$applicationServices = $event->getApplicationServices();
		$modelManager = $applicationServices->getModelManager();

		foreach ($applicationServices->getPluginManager()->getModules() as $module)
		{
			if ($module->getName() === 'Rbs_Admin')
			{
				continue;
			}
			$moduleAdminAssetPath = $workspace->composePath($module->getAssetsPath(), 'Admin');
			$routeFilePath = $workspace->composePath($moduleAdminAssetPath, 'routes.json');

			if (is_dir(dirname($moduleAdminAssetPath)))
			{
				$modulePath = '/' . $module->getVendor() . '/' . $module->getShortName() . '/';

				if (is_readable($routeFilePath))
				{
					$routes = json_decode(file_get_contents($routeFilePath), true);
					if (!is_array($routes))
					{
						$response->addErrorMessage('Invalid json routes files : ' . $routeFilePath);
						continue;
					}
				}
				else
				{
					$routes = [];
					$routes[$modulePath] = ['module' => $module->getName(), 'name' => 'home',
						'rule' => ['redirectTo' => '/404'],
						'auto' => true];
				}

				$moduleRoute = (array_key_exists($modulePath, $routes)) ? $routes[$modulePath] : null;
				if (!$this->isAutoGenerated($moduleRoute))
				{
					$moduleRoute = null;
				}
				$generatedRoutes = [];

				$validModelName = $module->getName() . '_';
				foreach ($modelManager->getModelsNames() as $modelName)
				{
					if (strpos($modelName, $validModelName) !== 0)
					{
						continue;
					}

					$model = $modelManager->getModelByName($modelName);
					if ($model->isAbstract())
					{
						continue;
					}
					$modelRoutes = $this->buildModelRoutes($module, $model, $workspace);
					$generatedRoutes = array_merge($generatedRoutes, $modelRoutes);
					foreach ($modelRoutes as $path => $route)
					{
						if ($moduleRoute && $route['name'] === 'list')
						{
							if ($this->isAutoGenerated($moduleRoute))
							{
								$routes[$modulePath]['rule']['redirectTo'] = $path;
								$labelKey = strtolower('m.' . $module->getVendor() . '.' . $module->getShortName()
								. '.admin.module_name | ucf');
								$routes[$modulePath]['rule']['labelKey'] = $labelKey;
								$moduleRoute = null;
							}
						}

						$originalRoute = (array_key_exists($path, $routes)) ? $routes[$path] : null;
						if ($this->isAutoGenerated($originalRoute))
						{
							$route['auto'] = true;
							$routes[$path] = $route;
						}
					}
				}
				$validRoutes = [];
				foreach ($routes as $path => $route)
				{
					if (!$this->isAutoGenerated($route))
					{
						if ($path[0] !== '/')
						{
							$path = '/' . $path;
						}
						if (is_array($route) && !isset($route['rule']))
						{
							$route = false;
						}
						elseif (is_array($route))
						{
							$route['auto'] = false;
						}
					}
					elseif ($path != $modulePath)
					{
						if (!isset($generatedRoutes[$path]))
						{
							$route['auto'] = false;
						}
					}
					$validRoutes[$path] = $route;
				}

				ksort($validRoutes);
				$json = json_encode($validRoutes, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
				\Change\Stdlib\File::write($routeFilePath, str_replace('    ', '	', $json));
			}
		}
		$response->addInfoMessage('Done.');
	}

	/**
	 * @param $route
	 * @return boolean
	 */
	protected function isAutoGenerated($route)
	{
		return $route === null || (is_array($route) && isset($route['auto']) && $route['auto'] === true);
	}

	/**
	 * @param \Change\Plugins\Plugin $module
	 * @param \Change\Documents\AbstractModel $model
	 * @param \Change\Workspace $workspace
	 * @return array
	 */
	protected function buildModelRoutes($module, $model, $workspace)
	{
		$routes = [];
		$modelAssetPath = $workspace ? $workspace->composePath($module->getAssetsPath(), 'Admin', 'Documents',
			$model->getShortName()) : null;
		if ($modelAssetPath && !is_dir($modelAssetPath))
		{
			return $routes;
		}
		$modelName = $model->getName();
		$baseGenericRule = '/' . $module->getVendor() . '/' . $module->getShortName() . '/' . $model->getShortName() . '/';
		$baseKey = strtolower('m.' . $module->getVendor() . '.' . $module->getShortName());

		if (file_exists($workspace->composePath($modelAssetPath, 'list.twig')))
		{
			$route = ['model' => $modelName, 'name' => 'list',
				'rule' => [
					'templateUrl' => 'Document' . $baseGenericRule . 'list.twig',
					'labelKey' => $baseKey . '.admin.' . strtolower($model->getShortName()) . '_list | ucf'
				]];
			$routes[$baseGenericRule] = $route;
		}

		$docPath = ':id';

		if (file_exists($workspace->composePath($modelAssetPath, 'new.twig')))
		{
			$route = ['model' => $modelName, 'name' => 'new',
				'rule' => [
					'templateUrl' => 'Document' . $baseGenericRule . 'new.twig',
					'labelKey' => 'm.rbs.admin.adminjs.new_resource | ucf'
				]];
			$routes[$baseGenericRule . 'new'] = $route;
		}

		if (file_exists($workspace->composePath($modelAssetPath, 'edit.twig')))
		{
			$route = ['model' => $modelName, 'name' => 'edit',
				'rule' => [
					'templateUrl' => 'Document' . $baseGenericRule . 'edit.twig',
					'labelKey' => $baseKey . '.documents.' . strtolower($model->getShortName()) . ' | ucf',
					'labelId' => 'id'
				]];
			$routes[$baseGenericRule . $docPath] = $route;
		}

		if (file_exists($workspace->composePath($modelAssetPath, 'translate.twig')))
		{
			$route = ['model' => $modelName, 'name' => 'translate',
				'rule' => [
					'templateUrl' => 'Document' . $baseGenericRule . 'translate.twig',
					'controller' => 'RbsChangeTranslateEditorController',
					'labelKey' => 'm.rbs.admin.admin.translation | ucf'
				]];
			$routes[$baseGenericRule . $docPath . '/translate/:LCID'] = $route;
		}
		return $routes;
	}
}