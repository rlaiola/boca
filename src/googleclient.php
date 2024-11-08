<?php

//========================================================================
// Copyright Universidade Federal do Espirito Santo (Ufes)
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
// 
// This program is released under license GNU GPL v3+ license.
//
//========================================================================

require_once('globals.php');
require '../vendor/autoload.php';

use Google\Client;
use Google\Service\Oauth2 as ServiceOauth2; 
use Google\Service\Oauth2\Userinfo;
use GuzzleHttp\Client as GuzzleClient;

class GoogleClient {

    public Client $client;
    public UserInfo $data;

    /**
     * @throws \Google\Exception
     */
    public function __construct() {
        $this->client = new Client();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));

        $httpStr = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https://" : "http://";

        $this->client->setRedirectUri($httpStr . $_SERVER['HTTP_HOST'] . '/boca/index.php');
        $this->client->addScope('email');
        $this->client->addScope('profile');

        $guzzleClient = new GuzzleClient(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $this->client->setHttpClient($guzzleClient);
    }

    public function generateAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authorized(): bool
    {
        if (isset($_GET['code'])) {
            $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            $this->client->setAccessToken($token['access_token']);
            $googleService = new ServiceOauth2($this->client);

            $this->data = $googleService->userinfo->get();

            return $this->data->verifiedEmail;
        }
        return false;
    }

    public function logout($token=null): void
    {
        try {
            if ($token) {
                $this->client->revokeToken($token);
            } else {
                $this->client->revokeToken();
            }
        } catch (Exception $e) {
            throw new Exception('Error while revoking token: '. $e->getMessage());
        }
    }
}

?>