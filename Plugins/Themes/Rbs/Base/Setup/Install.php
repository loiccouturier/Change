<?php
namespace Theme\Rbs\Base\Setup;

/**
 * @name \Theme\Rbs\Base\Setup
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$pluginManager = $applicationServices->getPluginManager();
		$modules = $pluginManager->getModules();
		$themeManager = $presentationServices->getThemeManager();
		$themeManager->setDocumentServices($documentServices);
		$themeManager->installPluginTemplates($plugin);
		$themeManager->installPluginAssets($plugin);
		foreach ($modules as $module)
		{
			if ($module->isAvailable())
			{
				//echo $module, PHP_EOL;
				$themeManager->installPluginTemplates($module);
				$themeManager->installPluginAssets($module);
			}
		}
		$configuration = $themeManager->getDefault()->getAssetConfiguration();
		$am = $themeManager->getAsseticManager($configuration);
		$writer = new \Assetic\AssetWriter($themeManager->getAssetRootPath());
		$writer->writeManagerAssets($am);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}