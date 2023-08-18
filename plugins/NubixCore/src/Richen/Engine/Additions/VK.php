<?php 

namespace Richen\Engine\Additions;

class VK extends \Richen\Engine\Manager {
    private string $vk_token;
    public function __construct(string $vk_token) {
        $this->vk_token = $vk_token;
    }

    private function curl($link) {
        $curl = curl_init($link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 'true');
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    public function sendMessage(string $message, $group = false, $admin = true): bool {
        if ($admin) {
            $admin_id = '362746574';
            // $url = "https://api.vk.com/method/messages.send?access_token=" . urlencode($this->vk_token) . "&message=" . urlencode($message);
            // $response = file_get_contents($url);
            // $json = json_decode($response, true);
            // return ($json && isset($json['response']) && $json['response'] === 1);


            $group_access_token = $this->vk_token;
            $recipient_id = $admin_id;
            
            $request_params = array(
                'message' => $message,
                'peer_id' => $recipient_id,
                'access_token' => $group_access_token,
                'v' => '5.131',
                'random_id' => mt_rand(1000000,9999999)
            );
            
            $method_url = 'https://api.vk.com/method/messages.send';
            $response = $this->send($method_url . '?' . http_build_query($request_params));
            $response_data = json_decode($response, true);
            if (isset($response_data['error'])) {
                $error_code = $response_data['error']['error_code'];
                $error_message = $response_data['error']['error_msg'];
                $this->core()->getLogger()->warning("Ошибка при отправке сообщения `" . $message . "` $error_code: $error_message");
                return false;
            } else {
                $this->core()->getLogger()->info($message);
                return true;
            }


        }
        return false;
    }

    private function send($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}