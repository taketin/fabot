<?php

/**
 * TwitterAPIハンドラークラス
 *
 * @use TwitterOAUTH http://github.com/abraham/twitteroauth
 * @author taketin
 */
require_once('twitteroauth.php');

class TwitterAPIHandler
{

    private $_OAuth;

    private $_account;

    private $_notifyStatus;

    /**
     * コンストラクタ
     *
     * @param string $account アカウント名
     * @param string | array $ini 設定項目配列
     * @return void
     */
    public function __construct($account, $ini)
    {
        $this->_account = $account;
        $this->_OAuth = new TwitterOAuth(
                                $ini['consumer_key'],
                                $ini['consumer_secret'],
                                $ini['access_token'],
                                $ini['access_token_secret']
                            );
    
        return;                    
    }

    /**
     * つぶやかせる内容の準備
     *
     * @param mixed | array $updateStatus つぶやく内容
     * @param mixed | array $statuses つぶやき
     * @return obj 自身のオブジェクト
     */
    public function prepare($updateStatus, $statuses)
    {
        unset($this->_notifyStatuses);

        foreach ($updateStatus as $id => $favotters) {
            $key = array_search($id, $statuses['id']);
            foreach ($favotters as $favAccount) {
                $this->_notifyStatuses[] = array(
                    'fav_id' => $id,
                    'tweet' => $statuses['tweet'][$key],
                    'favotter' => $favAccount,
                    'total_fav' => $statuses['total_fav'][$key],
                );
            }
        }

        return $this;
    }

    /**
     * 発言する
     *
     * @return void
     */
    public function tweet()
    {
        foreach ($this->_notifyStatuses as $key => $val) {
            $status = $this->_buildStatus($this->_notifyStatuses[$key]);
            // in_reply_to_status_idを指定するのならば array('status'=>'@hogehoge reply','in_reply_to_status_id'=>'0000000000'); とする。
            $req = $this->_OAuth->OAuthRequest(
                       'https://twitter.com/statuses/update.xml',
                       'POST',
                       array('status' => $status)
                   );

            @header('Content-Type: application/xml');
            echo $req;
        }

        return;
    }

    /**
     * つぶやく内容を作る
     *
     * @param mixed | array $statuses 発言
     * @return string つぶやく内容
     */
    private function _buildStatus($statuses)
    {
        if (50 < mb_strlen($statuses['tweet'])) {
            $statuses['tweet'] = mb_substr($statuses['tweet'], 0, 50) . '...';
        }

        $status = '@' . $this->_account . ' [' . $statuses['favotter']  . '] さんに、「' . $statuses['tweet']  . '」がふぁぼられてるよ。(計'
                . $statuses['total_fav'][0] . 'ふぁぼ) http://favotter.net/status.php?id=' . $statuses['fav_id'];

        return $status;        
    }

}
