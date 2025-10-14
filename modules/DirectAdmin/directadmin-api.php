<?php
    /*
    MIT License

    Copyright (c) 2025 Daniel-Doggy

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
    */

    class DirectAdminAPI {
        private array $directadmin_api_url = array();
        private array $directadmin_api_username = array();
        private array $directadmin_api_password = array();

        public function __construct(array $directadmin_login){
            if(empty($directadmin_login)){
                throw new Exception("No DirectAdmin credentials given");
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
                    throw new Exception("Invalid login credentials for " . $url . ".");
                }
            }
        }

        public function setGitBranch(array $domains, string $repo_name, string $branch){
            $response = array();
            $git_uuids = $this->getGitUUID($domains, $repo_name, $branch);
            foreach($this->directadmin_api_url as $key => $url){
                if(empty($git_uuids[$key])){
                    continue;
                }
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $git_uuids[$key], "PUT", array("deploy_branch" => $branch));
            }
            return $response;
        }

        public function fetchGit(array $domains, string $repo_name){
            $response = array();
            $git_uuids = $this->getGitUUID($domains, $repo_name, "");
            foreach($this->directadmin_api_url as $key => $url){
                if(empty($git_uuids[$key])){
                    continue;
                }
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $git_uuids[$key] . "/fetch", "POST", array());
            }
            return $response;
        }

        public function deployGit(array $domains, string $repo_name){
            $response = array();
            $git_uuids = $this->getGitUUID($domains, $repo_name, "");
            foreach($this->directadmin_api_url as $key => $url){
                if(empty($git_uuids[$key])){
                    continue;
                }
                $response[$key] = $this->fetchData($url, $key, "api/git/uuid/" . $git_uuids[$key] .  "/deploy", "POST", array());
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

        private function getGitUUID(array $domains, string $repo_name, string $branch){
            $git_uuids = array();
            foreach($this->directadmin_api_url as $key => $url){
                $records = $this->fetchData($url, $key, "api/git/domain/" . $domains[$key], "GET", array());
                if($records["response_code"] != 200){
                    echo "Failed to get git domain records for '" . $domains[$key] . "' from " . $url . "\n";
                    continue;
                }

                $record_id = array_search($repo_name, array_column($records["data"], "name"));
                if($record_id === false){
                    echo "Failed to get git repository '" . $repo_name . "' for '" . $domains[$key] . "' from " . $url . "\n";
                    continue;
                }

                if(!empty($branch)){
                    if(!is_array($records["data"][$record_id]["branches"]) && !is_string($records["data"][$record_id]["branches"])){
                        echo "Failed to get git branches for '" . $domains[$key] . "' from " . $url . "\n";
                        continue;
                    }

                    if((is_array($records["data"][$record_id]["branches"]) ? (array_search($branch, $records["data"][$record_id]["branches"]) === false) : $records["data"][$record_id]["branches"] != $repo_name)){
                        echo "Failed to find branch '" . $branch . "' for '" . $domains[$key] . "' in '" . $repo_name . "' from " . $url . "\n";
                        continue;
                    }
                }
                $git_uuids[$key] = $records["data"][$record_id]["uuid"];
            }
            return $git_uuids;
        }
    }

?>