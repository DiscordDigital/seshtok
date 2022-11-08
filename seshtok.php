<?php
    class seshtok {
        public int $perMinute;
        public int $maxHits;
        public string $basedir;
        public string $salt;
        public string $identifier;
        public string $action;

        private function cleanup() {
            $files = glob($this->workdir.'/*.tok');
            foreach ($files as $file) {
                if(time() - filectime($file) > ($this->perMinute * 60)) {
                   unlink($file);
                }
            }
        }

        public function consume($tokens = 1) {
            $this->cleanup();
            $consumerid = hash('sha256', $this->identifier.$this->salt);
            $tokfile = $this->workdir.'/'.$consumerid.'.tok';
            if (!file_exists($tokfile)) {
                file_put_contents($tokfile, $tokens);
                $count = $this->maxHits - $tokens;
                $normalCount = 1;
            } else {
                $count = file_get_contents($tokfile);
                $normalCount = $count/$tokens;
                $count += $tokens;
                file_put_contents($tokfile, $count);
                $count = $this->maxHits - $count;
            }
            
            if ($count <= 0) {
                switch ($this->action) {
                    case "renew":
                        $count = 1;
                        break;
                    case "reset":
                        $count = 1;
                        unlink($tokfile);
                        break;
                    case "block":
                        $data = 'Rate Limit Exceeded';
                        header('Content-Type: application/json');
                        die(json_encode($data));
                        break;
                }
            }
            
            $blockingMinutes = $this->perMinute / $count;
            $seconds = $blockingMinutes * 60;
            if ($normalCount > $this->freeTokens) {
                sleep($seconds);
            }
        }

        public function setAction($action = "renew") {
            $this->action = $action;
            return $this;
        }

        public function setFreeTokens($tokens = 5) {
            $this->freeTokens = $tokens;
            return $this;
        }

        public function setIdentifier($identifier = "ip") {
            if ($identifier == "ip") {
                if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                    $this->identifier = $_SERVER['HTTP_CF_CONNECTING_IP'];
                } else {
                    $this->identifier = $_SERVER['REMOTE_ADDR'];
                }
            } else {
                $this->identifier = $identifier;
            }
            return $this;
        }

        public function setSalt($salt = "seshtok_file") {
            $this->salt = $salt;
            return $this;
        }

        public function setWorkDir($workdir = __DIR__."/toks") {
            $this->workdir = $workdir;
            return $this;
        }

        public function setMaxHits($hits = 30) {
            $this->maxHits = $hits;
            return $this;
        }

        public function setMinuteRate($rate = 60) {
            $this->perMinute = $rate;
            return $this;
        }

        function __construct() {
            $this->setAction();
            $this->setFreeTokens();
            $this->setSalt();
            $this->setWorkDir();
            $this->setMinuteRate();
            $this->setMaxHits();
            $this->setIdentifier();
            
            if (!file_exists($this->workdir)) {
                mkdir($this->workdir, 0770, true);
            }
        }
    }
?>
