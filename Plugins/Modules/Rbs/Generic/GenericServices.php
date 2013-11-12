<?php
namespace Rbs\Generic;

use Change\Application;
use Change\Events\EventManagerFactory;
use Change\Services\ApplicationServices;

/**
 * @name \Rbs\Generic\GenericServices
 */
class GenericServices extends \Zend\Di\Di
{
	use \Change\Services\ServicesCapableTrait;

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @return array<alias => className>
	 */
	protected function loadInjectionClasses()
	{
		$classes = $this->getApplication()->getConfiguration('Rbs/Generic/Services');
		return is_array($classes) ? $classes : array();
	}

	/**
	 * @param Application $application
	 * @param EventManagerFactory $eventManagerFactory
	 * @param ApplicationServices $applicationServices
	 */
	public function __construct(Application $application, EventManagerFactory $eventManagerFactory, ApplicationServices $applicationServices)
	{
		$this->setApplication($application);
		$this->setEventManagerFactory($eventManagerFactory);
		$this->setApplicationServices($applicationServices);

		$definitionList = new \Zend\Di\DefinitionList(array());

		//SeoManager : EventManagerFactory, Application, ApplicationServices
		$seoManagerClassName = $this->getInjectedClassName('SeoManager', 'Rbs\Seo\SeoManager');
		$classDefinition = $this->getDefaultClassDefinition($seoManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		//AvatarManager : EventManagerFactory, Application, ApplicationServices
		$avatarManagerClassName = $this->getInjectedClassName('AvatarManager', 'Rbs\Media\Avatar\AvatarManager');
		$classDefinition = $this->getDefaultClassDefinition($avatarManagerClassName);
		$this->addEventsCapableClassDefinition($classDefinition);
		$definitionList->addDefinition($classDefinition);

		$fieldManagerClassName = $this->getInjectedClassName('FieldManager', 'Rbs\Simpleform\Field\FieldManager');
		$fieldClassDefinition = $this->getDefaultClassDefinition($fieldManagerClassName);
		$definitionList->addDefinition($fieldClassDefinition);

		$securityManagerClassName = $this->getInjectedClassName('SecurityManager', 'Rbs\Simpleform\Security\SecurityManager');
		$securityClassDefinition = $this->getDefaultClassDefinition($securityManagerClassName);
		$definitionList->addDefinition($securityClassDefinition);

		parent::__construct($definitionList);
		$im = $this->instanceManager();

		$defaultParameters = array('application' => $this->getApplication(),
			'applicationServices' => $this->getApplicationServices(),
			'eventManagerFactory' => $this->getEventManagerFactory());

		$im->addAlias('SeoManager', $seoManagerClassName, $defaultParameters);
		$im->addAlias('AvatarManager', $avatarManagerClassName, $defaultParameters);
		$im->addAlias('FieldManager', $fieldManagerClassName, $defaultParameters);
		$im->addAlias('SecurityManager', $securityManagerClassName, $defaultParameters);
	}

	/**
	 * @api
	 * @return \Rbs\Seo\SeoManager
	 */
	public function getSeoManager()
	{
		return $this->get('SeoManager');
	}

	/**
	 * @api
	 * @return \Rbs\Media\Avatar\AvatarManager
	 */
	public function getAvatarManager()
	{
		return $this->get('AvatarManager');
	}

	/**
	 * @return \Rbs\Simpleform\Field\FieldManager
	 */
	public function getFieldManager()
	{
		return $this->get('FieldManager');
	}

	/**
	 * @return \Rbs\Simpleform\Security\SecurityManager
	 */
	public function getSecurityManager()
	{
		return $this->get('SecurityManager');
	}
}