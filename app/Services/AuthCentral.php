<?php

namespace App\Services;

class AuthCentral
{


    private $url_auth;
    private $session_name;
    private $apiKey;
    private $secret;
    private $redirect_to;
    private $redirect_to_encoded;
    private $error = null;

    public function __construct($url_auth, $session_name, $apiKey, $secret, $redirect_to)
    {
        $this->url_auth = $url_auth;
        $this->session_name = $session_name;
        $this->apiKey = $apiKey;
        $this->secret = $secret;
        $this->redirect_to = $redirect_to;
        $this->redirect_to_encoded = urlencode($redirect_to);
    }

    private function getUrlLogin()
    {
        return "$this->url_auth?action=remoteAuthenticacion&service=$this->redirect_to_encoded&apiKey=$this->apiKey";
    }

    private function getUrlLogout()
    {
        return "$this->url_auth?action=remoteLogoutService&service=$this->redirect_to_encoded&apiKey=$this->apiKey";
    }

    private function getUrlCheckRemoteLogin()
    {
        return "$this->url_auth?action=checkRemoteLogin";
    }

    private function getUrlSimpleLogin()
    {
        return "$this->url_auth?action=simpleLogin";
    }


    public function logout()
    {
        session_destroy();
        header("Location: " . $this->getUrlLogout());
    }

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    public function simpleLogin($username, $password)
    {

        $params = array(
            'login' => array(
                'username' => $username,
                'password' => $password,
            ),
            'apiKey' => $this->apiKey,
            'secret' => $this->secret,
            'service' => $this->redirect_to_encoded
        );

        $request = $this->buildCurlRequest($this->getUrlSimpleLogin(), $params);

        $response = json_decode($request, true);
        if (isset($response['result']) && $response['result']) {

            $_SESSION[$this->session_name]['username'] = $response['userInfo']['username'];
            $_SESSION[$this->session_name]['logged_apps'][$this->apiKey] = true;
            $_SESSION[$this->session_name]['response'] = $response;

            return true;
        }
        return false;
    }

    public function isLogin()
    {

        if (isset($_SESSION[$this->session_name]['logged_apps'][$this->apiKey])) {
            return;
        }
        if (isset($_GET['ticketID']) && $_GET['ticketID']) {
            $this->processTicket($_GET['ticketID']);
        }
        if (isset($_GET['logout']) && $_GET['logout']) {
            $this->invalidate();
        }
        if (!$this->appLogged()) {
            header("Location: " . $this->getUrlLogin());
            exit;
        }
    }

    public function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    public function getUsername()
    {
        if (isset($_SESSION[$this->session_name]['username'])) {
            return $_SESSION[$this->session_name]['username'];
        }
        return false;
    }

    public function invalidate()
    {
        session_destroy();
        unset($_SESSION[$this->session_name]['logged_apps'][$this->apiKey]);
        $this->redirect($this->redirect_to);
    }

    /**
     * @return bool
     */
    public function appLogged()
    {
        if (isset($_SESSION[$this->session_name]['logged_apps'][$this->apiKey])
            && $_SESSION[$this->session_name]['logged_apps'][$this->apiKey]
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $url
     * @param $params
     * @return mixed
     */
    private function buildCurlRequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        $response = curl_exec($ch);
        rewind($verbose);
        if ($response === FALSE) {
            $this->error = curl_error($ch);

        }
        curl_close($ch);

        return $response;
    }

    /**
     * @param $ticketID
     */
    public function processTicket($ticketID)
    {
        $response = $this->buildCurlRequest($this->getUrlCheckRemoteLogin(), array(
            'secret' => $this->secret, 'ticketID' => $ticketID
        ));

        $result = json_decode($response, true);

        if (isset($result['result']) && $result['result']) {
            $_SESSION[$this->session_name]['username'] = $result['userInfo']['username'];
            $_SESSION[$this->session_name]['logged_apps'][$this->apiKey] = true;
            $this->redirect($this->redirect_to);
        } else {

            $warning = null;
            if(!$response){
                $warning = "Error communication with central login : " . $this->error;
            }elseif (!$result) {
                $warning = "Error fetching central login response: " .json_last_error_msg();
            } elseif (isset($result['error'])) {
                $warning = $result['error'];
            }
            $url = $this->getUrlLogin() . '&warning=' . $warning;
            $this->redirect($url);
        }
    }

    public function checkRemoteLogin($ticketID){
        $response = $this->buildCurlRequest($this->getUrlCheckRemoteLogin(), array(
            'secret' => $this->secret, 'ticketID' => $ticketID
        ));
        return $response;
    }
}