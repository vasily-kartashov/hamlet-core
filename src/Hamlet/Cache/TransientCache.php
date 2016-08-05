<?php

namespace Hamlet\Cache {

    class TransientCache implements Cache {
        
        private $entries = [];

        public function get(string $key, $defaultValue = null) {
            if (isset($this -> entries[$key])) {
                $entry = $this -> entries[$key];
                if ($entry['expiry'] > time() || $entry['expiry'] == -1) {
                    return [$entry['value'], true];
                }
            }
            return [$defaultValue, false];
        }

        public function set(string $key, $value, int $timeToLive = 0) {
            $expiry = $timeToLive == 0 ? -1 : time() + $timeToLive;
            $this -> entries[$key] = [
                'value'  => $value,
                'expiry' => $expiry,
            ];
        }

        public function delete(string... $keys) {
            foreach ($keys as $key) {
                if (isset($this -> entries[$key])) {
                    unset($this -> entries[$key]);
                }
            }
        }
    }
}