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
    protected $name = '';

    /**
     * email account
     * @var string
     */
    protected $username = '';

    /**
     * password account
     * @var string
     */
    private $password = '';
    
    /**
     * timeout php mailer
     * @var string
     */
    private $timeout = 30;

    public function __construct(){
        $config = Config::getInstance();
        $mailConfig = $config->get('mail');

        $this->host = $mailConfig['host'];
        $this->port = $mailConfig['port'];
        $this->name = $mailConfig['name'];
        $this->username = $mailConfig['username'];
        $this->password = $mailConfig['password'];
        if( $mailConfig['timeout'] ){
            $this->timeout = $mailConfig['timeout'];
        }
    }

    /**
     * send an email
     * @param $from             array ("email"=>"", "name"=>"")
     * @param $to               array( array("email"=>"", "name"=>"") )
     * @param $subject          string
     * @param $message          string HTML
     * @return bool
     */
    public function send($from, $to, $subject, $message, $attach = null){
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
            $mail->Timeout = $this->timeout;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            if( $from == null ){
                $from = array(
                    'name' => $this->name,
                    'email' => $this->username
                );
            }
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
            if( $attach && $attach != null ){
                $mail->addAttachment($attach);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            //echo 'Mailer Error: ' . $mail->ErrorInfo;
            return false;
        }
    }

}