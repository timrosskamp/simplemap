<?php
/**
 * Maps for Craft CMS
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\maps;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use ether\maps\enums\GeoService;
use ether\maps\enums\MapTiles;
use ether\maps\fields\MapField as MapField;
use ether\maps\integrations\craftql\GetCraftQLSchema;
use ether\maps\integrations\feedme\FeedMeMaps;
use ether\maps\models\Settings;
use ether\maps\services\MapService;
use ether\maps\web\Variable;
use yii\base\Event;

/**
 * Class Maps
 *
 * @author  Ether Creative
 * @package ether\maps
 * @property MapService $map
 */
class Maps extends Plugin
{

	// Properties
	// =========================================================================

	public $hasCpSettings = true;

	// Craft
	// =========================================================================

	public function init ()
	{
		parent::init();

		\Craft::setAlias(
			'mapsimages',
			__DIR__ . '/web/assets/imgs'
		);

		$this->setComponents([
			'map' => MapService::class,
		]);

		Event::on(
			Fields::class,
			Fields::EVENT_REGISTER_FIELD_TYPES,
			[$this, 'onRegisterFieldTypes']
		);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			[$this, 'onRegisterVariable']
		);

		if (class_exists(\markhuot\CraftQL\CraftQL::class))
		{
			Event::on(
				MapField::class,
				'craftQlGetFieldSchema',
				[new GetCraftQLSchema, 'handle']
			);
		}

		if (class_exists(\craft\feedme\Plugin::class))
		{
			Event::on(
				\craft\feedme\services\Fields::class,
				\craft\feedme\services\Fields::EVENT_REGISTER_FEED_ME_FIELDS,
				[$this, 'onRegisterFeedMeFields']
			);
		}
	}

	// Settings
	// =========================================================================

	protected function createSettingsModel ()
	{
		return new Settings();
	}

	/**
	 * @return string|null
	 * @throws \Twig\Error\LoaderError
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 */
	protected function settingsHtml ()
	{
		return \Craft::$app->getView()->renderTemplate(
			'simplemap/settings',
			[
				'settings' => $this->getSettings(),
				'mapTileOptions' => MapTiles::getSelectOptions(),
				'geoServiceOptions' => GeoService::getSelectOptions(),
			]
		);
	}

	// Events
	// =========================================================================

	public function onRegisterFieldTypes (RegisterComponentTypesEvent $event)
	{
		$event->types[] = MapField::class;
	}

	/**
	 * @param Event $event
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	public function onRegisterVariable (Event $event)
	{
		/** @var CraftVariable $variable */
		$variable = $event->sender;
		$variable->set('simpleMap', Variable::class);
		$variable->set('maps', Variable::class);
	}

	public function onRegisterFeedMeFields (\craft\feedme\events\RegisterFeedMeFieldsEvent $event)
	{
		$event->fields[] = FeedMeMaps::class;
	}

	// Helpers
	// =========================================================================

	public static function t ($message, $params = [])
	{
		return \Craft::t('simplemap', $message, $params);
	}

}