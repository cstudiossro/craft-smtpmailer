<?php

namespace cstudios\smtpmailer;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use cstudios\smtpmailer\controllers\EmailController;
use cstudios\smtpmailer\services\EmailService;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public $id = 'smtpmailer';

    public $controllerMap = [
        'email' => EmailController::class
    ];

    /**
     * Initializes the module.
     */
    public function init()
    {
        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'cstudios\\smtpmailer\\console\\controllers';
        } else {
            $this->controllerNamespace = 'cstudios\\smtpmailer\\controllers';
        }

        parent::init();

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['email/send'] = 'smtpmailer/email/send';
            }
        );

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $e) {
            /** @var CraftVariable $variable */
            $variable = $e->sender;

            // registering the services
            $variable->set('email', EmailService::class);

        });


    }
}
