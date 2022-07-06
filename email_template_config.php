<?php

/**
 * //Recipients
 * $mail->setFrom('from@example.com', 'Mailer');
 * $mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
 * $mail->addAddress('ellen@example.com');               // Name is optional
 * $mail->addReplyTo('info@example.com', 'Information');
 * $mail->addCC('cc@example.com');
 * $mail->addBCC('bcc@example.com');
 */

return [
    'test_template' => [
        'setFrom' => [
            //equal to $mail->setFrom('from@example.com', 'Mailer');
            ['from@example.com', 'Mailer']
        ],
        'replyConfig' => [
            'setFrom' => [
                //equal to $mail->setFrom('from@example.com', 'Mailer');
                ['from@example.com', 'Mailer']
            ],
            'setReplyInputName' => ['email'],
            'setReplyTemplate' => ['test_reply_template'],
        ],
        'recaptcha' => [
            //equal to $recaptcha->setExpectedHostname('example.com')
            'setExpectedHostname' => ['example.com'],
            'setExpectedAction' => ['homepage'],
            'setScoreThreshold' => ['0.5'],
        ],
        'addAddress' => [
            //equal to $mail->addAddress('joe@example.net', 'Joe');
            ['joe@example.net', 'Joe'],
            //name is optional; equal to $mail->addAddress('joe@example.net');
            //['joe@example.net']
        ],
        'addCC' => [
            //equal to $mail->addCC('cc@example.com');
            //['cc@example.com']
        ]
    ],
];