<?php
namespace Code;

class Login
{
    /**
     * @return bool indicates successful login
     */
    public function login(): bool
    {
        global $config;
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
            $pw = $_SERVER['PHP_AUTH_PW'] ?? '';
            if (!in_array($user, $config->users)) {
                $this->requestLogin();
                return false;
            }
            if(empty($pw)){
                $db = DbCtx::getCtx();
            }
        }
        $this->requestLogin();
        return false;
    }
    /**
     * @return void
     */
    private function requestLogin(): void
    {
        http_response_code(401);
        header('www-authenticate: Basic realm="diary", charset="UTF-8"');
        echo <<< EOM
        use email as user name and let password stay empty to request a new password sent by email
        EOM;
    }

}
