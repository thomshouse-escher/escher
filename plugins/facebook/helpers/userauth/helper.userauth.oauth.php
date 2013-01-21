<?php Load::HelperClass('userauth');

class Plugin_facebook_Helper_userauth_oauth extends Helper_userauth {
    protected $me = NULL;

    function authenticate() {
        $CFG = Load::Config();
        $headers = Load::Headers();

        // If no code, redirect to facebook
        $input = Load::Input();
        if(empty($input->get['code'])) {
            $browser = Load::UserAgent();
            $headers->redirect(
                'https://www.facebook.com/dialog/oauth?client_id='
                    .$CFG['facebook_appId'].'&redirect_uri='
                    .urlencode($CFG['wwwroot'].'/login/facebook/')
                    .($browser->match('mobile')? '&amp;display=touch' : '')
                ,TRUE);
        }

        // Attempt to get Oauth access token using the provided code
        $response = @file_get_contents('https://graph.facebook.com/oauth/access_token?client_id='.$CFG['facebook_appId'].
            '&redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/').
            '&client_secret='.$CFG['facebook_secret'].
            '&code='.$input->get['code']);

        if(empty($response)) { return false; }

        // Parse the response and save it to the session
        parse_str($response,$rarr);
        $_SESSION['facebook_access_token'] = $rarr['access_token'];
        $_SESSION['facebook_expires'] = NOW+$rarr['expires']-1; // Local unix time when token expires
        $_SESSION['facebook_query_string'] = $response;

        // Use the token to request facebook user information
        $this->me = $me = (array)json_decode(file_get_contents(
            'https://graph.facebook.com/me?access_token='.$rarr['access_token']));

        // If there is a local user with this facebook uid, log them in and redirect
        if ($user = Load::User(array('facebook_uid'=>$me['id']))) {
            return $user;
        }

        // Setup registration vars (username, fullname, etc.)
        $vars = $this->registrationVars($me);

        // Attempt to register a new user
        return $this->register($vars['username'],'',$vars);
    }

    // If our token expires, we need to redirect the user to authenticate with facebook
    function reauthenticate($force=FALSE) {
        if (!empty($_SESSION['skip_reauthentication'])) { return TRUE; }
        if ($force || empty($_SESSION['facebook_expires']) || $_SESSION['facebook_expires']<NOW) {
            unset($_SESSION['user_id']);
            $CFG = Load::Config();
            $headers = Load::Headers();
            $headers->redirect('https://www.facebook.com/dialog/oauth?client_id='.$CFG['facebook_appId'].
                '&redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/'));
        }
        return true;
    }

    // Nothing to do here, session is entirely local
    function deauthenticate() { return true; }

    // Registration is pretty straightforward...
    function register($username,$password=NULL,$vars=array()) {
        // We must know the facebook uid to register a facebook user
        if (!isset($vars['facebook_uid'])) {
            return false;
        }

        // Assign vars to the user and save
        $vars['username'] = $username;
        $vars['password'] = md5($username.NOW);
        $vars['user_auth'] = 'facebook';
        $user = Load::Model('user');
        return $user->register($vars);
    }

    function onLogin() {
        $USER = Load::User();
        if(empty($USER->facebook_uid)) { return; }

        // Let's track changes so we only save the model once!
        $doSave = FALSE;

        // If we're logged in via facebook, let's check /me for updates
        if ($token = @$_SESSION['facebook_access_token']) {
            if (is_null($this->me)) {
                $me = (array)json_decode(file_get_contents(
                    'https://graph.facebook.com/me?access_token='.$token));
            } else {
                $me = $this->me;
            }
            if(empty($USER->facebook_username) && !empty($me['username'])) {
                $USER->facebook_username = $me['username'];
                $doSave = TRUE;
            }
            if ($USER->display_name==$USER->facebook_display_name) {
                $USER->display_name = $USER->facebook_display_name = $this->formatName($me);
                $doSave = TRUE;
            }
        }

        // If avatar is coming from FB or doesn't exist, grab it
        if (empty($USER->avatar_source) || $USER->avatar_source=='facebook') {
            $CFG = Load::Config();
            $size = !empty($CFG['facebook_image_size'])
                ? $CFG['facebook_image_size']
                : '';
            $USER->avatar_url = $this->getUserImage(
                !empty($USER->facebook_username)
                    ? $USER->facebook_username
                    : $USER->facebook_uid,
                $size
            );
            $USER->avatar_source = 'facebook';
            $doSave = TRUE;
        }
        if ($doSave) {
            $USER->save();
        }
    }

    protected function registrationVars($me) {
        $CFG = Load::CFG();
        $vars = array();
        $vars['display_name'] = $vars['facebook_display_name'] = $this->formatName($me);
        // If user has a facebook username and it is available, use it
        if (!empty($me['username']) && $this->usernameIsAvailable($me['username'])) {
            $vars['username'] = $me['username'];
        } else {
            // Otherwise give them something that should be unique based on their uid
            $vars['username'] = 'facebook.com/'.$me['id'];
        }
        $vars['facebook_uid'] = $me['id'];
        return $vars;
    }

    protected function formatName($me) {
        $CFG = Load::Config();
        $format = @$CFG['facebook_name_format'];
        switch ($format) {
            case 'First': $name = $me['first_name']; break;
            case 'First Last':
                $name = $me['first_name'].' '.$me['last_name']; break;
            case 'First L.':
                $name = $me['first_name'].' '.$me['last_name'][0].'.'; break;
            case 'First L': default:
            $name = $me['first_name'].' '.$me['last_name'][0];
            break;
        }
        return $name;
    }

    protected function getUserImage($user,$size) {
        $url = 'http://graph.facebook.com/'.$user.'/picture';
        if (!empty($size)) {
            if (is_array($size)) { $size = http_build_query($size); }
            $url .= '?'.preg_replace('/^\?/','',$size);
        }
        $curl = curl_init($url);
        curl_setopt_array($curl,array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_FOLLOWLOCATION => FALSE,
        ));
        $response = curl_exec($curl);
        if(preg_match('/Location: (\S+)/i',$response,$match)) {
            return $match[1];
        }
        return null;
    }
}
