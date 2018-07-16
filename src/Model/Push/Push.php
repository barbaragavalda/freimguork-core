<?php

namespace Core\Model\Push;

use Core\Model\Utils\Mail;
use Core\Utils\Config;

class Push {

    /**
     * @var string  deep linking protocol
     */
    protected $urlScheme = '';


    /**
     * @var string  email to sent the report
     */
    private $reportEmail = '';

    public function __construct(){
        // url scheme
        $config = Config::getInstance();
        $webserviceConfig = $config->get('webservice');
        $this->urlScheme = $webserviceConfig['url_scheme'];
        $this->reportEmail = $config->get('push', 'report');
    }

    /**
     * send push notification
     * @param $platforms
     * @param $message
     * @param string $deepLink
     */
    public function send($platforms, $message, $deepLink = ''){
        $urlScheme = '';
        if( $deepLink != '' ) $urlScheme = $this->urlScheme . $deepLink;

        $android = $ios = array();
        foreach($platforms as $platform){
            $tokens = explode(',', $platform['tokens']);
            switch( $platform['name'] ){
                case 'android': $android = $tokens; break;
                case 'ios':     $ios = $tokens;     break;
            }
        }

        $emailMessage = '<p><b>Texto: </b>' . $message . '</p>';
        if( count($android) ){
            if( IS_DEV ) {
                echo '<p>Fake push notification for Android: <b>' . $message . '</b></p>';
            }else{
                $pushAndroid = new Android($message, $android, $urlScheme);
                $pushAndroid->send();
                $pushAndroid->close();


                $emailMessage .= '<p><b>Android: </b>' . $pushAndroid->getEmailResult() . '</p>';
            }
        }

        if( count($ios) ){
            if( IS_DEV ){
                echo '<p>Fake push notification for iOS: <b>'.$message.'</b></p>';
            }else{
                $pushiOS = new iOS($message, $ios, $urlScheme);
                $pushiOS->send();
                $pushiOS->close();

                $emailMessage .= '<p><b>iOS: </b>' . $pushiOS->getEmailResult() . '</p>';
            }
        }

        if( $message && $this->reportEmail ){
            $email = new Mail();
            $email->send(
                null,
                array( array('email' => $this->reportEmail, 'name' => 'El Chollo Marcas') ),
                'Estadísticas notificaciones push',
                $emailMessage
            );
        }

    }

}