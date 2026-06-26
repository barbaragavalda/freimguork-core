<?php

namespace Core\Model\Utils;

use Core\Utils\Config;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{

    private ?PHPMailer $mail = null;

    private array $config;

    protected string $fromEmail = '';

    protected string $fromName = '';

    public function __construct($configMail = null)
    {
        if ($configMail == null) {
            $config       = Config::getInstance();
            $this->config = $config->get('mail');
        } else {
            $this->config = $configMail;
        }

        $this->fromEmail = $this->config['username'];
        if (array_key_exists('from_name', $this->config)) {
            $this->fromName = $this->config['from_name'];
        }
    }

    private function getSender(): PHPMailer
    {
        if ($this->mail == null) {
            $this->mail           = new PHPMailer();
            $this->mail->Host     = $this->config['host'];
            $this->mail->Port     = $this->config['port'];
            $this->mail->From     = $this->config['username'];
            $this->mail->FromName = $this->fromName;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];

            if (array_key_exists('timeout', $this->config)) {
                $this->mail->Timeout = $this->config['timeout'];
            }

            $this->mail->isSMTP();
            $this->mail->SMTPAuth   = true;
            $this->mail->SMTPSecure = 'tls';
            if (array_key_exists('protocol', $this->config)) {
                $this->mail->Mailer = $this->config['protocol'];
            }
            if (array_key_exists('smtp_auth', $this->config)) {
                $this->mail->SMTPAuth = $this->config['smtp_auth'];
            }
            if (array_key_exists('smtp_secure', $this->config)) {
                $this->mail->SMTPSecure = $this->config['smtp_secure'];
            }
            if (array_key_exists('smtp_options', $this->config)) {
                $this->mail->SMTPOptions = $this->config['smtp_options'];
            }

            return $this->mail;
        }
        return $this->mail;
    }

    /**
     * send an email
     *
     * @param array  $from        who is sending ("email"=>"", "name"=>"")
     * @param array  $to          array of recipients ( array("email"=>"", "name"=>"") )
     * @param string $subject
     * @param string $message     HTML text
     * @param array  $attachments array of files to be attached
     *
     * @return bool
     */
    public function send(array $from, array $to, string $subject, string $message, array $attachments = array()): bool
    {
        $mail = $this->getSender();
        try {
            // Recipients
            if ($from != null) {
                $mail->setFrom($from['email'], $from['name']);
            }
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            foreach ($to as $recipient) {
                $mail->addAddress($recipient['email'], $recipient['name']);
            }

            // Content
            $mail->CharSet = 'utf-8';
            $mail->isHTML();
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);
            if (count($attachments)) {
                foreach ($attachments as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }

            $result     = $mail->send();
            $this->mail = null;
            return $result;
        } catch (Exception) {
            return false;
        }
    }

    private array $data      = array();
    private bool  $header    = true;
    private bool  $footer    = true;
    private bool  $signature = true;

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendTwig(string $subject, string $template = 'new_mail.twig', ?string $projectFolder = null): bool
    {
        $mail = $this->getSender();

        $mail->Subject = $subject;

        $this->data['header']    = $this->header;
        $this->data['footer']    = $this->footer;
        $this->data['signature'] = $this->signature;
        $this->data['hostname']  = gethostname();

        // We prepare the HTML for the e-mail
        $response = new \Core\View\Response\Mail('Mail/' . $template, $projectFolder);
        $response->initResponse($this->data);

        $mail->CharSet = 'utf-8';
        $mail->Body    = StringUtils::replaceAccents($response->get());
        $mail->AltBody = strip_tags($mail->Body);
        $success       = $mail->Send();

        $tries = 1;
        while ((!$success) && ($tries < 5)) {
            sleep(5);
            $success = $mail->Send();
            $tries++;
        }

        $this->mail = null;
        if (!$success) {
            echo $mail->ErrorInfo;
            return false;
        } else {
            return true;
        }
    }

    /**
     * Adds an attachment to the mail.
     *
     * @param string $path with the path to the file to attach.
     * @param string $name containing the name shown as attached file.
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function addAttachment(string $path, string $name): void
    {
        $mail = $this->getSender();
        $mail->AddAttachment($path, $name);
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function addAddress(string $to): void
    {
        $mail = $this->getSender();
        $mail->AddAddress($to);
    }

    /**
     * Assigns variables to be shown on the mail
     *
     * @param string $var_name containing the name of the variable.
     * @param mixed  $value    the value of the variable
     */
    public function assign(string $var_name, mixed $value): void
    {
        $this->data[ $var_name ] = $value;
    }

}