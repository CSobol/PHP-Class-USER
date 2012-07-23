<?php
class user {
    public $userName, $userEmail, $userAvatar, $userPermission, $verifiedBy, $externalUserID, $greeted, $userScreenName, $internalUserId;
    function  __construct( $userName, $verifiedBy, $externalUserID = null, $eMail = null, $token = null, $secret = null, $avatarURL = null, $userScreenName = null) {
        $getUser = new PDO("mysql:host=localhost;dbname=tweetf5_Combined", 'tweetf5_write', 'B4rgle99!');
        $skip = false;
        $cookieId = false;
        switch($verifiedBy){
            case 'twitter':
                $getUserSql = "SELECT
                                    userId,
                                    userName,
                                    userScreenName,
                                    userAvatar,
                                    userEmail,
                                    userGreeted,
                                    userPermission
                                FROM
                                    tblUsers
                                WHERE
                                    twitterId = :userId;";
                $newUserSQL = "INSERT INTO
                                    tblUsers
                                    (userName, userRegistered, twitterId, userAvatar, userScreenName)
                                VALUES
                                    (:userName, :userRegistered, :externalId, :avatarURL, :userScreenName)";
                if(isset($_COOKIE['logon']['facebook'])){
                    $updateUserSQL .= " facebookId = :cookieId";
                    $cookieId = $_COOKIE['logon']['facebook'];
                }
                setcookie('logon[twitter]', $externalUserID, time()+90*24*60*60);
            break;
            case 'facebook':
                $getUserSql = "SELECT
                                    userId,
                                    userName,
                                    userScreenName,
                                    userAvatar,
                                    userEmail,
                                    userGreeted,
                                    userPermission
                                FROM
                                    tblUsers
                                WHERE
                                    facebookId = :userId;";
                $newUserSQL = "INSERT INTO
                                    tblUsers
                                    (userName, userRegistered, facebookId, userAvatar, userScreenName)
                                VALUES
                                    (:userName, :userRegistered, :externalId, :avatarURL, :userScreenName)";
                if(isset($_COOKIE['logon']['twitter'])){
                    $updateUserSQL .= " twitterId = :cookieId";
                    $cookieId = $_COOKIE['logon']['twitter'];
                }
                setcookie('logon[facebook]', $externalUserID, time()+90*24*60*60);
            break;
            case 'organic':
                setcookie('organicId', $userName, time()+90*24*60*60);
                setcookie('sessionKey', md5($_SERVER["REMOTE_ADDR"]) , time()+90*24*60*60);
                $skip = true;
            break;
        }
        if(!$skip){
            $userDetails = $getUser->prepare($getUserSql);
            $userDetails->execute(array(':userId' => $externalUserID));
            $user = $userDetails->fetch();
            if($user){
                $this->verifiedBy = $verifiedBy;
                $this->greeted = $user['userGreeted'];
                $this->userName = $user['userName'];
                $this->userScreenName = $dataArray['userScreenName'];
                $this->userPermission = $user['userPermission'];
                $this->internalUserId = $user['userId'];
                setcookie('userId', $user['userId'], time()+90*24*60*60);
                setcookie('loggedIn', uniqid(time(), true) );
                $updateCookie = $getUser->prepare("INSERT INTO tblSessions (userId, sessionKey) VALUES (".$this->internalUserId .",". strip_tags($_COOKIE['loggedIn']) . ")");
                $updateCookie->execute();
                if($verifiedBy == 'twitter'){
        //User is relogging in manually, so we need to store new keys
            require_once('encrypt.php');
            $crypt = new proCrypt();
            $tokenSQL = "INSERT INTO
                            tblOAuth
                            (oAuthKey, oAuthSecret, userId)
                         VALUES
                            (:key, :secret, :id)
                            ON DUPLICATE KEY UPDATE
                            oAuthKey = :OAKey, oAuthSecret = :OASecret";
            $encSecret = $crypt->encrypt($secret);
            $encToken = $crypt->encrypt($token);
            $writeTokens = $getUser->prepare($tokenSQL);
            $writeTokens->execute(array(':key' => $encToken, ':secret' => $encSecret, ':id' => $this->internalUserId, ':OAKey' => $encToken, ':OASecret' => $encSecret));
            //write tokens for twitter
            }else{
                $submitUser = $getUser->prepare($newUserSQL);
                $user = $submitUser->execute(array (':userName' => $userName, ":userRegistered" => date('m-d-y', time()), ":externalId" => $externalUserID, ":avatarURL"=>$avatarURL, ":userScreenName" => $userScreenName ));
                $id = $getUser->lastInsertId();
                $this->greeted = false;
                $this->userScreenName = $userScreenName;
                $this->verifiedBy = $verifiedBy;
                $this->userPermission = 1;
                $this->userAvatar = $avatarURL ? $avatarURL : strtolower($verifiedBy);
                setcookie('userId', $user['userId'], time()+90*24*60*60);
                setcookie('loggedIn', uniqid(time(), true) );
                $updateCookie = $getUser->prepare("INSERT INTO tblSessions (userId, sessionKey) VALUES (".$this->internalUserId .",.". strip_tags($_COOKIE['loggedIn']) . ")");
                $updateCookie->execute();
                if($verifiedBy == 'twitter'){
                //User is relogging in manually, so we need to store new keys
                    require_once('encrypt.php');
                    $crypt = new proCrypt();
                    $tokenSQL = "INSERT INTO
                                    tblOAuth
                                    (oAuthKey, oAuthSecret, userId)
                                 VALUES
                                    (:key, :secret, :id)
                                    ON DUPLICATE KEY UPDATE
                                    oAuthKey = :OAKey, oAuthSecret = :OASecret";
                    $encSecret = $crypt->encrypt($secret);
                    $encToken = $crypt->encrypt($token);
                    $writeTokens = $getUser->prepare($tokenSQL);
                    $writeTokens->execute(array(':key' => $encToken, ':secret' => $encSecret, ':id' => $this->internalUserId, ':OAKey' => $encToken, ':OASecret' => $encSecret));
                    //write tokens for twitter
                }
            }
        }else{
            $this->greeted = false;
            $this->userScreenName = $userScreenName;
            $this->verifiedBy = $verifiedBy;
            $this->userPermission = 1;
            $this->userAvatar = $avatarURL ? $avatarURL : null;
            $this->userEmail = $eMail;
            $this->userName = $userScreenName;
            $this->externalUserID = false;
        }
    }
    }
    private function checkUserExists(){
        $userDetails = $getUser->prepare($getUserSql);
            $userDetails->execute(array(':userId' => $externalUserID));
            $user = $userDetails->fetch();
    }
    private function setDetails(){
        $this->userAvatar = $user['userAvatar'];
        $this->userName = $user['name'];
        $this->userPermission = $user['permission'];
    }
    public function updateStatus($message){
        
    }
    public static function banUser($userName, $reason, $duration){
        //TODO: Put something here
    }

    public static function getUserProfile($uid) {
        $lookupSQL = "SELECT
                tblUsers.userScreenName,
                tblUsers.userName,
                tblUsers.userURL,
                tblUsers.userRegistered,
		posts.count,
		votes.upVotes,
		votes.downVotes
              FROM
                tblUsers
		LEFT JOIN (
					SELECT
						COUNT(tblContent.uniqueID) as count,
						tblContent.postAuthor
					FROM
						tblContent
					GROUP BY tblContent.postAuthor
				)posts
		ON posts.postAuthor= tblUsers.userId
                LEFT JOIN
                        (
                                SELECT
                                        voterId,
                                        SUM(CASE WHEN tblVotes.voteType = 'up' THEN 1 ELSE 0 END) as upVotes,
                                        SUM(CASE WHEN tblVotes.voteType = 'down' THEN 1 ELSE 0 END) as downVotes
                                FROM
                                        tblVotes
                                GROUP BY
                                    tblVotes.voterId
                         ) votes
              ON votes.voterId = tblUsers.userId
              WHERE
                tblUsers.userId =  :user";
        $getCard = new PDO("mysql:host=localhost;dbname=tweetf5_Combined", 'tweetf5_write', 'B4rgle99!');
        $getUser = $getCard->prepare($lookupSQL);
        $getUser->execute(array(':user'=>$uid));
        while($r = $getUser->fetch()){
            $user['name'] = $r['userScreenName'] ? $r['userScreenName'] : $r['userName'];
            $user['registered'] = $r['userRegistered'];
            $user['url'] = $r['userURL'];
            $user['count'] = $r['count'];
        }
        return $user;
    }
    //end getUserCard
    public static function getPostList($uid) {
         $lookupSQL = "SELECT
                tblUsers.userScreenName,
                tblUsers.userName,
                tblUsers.userURL,
                tblUsers.userRegistered,
                tblContent.postTitle,
                tblContent.uniqueId
              FROM
                tblUsers
                LEFT JOIN
                    tblContent
                        ON
                            tblUsers.userId=tblContent.postAuthor
              WHERE
                tblUsers.userId = :user";
        $getList = new PDO("mysql:host=localhost;dbname=tweetf5_Combined", 'tweetf5_write', 'B4rgle99!');
        $getPosts = $getList->prepare($lookupSQL);
        $getPosts->execute(array(':user'=>$uid));
        while($r = $getPosts->fetch()){
            $post[]['postTitle'] = $r['postTitle'];
            $post[]['postId'] = $r['uniqueId'];
        }
    }
    public static function getUserAvatar($uid) {
        $SQL = "SELECT
                    tblUsers.userAvatar,
                    tblOAuth.oAuthKey,
                    tblOAuth.oAuthSecret,
                    tblUsers.facebookId
                FROM
                    tblUsers
                LEFT JOIN
                    tblOAuth
                ON
                    tblUsers.userId=tblOAuth.userId
                WHERE
                    tblUsers.userId = :user";
        $getAvatar = new PDO("mysql:host=localhost;dbname=tweetf5_Combined", 'tweetf5_write', 'B4rgle99!');
        $findURL = $getAvatar->prepare($SQL);
        $findURL->execute(array(':user'=>$uid));
        while($r = $findURL->fetch()){
            if(strtolower(substr($r['userAvatar'], 0, strlen(SITE_URL)))== SITE_URL){
                $avatarURL = $r['userAvatar'];
            }elseif($r['userAvatar'] == 'facebook'){
                $avatarURL = "http://graph.facebook.com/{$r['facebookId']}/picture";
            }elseif($r['userAvatar'] == 'twitter'){
                //POLL API FOR AVATAR URL
                require_once('twitter/twitteroauth/twitteroauth.php');
                require_once('twitter/config.php');
                require_once ('encrypt.php');
                $crypt = new proCrypt();
                $oAuthToken = trim($crypt->decrypt($r['oAuthKey']));
                $oAuthSecret = trim($crypt->decrypt($r['oAuthSecret']));
                $twitteroauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,$oAuthToken,$oAuthSecret);
                $twitterUser = $twitteroauth->get('account/verify_credentials');
                $avatarURL = $twitterUser->profile_image_url;
            }else{
                $avatarURL = "/images/blankly.jpg";
            }
        }
        return $avatarURL;
    }
    //END getUserAvatar
    public static function loginViaCookie($cookieId, $cookieKey){
        $SQL = "
            SELECT
                tblUsers.userId,
                tblUsers.userScreenName,
                tblUsers.userAvatar
            FROM
                tblSessions
            LEFT JOIN
                tblSessions
                ON tblSessions.sessionUser = tblUsers.userId
            WHERE
                tblSessions.sessionKey = :sessionKey
        ";
        //TODO: Finish this
    }
    public static function checkIfUserIsLoggedIn() {
        $user = $_SESSION['user'] ? $_SESSION['user'] : $_COOKIE['user'];
        return (!!$user);
    }
    //END checkIfUserIsLoggedIn
}
?>
