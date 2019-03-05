<?php

namespace lukeyouell\queuemanager;

use lukeyouell\queuemanager\models\Settings;
use lukeyouell\queuemanager\utilities\Queue as QueueUtility;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Utilities;
use craft\web\UrlManager;

use Yii\base\Event;

class QueueManager extends Plugin
{
    // Static Properties
    // =========================================================================

    public static $plugin;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['queue-manager'] = 'queue-manager/cp/jobs';
                $event->rules['queue-manager/<status>'] = 'queue-manager/cp/jobs';
            }
        );

        // Register our utility type
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = QueueUtility::class;
            }
        );

        // Register components
        $this->setComponents([
            'queue' => \lukeyouell\queuemanager\services\QueueService::class,
        ]);
    }

    public function getCpNavItem()
    {
        $ret = parent::getCpNavItem();

        $ret['label'] = $this->name;
        $ret['badgeCount'] = $this->queue->getJobCount();

        return $ret;
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }
}
