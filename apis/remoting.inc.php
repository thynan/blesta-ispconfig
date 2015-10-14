/******************************************************
* CUSTOM FUNCTIONS FOR BLESTA - This functions can be moved to an extra file in a remote.d folder,
* as soon as ispconfig 3.1 comes out. As of now, append them to the file /usr/local/ispconfig/interface/lib/classes/remoting.php
* Attention: will be overwritten on ISPConfig Upgrade!
********************************************************/

/**
* Gets the server configuration
* @param int session id
* @param string  server_type of the server. Could be 'web_server', 'dns_server', 'mail_server', db_server etc.
* @return  server names which fit the section
**/
 public function server_get_by_type($session_id, $server_type) {
    global $app;
    if(!$this->checkPerm($session_id, 'server_get')) {
        $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
        return false;
    }
    if (!empty($session_id)) {
            $sql = "SELECT server_name FROM server WHERE " . $server_type . " = 1";
            $servers = $app->db->queryAllRecords($sql);

            return $servers;
    } else {
            return false;
        }
    }

    //Returs ID of client template by name
    public function client_template_get_id_by_name($session_id, $template_name)  {
        global $app;
        if(!$this->checkPerm($session_id, 'server_get')) {
                $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
                return false;
        }
        $sql = "SELECT template_id FROM client_template WHERE template_name = '$template_name' LIMIT 1 ";
        $all = $app->db->queryAllRecords($sql);
        return $all;
    }
