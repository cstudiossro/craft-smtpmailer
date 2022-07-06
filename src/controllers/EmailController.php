<?php

namespace cstudios\smtpmailer\controllers;

use Craft;
use craft\web\Controller;
use craft\web\UploadedFile;
use cstudios\smtpmailer\services\EmailService;
use cstudios\smtpmailer\services\RecaptchaService;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class EmailController extends Controller
{
    public array|int|bool $allowAnonymous = true;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionSend()
    {
        /** @var EmailService $emailService */
        $emailService = Craft::createObject(['class' => EmailService::class, 'loadFromDotEnv' => true]);
        $templateConfigs = require Craft::getAlias('@config/email_template_config.php');
        $templateConfig = null;

        $redirect = Craft::$app->security->validateData(Craft::$app->request->post('redirect'));
        $email_template = Craft::$app->security->validateData(Craft::$app->request->post('email_template'));
        $attachments = UploadedFile::getInstancesByName('attachments', false);

        if (!$email_template) {
            throw new NotFoundHttpException('Email template not found');
        }

        if (array_key_exists($email_template, $templateConfigs)) {
            $templateConfig = $templateConfigs[$email_template];
        }

        $allowEmailSending = true;

        if (ArrayHelper::keyExists('recaptcha', $templateConfig)) {
            $gRecaptchaResponse = Craft::$app->request->post('g-recaptcha-response');
            $remoteIp = Craft::$app->request->userIP;

            $allowEmailSending = false;
            /** @var RecaptchaService $recaptchaService */
            $recaptchaService = Craft::createObject(['class' => RecaptchaService::class]);
            $recaptchaService->create();
            $recaptchaService->configureRecaptchaFromTemplateConfig(ArrayHelper::getValue($templateConfig, 'recaptcha'));
            $response = $recaptchaService->verify($gRecaptchaResponse, $remoteIp);
            if ($response->isSuccess()) {
                $allowEmailSending = true;
            } else {
                $codes = $response->getErrorCodes();
                Craft::$app->session->setFlash('recaptchaErrorCodes', implode('|', $codes));
            }
        }

        if (!$allowEmailSending) {
            return $this->redirect(Craft::$app->request->referrer);
        }

        $emailService->create([]);
        $emailService->assignAttachments($attachments);
        $emailService->setBodyFromTemplate($email_template, Craft::$app->request->post());
        $emailService->configureMailFromTemplateConfig($templateConfig);
        $emailService->send();

        if (ArrayHelper::keyExists('replyConfig', $templateConfig)) {
            $emailService->purge();
            $emailService->create([]);
            $emailService->configureReply($templateConfig, Craft::$app->request->post());
            $emailService->send();
        }

        if ($redirect) {
            $this->redirect($redirect);
        }
    }
}