<?php

namespace cstudios\smtpmailer\services;

use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\web\UploadedFile;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use yii\web\HttpException;

/**
 * Class EmailService
 * @package services
 *
 * @property null|PHPMailer $mail
 */
class EmailService extends Component
{
    const EVENT_BEFORE_CREATE = 'beforeCreate';

    const EVENT_AFTER_CREATE = 'afterCreate';

    const EVENT_BEFORE_PURGE = 'beforePurge';

    const EVENT_AFTER_PURGE = 'afterPurge';

    public $replyTemplate = null;

    public $replyInputName = false;

    public $loadFromDotEnv = false;

    /**
     * @var PHPMailer|null $_mail
     */
    private $_mail;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();
    }

    public function configMethods()
    {
        return [
            'setReplyInputName',
            'setReplyTemplate',
        ];
    }

    public function setReplyInputName($replyInputName){
        $this->replyInputName = $replyInputName;
    }

    public function setReplyTemplate($replyTemplate){
        $this->replyTemplate = $replyTemplate;
    }

    /**
     * @param array $config
     * @param bool $debug
     * @return EmailService
     * @throws \Exception
     */
    public function create($config = [], $debug = false)
    {
        if ($this->_mail) {
            throw new \Exception('Please use the purge method before creating another mail');
        }

        $this->trigger(self::EVENT_BEFORE_CREATE);

        $mail = new PHPMailer(true);

        $mail->SMTPDebug = $debug ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;

        if ($this->loadFromDotEnv) {

            $useSendmail = getenv('MAILER_DELIVERY_PROTOCOL') == 'sendmail';

            if ($useSendmail) {

                $mail->isSendmail();

            } else {

                $mail->isSMTP();
                $mail->Host = getenv('MAILER_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAILER_USERNAME');
                $mail->Password = getenv('MAILER_PASSWORD');
                $mail->SMTPSecure = getenv('MAILER_ENCRYPTION');
                $mail->Port = getenv('MAILER_PORT');

            }

        } else {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
        }

        $this->_mail = $mail;

        $this->trigger(self::EVENT_AFTER_CREATE);

        return $this;
    }

    /**
     * @param UploadedFile[] $attachments
     */
    public function assignAttachments($attachments)
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->tempName;
            $name = $attachment->name;
            $this->_mail->addAttachment($path, $name);
        }
    }

    public function configureReply($templateConfig, $variables){
        $replyConfig = [];

        if (ArrayHelper::keyExists('replyConfig', $templateConfig)){
            $replyConfig = $templateConfig['replyConfig'];
        }

        foreach ($replyConfig as $key => $config){
            $this->configureMailFromTemplateConfig($replyConfig);
        }

        $address = ArrayHelper::getValue($variables,$this->replyInputName);
        $template = $this->replyTemplate;

        if (!$address){
            throw new HttpException('Address can not be empty');
        }

        if (!$template){
            throw new HttpException('Missing template');
        }

        $this->_mail->addAddress($address);
        $this->setBodyFromTemplate($template, $variables);
    }

    public function configureMailFromTemplateConfig($templateConfig)
    {
        foreach ($templateConfig as $key => $config) {
            $this->configure($key, $templateConfig, function ($value) use ($key) {
                if (is_array($value)) {
                    if (method_exists($this->_mail, $key)) {
                        call_user_func_array([$this->_mail, $key], $value);
                    } else if (method_exists($this, $key) && in_array($key,$this->configMethods())) {
                        call_user_func_array([$this, $key], $value);
                    }
                } else {
                    $this->_mail->$key($value);
                }
            });
        }
    }

    public function configure($key, $templateConfig, $callback)
    {
        if (ArrayHelper::keyExists($key, $templateConfig)) {
            if (is_array($templateConfig[$key])) {
                foreach ($templateConfig[$key] as $value) {
                    $callback($value);
                }
            } else {
                $callback($templateConfig[$key]);
            }
        }
    }

    public function setBodyFromTemplate($template, $variables)
    {
        $this->_mail->Body = Craft::$app->view->renderTemplate('/_mails/', $template, $variables);
        $this->_mail->isHTML(true);
        return $this;
    }

    /**
     * @param PHPMailer|null $mail
     * @return EmailService
     * @throws Exception
     */
    public function send($mail = null)
    {

        if ($mail && $mail instanceof PHPMailer) {
            $mail->send();
        }

        $this->_mail->send();

        return $this;
    }

    /**
     *
     */
    public function purge()
    {
        $this->trigger(self::EVENT_BEFORE_PURGE);
        $this->_mail = null;
        $this->trigger(self::EVENT_AFTER_PURGE);
    }

    /**
     * @return PHPMailer|null
     */
    public function getMail()
    {
        return $this->_mail;
    }

    /**
     * @param PHPMailer|null $mail
     */
    public function setMail($mail)
    {
        $this->_mail = $mail;
    }

    public function templateInput($template)
    {
        $template = Craft::$app->security->hashData($template);
        return Html::hiddenInput('email_template', $template);
    }


}