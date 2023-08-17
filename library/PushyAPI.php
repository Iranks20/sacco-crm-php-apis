<?php

class PushyAPI {
    static public function sendPushNotification($data, $ids, $apiKey) {

        // Set post variables
        $post = array(
            'data'              => $data,
            'registration_ids'  => $ids,
        );

        // Set Content-Type header since we're sending JSON
        $headers = array(
            'Content-Type: application/json'
        );

        // Initialize curl handle
        $ch = curl_init();

        // Set URL to Pushy endpoint
        curl_setopt($ch, CURLOPT_URL, 'https://api.pushy.me/push?api_key='.$apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);

		return $result;
    }
}

?>