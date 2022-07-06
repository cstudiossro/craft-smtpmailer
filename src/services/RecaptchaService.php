<?php

namespace cstudios\smtpmailer\services;

use craft\helpers\ArrayHelper;
use yii\base\Component;
use ReCaptcha\ReCaptcha;

class RecaptchaService extends Component
{
    /**
     * @var ReCaptcha|null $_recaptcha
     */
    private $_recaptcha;

    public function create(){
        $secret = getenv('RECAPTCHA_SECRET');
        $this->_recaptcha = new \ReCaptcha\ReCaptcha($secret);
    }

    /**
     * @return array
     */
    public function configMethods()
    {
        return [
        ];
    }

    public function configureRecaptchaFromTemplateConfig($templateConfig)
    {
        foreach ($templateConfig as $key => $config) {
            $this->configure($key, $templateConfig, function ($value) use ($key) {
                if (is_array($value)) {
                    if (method_exists($this->_recaptcha, $key)) {
                        call_user_func_array([$this->_recaptcha, $key], $value);
                    } else if (method_exists($this, $key) && in_array($key,$this->configMethods())) {
                        call_user_func_array([$this, $key], $value);
                    }
                } else {
                    $this->_recaptcha->$key($value);
                }
            });
        }
    }

    public function verify($gRecaptchaResponse, $remoteIp){
        return $this->_recaptcha->verify($gRecaptchaResponse,$remoteIp);
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

    public function purge(){
        $this->_recaptcha = null;
    }
}