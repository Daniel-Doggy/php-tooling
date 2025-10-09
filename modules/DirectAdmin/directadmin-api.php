<?php

    class DirectAdminAPI {
        private array $directadmin_api_url = array();
        private array $directadmin_api_username = array();
        private array $directadmin_api_password = array();

        public function __construct(array $directadmin_login){
            if(empty($directadmin_login)){

            }

            foreach($directadmin_login as $credentials){
                if(!is_string($credentials["da_url"]) || empty($credentials["da_url"])){
                    continue;
                }
                if(!is_string($credentials["da_username"]) || empty($credentials["da_username"])){
                    continue;
                }
                if(!is_string($credentials["da_auth_key"]) || empty($credentials["da_auth_key"])){
                    continue;
                }

                $this->directadmin_api_url[] = $credentials["da_url"];
                $this->directadmin_api_username[] = $credentials["da_username"];
                $this->directadmin_api_password[] = $credentials["da_auth_key"];
            }

            if(empty($this->directadmin_api_url)){
                throw new Exception("No DirectAdmin credentials given");
            }

            foreach($this->directadmin_api_url as $key => $url){
                if($this->fetchData($url, $key, "CMD_API_LOGIN_TEST?json=yes", "GET", array())["response_code"] != 200){
                    throw new Exception("Invalid login credentials for key " . $key . ".");
                }
            }
        }

        public function setGitBranch(array $uuid, string $branch){
            $response = array();
            foreach($this->directadmin_api_url as $key => $url){
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $uuid[$key], "PUT", array("deploy_branch" => $branch));
            }
        }

        public function fetchGit(array $uuid){
            $response = array();
            foreach($this->directadmin_api_url as $key => $url){
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $uuid[$key] . "/fetch", "POST", array());
            }
        }

        public function deployGit(array $uuid){
            $response = array();
            foreach($this->directadmin_api_url as $key => $url){
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $uuid[$key].  "/deploy", "POST", array());
            }
            return $response;
        }

        private function fetchData(string $url, int $key, string $endpoint, string $type, array $data){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            if(!empty($data)){
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["accept: application/json", "content-type: application/json"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . base64_encode($this->directadmin_api_username[$key] . ":" . $this->directadmin_api_password[$key])));
            curl_setopt($ch, CURLOPT_URL, $url . "/" . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
            $responsedata = curl_exec($ch);
            $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return array("response_code" => $responsecode, "data" => json_decode($responsedata, true));
        }
    }

?>