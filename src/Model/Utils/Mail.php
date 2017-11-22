<?php

namespace Core\Model\Utils;

use Core\Utils\Config;
use Core\Utils\Exception;

class Mail {

    /**
     * SMTP host
     * @var string
     */
    private $host = '';

    /**
     * SMTP port
     * @var int
     */
    private $port = 0;

    /**
     * display name
     * @var string
     */
    private $name = '';

    /**
     * email account
     * @var string
     */
    private $username = '';

    /**
     * password account
     * @var string
     */
    private $password = '';

    public function __construct(){
        $config = Config::getInstance();
        $mailConfig = $config->get('mail');

        $this->host = $mailConfig['host'];
        $this->port = $mailConfig['port'];
        $this->name = $mailConfig['name'];
        $this->username = $mailConfig['username'];
        $this->password = $mailConfig['password'];
    }

    /**
     * send an email
     * @param array $from       array("email"=>"", "name"=>"")
     * @param array $to         array( array("email"=>"", "name"=>"") )
     * @param string $subject
     * @param HTML $message
     * @return bool
     */
    public function send($from, $to, $subject, $message){
        $mail = new \PHPMailer();
        try{
            // Server settings
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Host = $this->host;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->Port = $this->port;

            // Recipients
            $mail->setFrom($from['email'], $from['name']);
            $mail->addReplyTo($this->username, $this->name);
            foreach($to as $recipient){
                $mail->addAddress($recipient['email'], $recipient['name']);
            }

            // Content
            $mail->CharSet = 'utf-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();
            return true;
        } catch (Exception $e) {
            //echo 'Mailer Error: ' . $mail->ErrorInfo;
            return false;
        }
    }

}