<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Interfaces;

/**
 * @package Change\Presentation\Interfaces
 * @name \Change\Presentation\Interfaces\Theme
 */
interface Theme
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @return void
	 */
	public function setThemeManager(\Change\Presentation\Themes\ThemeManager $themeManager);

	/**
	 * @return \Change\Presentation\Interfaces\Theme|null
	 */
	public function getParentTheme();

	/**
	 * @param string $moduleName
	 */
	public function removeTemplatesContent($moduleName);

	/**
	 * @param string $moduleName
	 * @param string $pathName
	 * @param string $content
	 * @return void
	 */
	public function installTemplateContent($moduleName, $pathName, $content);

	/**
	 * @param string $name
	 * @return \Change\Presentation\Interfaces\Template
	 */
	public function getPageTemplate($name);

	/**
	 * The path used by Twig to find the template.
	 * @param string $moduleName
	 * @param string $fileName
	 * @return string
	 */
	public function getTemplateRelativePath($moduleName, $fileName);

	/**
	 * @return string the base path in App directory.
	 */
	public function getTemplateBasePath();

	/**
	 * @param string $resourcePath
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	public function getResource($resourcePath);

	/**
	 * @return string the asset base path in theme plugin.
	 */
	public function getAssetBasePath();

	/**
	 * @return array
	 */
	public function getAssetConfiguration();

	/**
	 * @param string $resourcePath
	 * @return string
	 */
	public function getResourceFilePath($resourcePath);
}