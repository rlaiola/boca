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

class LDAPManager {
    private LDAP\Connection $ldapConnection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->ldapConnection = ldap_connect(getenv('LDAP_SERVER'));

        if (!$this->ldapConnection) {
            throw new Exception('Error while connecting to LDAP server');
        }
        ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);
        
        $bindResult = ldap_bind($this->ldapConnection, getenv('LDAP_USER'), getenv('LDAP_PASSWORD'));
        
        if (!$bindResult) {
            throw new Exception('Error while authenticating with LDAP server');
        }
    }

    public function authenticateUser($name, $pass) {
        $searchResult = ldap_search($this->ldapConnection, getenv('LDAP_BASE_DN'), "(uid=$name)");
        $entries = ldap_get_entries($this->ldapConnection, $searchResult);

        if ($entries['count'] === 0) return false;

        $userDn = $entries[0]['dn'];

        return ldap_bind($this->ldapConnection, $userDn, $pass);
    }

    public function disconnect() {
        if ($this->ldapConnection) ldap_unbind($this->ldapConnection);
    }
}
?>
