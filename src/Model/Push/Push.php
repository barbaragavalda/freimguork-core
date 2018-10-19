<?php

namespace Core\Model\Push;

use Appacman\Model\Push\Statistic;
use Core\Model\Model;
use Core\Model\Utils\Mail;
use Core\Utils\Config;

class Push extends Model {

    /**
     * @var string  app name
     */
    protected $appName = '';

    /**
     * @var string  deep linking protocol
     */
    protected $urlScheme = '';

    /**
     * @var string  email to sent the report
     */
    private $reportEmail = '';

    /**
     * @var bool    save push statistics
     */
    private $hasStatistics = false;

    public function __construct(){
        parent::__construct();

        // url scheme
        $config = Config::getInstance();
        $webserviceConfig = $config->get('webservice');
        $this->appName = $webserviceConfig['app_name'];
        $this->urlScheme = $webserviceConfig['url_scheme'];
        $this->reportEmail = $config->get('push', 'report');

        $this->hasStatistics = $this->mysql->tableExists('appacman_push_statistic');
    }

    /**
     * send push notification
     * @param $platforms
     * @param $message
     * @param string $deepLink
     * @param int $pushID
     */
    public function send($platforms, $message, $deepLink = '', $pushID = null){
        if( $pushID && $this->hasStatistics ){
            $this->id = $pushID;
            if( $deepLink && strpos($deepLink, '?') !== false ){
                $deepLink .= '&';
            }else{
                $deepLink .= '?';
            }
            $deepLink .= 'push_id=' . $this->id;
        }

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

        $devices = 0;
        $emailMessage = '<p><b>Texto: </b>' . $message . '</p>';
        if( count($android) ){
            if( IS_DEV ) {
                echo '<p>Fake push notification for Android: <b>' . $message . '</b></p>';
            }else{
                $pushAndroid = new Android($message, $android, $urlScheme);
                $pushAndroid->send();
                $pushAndroid->close();

                $devices += $pushAndroid->getOk();
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

                $devices += $pushiOS->getOk();
                $emailMessage .= '<p><b>iOS: </b>' . $pushiOS->getEmailResult() . '</p>';
            }
        }

        $this->statistics($message, $emailMessage, $devices);
    }

    private function statistics($message, $emailMessage, $devices){
        if( $message && $this->reportEmail ){
            $email = new Mail();
            $email->send(
                null,
                array( array('email' => $this->reportEmail, 'name' => $this->appName) ),
                'Estadísticas notificaciones push',
                $emailMessage
            );
        }

        if( $this->hasStatistics ){
            $statistics = new Statistic($this->id);
            $statistics->update(0, $devices);
        }
    }

}