<?php

namespace Core\Model\Utils;

use Core\Utils\Config;
use Core\Utils\Exception;

class Mail {

    private $mail = null;

    private $config = array();

    private $fromEmail = '';

    private $fromName = '';

    public function __construct(){
        $config = Config::getInstance();
        $this->config = $config->get('mail');

        $this->fromEmail = $this->config['username'];
        if( array_key_exists('from_name', $this->config) ) $this->fromName = $this->config['from_name'];
    }

    private function getSender(){
        if( $this->mail == null ){
            $this->mail = new \PHPMailer();
            $this->mail->Host = $this->config['host'];
            $this->mail->Port = $this->config['port'];
            $this->mail->From = $this->config['username'];
            $this->mail->FromName = $this->config['from_name'];
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];

            if( array_key_exists('timeout', $this->config) ) $this->mail->Timeout = $this->config['timeout'];

            $this->mail->isSMTP();
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPSecure = 'tls';
            if( array_key_exists('protocol', $this->config) ) $this->mail->Mailer = $this->config['protocol'];
            if( array_key_exists('smtp_auth', $this->config) ) $this->mail->SMTPAuth = $this->config['smtp_auth'];
            if( array_key_exists('smtp_secure', $this->config) ) $this->mail->SMTPSecure = $this->config['smtp_secure'];

            return $this->mail;
        }
        return $this->mail;
    }

    /**
     * send an email
     * @param array $from           who is sending ("email"=>"", "name"=>"")
     * @param array $to             array of recipients ( array("email"=>"", "name"=>"") )
     * @param string $subject
     * @param string $message       HTML text
     * @param array $attachments    array of files to be attached
     * @return bool
     */
    public function send($from, $to, $subject, $message, $attachments = array()){
        $mail = $this->getSender();
        try{
            // Recipients
            if( $from != null ){
                $mail->setFrom($from['email'], $from['name']);
            }
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            foreach($to as $recipient){
                $mail->addAddress($recipient['email'], $recipient['name']);
            }

            // Content
            $mail->CharSet = 'utf-8';
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            if( count($attachments) ){
                foreach($attachments as $attachment){
                    $mail->addAttachment($attachment);
                }
            }

            $mail->send();
            $this->mail = null;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private $data = array();
    private $header = true;
    private $footer = true;
    private $signature = true;

    public function sendTwig($to, $subject, $template = 'new_mail.twig', $projectFolder = null){
        $mail = $this->getSender();

        if( isset($to) ) $mail->addAddress($to);
        $mail->Subject = $subject;

        $this->data['header'] = $this->header;
        $this->data['footer'] = $this->footer;
        $this->data['signature'] = $this->signature;
        $this->data['hostname'] = gethostname();

        // We prepare the html for the e-mail
        $response = new \Core\View\Response\Mail('Mail/'.$template, $projectFolder);
        $response->initResponse($this->data);

        $mail->Body = StringUtils::replaceAccents( $response->get() );
        $mail->AltBody = strip_tags($mail->Body);
        $success = $mail->Send();

        $tries=1;
        while( (!$success) && ($tries < 5) ){
            sleep(5);
            $success = $mail->Send();
            $tries++;
        }

        //TODO keep some kind of log of what we sent.
        $this->mail = null;
        if( !$success ){
            echo $mail->ErrorInfo;
            return false;
        }
        else return true;
    }
    /**
     * Adds an attachment to the mail.
     * @param string $path      with the path to the file to attach.
     * @param string $name      containing the name shown as attached file.
     */
    public function addAttachment($path,$name){
        $mail = $this->getSender();
        $mail->AddAttachment($path,$name);
    }

    public function addAddress($to){
        $mail = $this->getSender();
        $mail->AddAddress($to);
    }

    /**
     * Assigns variables to be shown on the mail
     * @param string $var_name  containing the name of the variable.
     * @param mixed $value      the value of the variable
     */
    public function assign($var_name, $value){
        $this->data[$var_name] = $value;
    }

}