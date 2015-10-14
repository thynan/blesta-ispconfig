<?php
/**
 * Created by PhpStorm.
 * User: Leonhard
 * Date: 10.11.2014
 * Time: 14:38
 */
//TODO: implement logging
//for now: file_put_contents('../private/testlog.txt', "this is a logout log",FILE_APPEND );
class IspconfigApi {

    // The host to connect to
    private $host				=	'127.0.0.1';

    // username to authenticate as
    private $user				= null;

    // literal strings hash or password
    private $auth_type 	= null;

    //  the actual password or hash
    private $auth 			= null;

    /**
     * @return null
     */
    public function getAuth()
    {
        return $this->auth;
    }

    // literal strings hash or password
    private $soap_location 	= null;

    //  the actual password or hash
    private $soap_uri 			= null;

    private $soap_client = null;

    private $session_id = null;

    private $log_group = null;

    /**
     * Instantiate the API Object
     * @param string $host The host to perform queries on
     * @param string $user The username to authenticate as
     * @param string $password The password to authenticate with
     * @return api object
     */
    public function __construct($host = null, $user = null, $password = null, $soap_location = null, $soap_uri = null )
    {
        if ( ( $user != null ) && ( strlen( $user ) < 9 ) ) {
            $this->user = $user;
        }
        if ($password != null) {
            $this->set_password($password);
        }

        // Set the host, error if not defined
        if ($host == null) {
            if ( (defined('XMLAPI_HOST')) && (strlen(XMLAPI_HOST) > 0) ) {
                $this->host = XMLAPI_HOST;
            } else {
                throw new Exception("No host defined");
            }
        } else {
            $this->host = $host;
        }

        if ($soap_location != null && $soap_uri != null) {
            $this->set_soap($soap_location, $soap_uri);
            $this->set_soap_client($soap_location, $soap_uri);
        }
        else{
            throw new Exception("Soap Parameters (uri, location) not defined");
        }
    }


    /**
     * Closes SOAP session upon destruction of ispconfig_api object
     */
    function __destruct () {
        if($this->soap_client->logout($this->session_id)){
            //log something?
        }
    }

    /**
     * Set the password to be autenticated with
     *
     * This will set the password to be authenticated with, the auth_type will be automatically adjusted
     * when this function is used
     *
     * @param string $pass the password to authenticate with
     * @see set_hash()
     * @see set_auth_type()
     * @see set_user()
     */
    public function set_password( $pass )
    {
        $this->auth_type = 'pass';
        $this->auth = $pass;
    }

    public function set_soap($soap_location, $soap_uri){
        $this->soap_location = $soap_location;
        $this->soap_uri = $soap_uri;
    }

    public function set_soap_client($soap_location, $soap_uri){
        $this->soap_client = new SoapClient(null, array('location' => $soap_location,
            'uri'      => $soap_uri,
            'trace' => 1,
            'exceptions' => 1));
    }


    public function soap_login(){
        try {
            $this->session_id = $this->soap_client->login($this->user, $this->auth);
        }
        catch (Exception $e) {
            // Nothing to do
        }
    }

    /**
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param null|string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return null|string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return null
     */
    public function getSoapClient()
    {
        return $this->soap_client;
    }

    /**
     * @return null
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    public function clientAdd($reseller, $params, $web_php_options = 'no,fast-cgi,cgi', $ssh_chroot = 'no', $usertheme = 'default'){

        //adding secondary dnsserver not supportet via ISPConfig remoting api atm. primary dns will be set for both dnsservers
       // unset($params['default_dnsserver_secondary']);
        $params['web_php_options'] = $web_php_options;
        $params['ssh_chroot'] = $ssh_chroot;
        $params['usertheme'] = $usertheme;



        return $this->soap_client->client_add($this->session_id, $reseller, $params);
    }


    public function dns_add_default_zone ( $client_id, $template_id, $domain, $ip, $ns1, $ns2, $email) {

        $this->soap_client->dns_templatezone_add($this->session_id, $client_id, $template_id, $domain, $ip, $ns1, $ns2, $email );
    }

}