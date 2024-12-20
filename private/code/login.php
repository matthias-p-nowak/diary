<?php
namespace Code;

use DateTime;


/**
 *
 */
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
            $dbctx = Db\DbCtx::getCtx();
            $pwdsIt = $dbctx->findRows('Password');
            $pwds = iterator_to_array($pwdsIt);
            $now=new \DateTime('now');
            if (empty($pw)) {
                // sending a password
                // todo find out last email
                $lt='1970-01-01 00:00:00';
                foreach($pwds as $pwd){
                   if($lt < $pwd->Created){
                    $lt = $pwd->Created;
                   }
                }
                $ltt=new \DateTime($lt);
                $diff=$now->diff($ltt);
                $diffS=(($diff->days*24+$diff->h)*60+$diff->i)*60+$diff->s; 
                if($diffS > 300) 
                {
                    $this->sendNewPassword($user);
                    return false;
                }
                $this->requestLogin();
                return false;
            }
            foreach($pwds as $pwd){
                if(password_verify($pw,$pwd->Hash)){
                    $pwd->Used= $now->format('Y-m-d H:i:s');
                    $dbctx->storeRow($pwd);
                    return true;
                }
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

    const ALFABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ23456789';

    /**
     * @return void
     * @param mixed $email
     */
    private function sendNewPassword($email): void
    {
        global $config, $data;
        $pw = '';
        $e = 1;
        $l = strlen(SELF::ALFABET);
        while ($e < ($config->pw_complexity ?? 1e12)) {
            $e *= $l;
            $i = random_int(0, $l - 1);
            $pw .= SELF::ALFABET[$i];
        }
        $message = <<< EOM
        Hello,

        please use the following password next time '$pw' (Remove the quotes).

        Greetings
        EOM;
        $r = \mail($email, 'password provided', $message);
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . "${r}");
        $hpw = password_hash($pw, PASSWORD_DEFAULT);
        if ($r) {
            echo <<< EOM
            <script>alert('An email has been sent. Wait for email and try again.');</script>
            EOM;
            $pw = new Db\Password();
            $pw->Hash = $hpw;
            $dbctx = Db\DbCtx::getCtx();
            $dbctx->storeRow($pw);

        } else {
            http_response_code(500);
            echo <<< EOM
            Could not send an email to $email.
            EOM;
        }
    }

}
