<p align="center"><img width="150" src="https://raw.githubusercontent.com/cstudiossro/craft-smtpmailer/main/src/icon.svg"></p>

# SMTP Mailer Plugin for Craft CMS

> We've built this plugin for internal use, but we've decided to make it public

SMTP Mailer provides additional options to send out mails compared to Pixel&Tonic`s Contact Form.
However you have to programatically set up everything. There is no Control Panel interface for this plugin.

> Make sure to carefully read the instructions before you use this plugin.

## Instructions

Add these lines to your `.env` file
```dotenv
MAILER_HOST=smtp.example.com
MAILER_USERNAME=login@example.com
MAILER_PASSWORD=********
MAILER_PORT=465
MAILER_ENCRYPTION=ssl
MAILER_DELIVERY_PROTOCOL=smtp
```

Obviously these are just placeholders, you need your own smtp configuration to make this work.

Create a `_mails` directory under your `templates`
Notice the underscore > `_`

`templates/_mails`

Create a `email_template_config.php` file inside your config folder > `config/email_template_config.php`
and copy the contents of this [file](https://raw.githubusercontent.com/cstudiossro/smtpmailer/main/email_template_config.php)
into that

## How do these templates work?

`templates/_mails` contains your email templates. These will be sent out to the email addresses.
The files inside this directory will get the variables you will set up on the contact pages.

Example:

You have a job application form (e.g. here: `templates/pages/contact.twig`). 
You might want to get the applicants age

If you use this code: 
```html
<label for="job">How old are you</label>
<input id="job" type="text" name="applicant_age">
```

Then you can use this variable inside your email template (That you might set up here `templates/_mails/job_contact.twig`)
like this: 
```html
<p>Applicant's age: {{ applicant_age }}</p>
```

This is depends on what you've entered into your input field's name attribute. (```name="variable_name"```)

## Where will we send the email?

First of all, it is important to know that the email template is not just a file name but an identifier as well.
So if you have a `templates/_mails/job_contact.twig` file, your ID for this template is `job_contact`

On you contact page you will need to place this row
```twig
{{ craft.email.templateInput('job_contact') | raw }}
```

This will tell 2 things to this plugin 
- We have a `templates/_mails/job_contact.twig` file
- We have a `job_contact` key inside the `config/email_template_config.php` file

Now this file is important. Here you actually refer to the functions specified in PHPmailer. 
You use keys to refer to the methods, and values to fill out the data.
I left an example in this repo: https://raw.githubusercontent.com/cstudiossro/smtpmailer/main/email_template_config.php

## How to use google recaptcha?
Add this line to the .env file (you will need the recaptcha secret):
```dotenv
RECAPTCHA_SECRET=""
```

Put these line into your `email_template_config.php` file

```php
    'test_template' => [
        //...
        'recaptcha' => [
            'setExpectedHostname' => ['example.com'],
            'setExpectedAction' => ['homepage'],
            'setScoreThreshold' => ['0.5']
        ],
        //...
    ],
```

After that, place this line into your form
```twig
<div class="g-recaptcha" data-sitekey="{{ gRecaptchaSitekey }}"></div>
```

## What happens if recaptcha could not authenticate the user
The page will return you to the email form, but you can retrieve the recaptcha error codes as follows:
```twig
{% set recaptchaErrorCodeString = craft.app.session.getFlash('recaptchaErrorCodes',null, true) $}
```
Error codes are separated by pipe, e.g.: missing-input-secret|missing-input-response <br>
In twig 2 and 3, you can split it with this: https://twig.symfony.com/doc/3.x/filters/split.html

```twig
{% set recaptchaErrorCodes = recaptchaErrorCodeString|split('|') %}
```
List of all the possible recaptcha error codes: https://developers.google.com/recaptcha/docs/verify#error_code_reference

## How to generate a contact form?

```twig
<form action="/email/send" method="post">

    {# necessary fields #}
    {{ csrfInput() }}
    {{ redirectInput('/') }}
    {{ craft.email.templateInput('test_template') | raw }}

    {# custom fields #}
    <label for="">What's your name?</label>
    <input name="name" type="text">

    <label for="">Which job are you applying for?</label>
    <input name="job" type="text">

    <label for="">What's your email address</label>
    <input name="email" type="text">

    <button>Send</button>
</form>
``` 

## How to generate email template?

```html
<p>You've received a new job application:</p>
<p>Name: {{ name }}</p>
<p>Job in question: {{ job }}</p>
<p>Applicant's email address: {{ email }}</p>
```

## How to use sendmail (instead of SMTP)?
Go into the `.env` file and copy the delivery_protocol line here:
 
```dotenv
MAILER_DELIVERY_PROTOCOL=sendmail
```

## How to send static files:

In your `email_template_config.php` file:

```php
'addAttachment' => [
    Craft::getAlias('@webroot/photo.jpg')
],
```

The `@webroot` alias points towards your /web folder
> @webroot provides an absolute file system path, while @web only gives you a relative web path

## How to send files from a form?

```html
<label for="">File 1</label>
<input type="file" name="attachments[]">

<label for="">File 2</label>
<input type="file" name="attachments[]">
```

## How do I send an autoreply email?

> Autoreply will go to the person who filled out your contact form

Paste these lines into your `email_template_config.php`, inside the template you want to have autoreply

```php
'test_template' => [
    //...
    'replyConfig' => [
        'setFrom' => [
            //$mail->setFrom('from@example.com', 'From');
            ['from@example.com', 'From']
        ],
        //The name of the input field that stores the mail address to which the mail will go
        'setReplyInputName' => ['applicantEmailAddress'],
        //The name of the template, put it here: templates/_mails/
        'setReplyTemplate' => ['test_reply_template'],
    ],
    //...
]
```

On the contact form:
```html
<label for="">My email address</label>
<input name="applicantEmailAddress" type="text">
```

