<?php

/**
 * データアクセスオブジェクト
 *
 * @author taketin
 */
class Dao
{

    private $_pdo;

    private $_params;

    private $_account;

    private $_stockFavIds;

    private $_stockFavs;

    /**
     * コンストラクタ
     *
     * @param string | array DB接続情報配列
     * @return void
     */
    public function __construct($params)
    {
        $this->_params = $params;
        $this->_pdo = $this->_getConnection();

        return;
    }

    /**
     * DBに接続してコネクションを返す
     *
     * @return obj PDOオブジェクト
     */
    public function _getConnection()
    {
        try {
            $pdo = new PDO(
                $this->_params['dsn'],
                $this->_params['account'],
                $this->_params['password']
            );
        } catch (PDOException $e) {
            die($e->getMessage());
        }

        return $pdo;
    }

    /**
     * アカウントセット
     *
     * @param string $account クロールしたいアカウント
     * @return void
     */
    public function setAccount($account)
    {
        $this->_account = $account;

        return;
    }

    /**
     * 取得したふぁぼられツイートとDBのレコードがマッチするものを取得 
     *
     * @param int | array $ids ステータスID
     * @return int | array DBに保存されているふぁぼったーID
     */
    public function getFavs($ids)
    {
        try {
            $count = count($ids);

            $query = '
                SELECT
                    fav_tweet.fav_id,
                    fav_follower.account 
                FROM 
                    fav_tweet,
                    fav_follower 
                WHERE 
                    fav_follower.fav_id = fav_tweet.fav_id AND 
                    fav_tweet.fav_id in ( 
            ';

            for ($i = 0; $i < $count; $i++) {
                $query .= '?,';
            }
            $query = substr_replace($query, '', -1, 1);
            $query .= ') ';
            
            $statement = $this->_pdo->prepare($query);
            for ($i = 0; $i < $count; $i++) {
                $statement->bindValue($i + 1, $ids[$i]);
            }

            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e){
            die($e->getMessage());
        }

        if (0 < count($results)) {
            foreach ($results as $key => $item) {
                $this->_stockFavIds[] = $results[$key]['fav_id'];
                $this->_stockFavs[$results[$key]['fav_id']] = $results[$key]['account'];
            }
        } else {
            $this->_stockFavIds = array();
        }

        return $this->_stockFavIds;
    }

    /**
     * 既存のふぁぼられを新たにふぁぼったアカウント抽出 
     *
     * @param mixed | array $statuses ステータスID配列
     * @return mixed | array 新たなふぁぼったー
     */
    public function findAddFavs($statuses)
    {
        if (1 > count($this->_stockFavIds)) { return; }
        
        foreach ($this->_stockFavIds as $key => $id) {
            $key = array_search($this->_stockFavIds[$key], $statuses['id']);
            $favotters = (false !== $key) ? $statuses['fav'][$key] : false;
            if (false !== $favotters) {
                try {
                    foreach ($favotters as $favAccount) {
                        $query = '
                            SELECT
                                account 
                            FROM 
                                fav_follower
                            WHERE
                                fav_id = ? AND 
                                account = ?
                        ';

                        $statement = $this->_pdo->prepare($query);
                        $statement->bindValue(1, $statuses['id'][$key]);
                        $statement->bindValue(2, $favAccount);
                        $statement->execute();

                        $result = $statement->fetchAll();

                        if (0 == count($result)) {
                            $addFavs[$statuses['id'][$key]][] = $favAccount;
                        }
                    }
                } catch (PDOException $e){
                    die($e->getMessage());
                }
            }
        }

        return $addFavs;
    }

    /**
     * 新たにふぁぼられたステータスを保存
     *
     * @param mixed | array $statuses ステータス
     * @return void
     */
    public function insertNewFavs($statuses)
    {
        date_default_timezone_set('Asia/Tokyo');
        $now = date('Y-m-d H:i:s');
        try {
            $query = '
                INSERT INTO 
                    fav_tweet 
                    (fav_id, account, created_on) 
                VALUES 
            ';

            $count = count($statuses);
            for ($i = 0; $i < $count; $i++) {
                $query .= '(?, ?, ?),';
            }

            $query = substr_replace($query, '', -1, 1);
            $statement = $this->_pdo->prepare($query);

            $count = 1;
            foreach ($statuses as $fav_id => $favotters) {
                $statement->bindValue($count++, $fav_id);
                $statement->bindValue($count++, $this->_account);
                $statement->bindValue($count++, $now);
                $this->_insertNewFavFollowers($fav_id, $favotters);
        }

            $statement->execute();
        } catch (PDOException $e){
            die($e->getMessage());
        }

        return;
    }

    /**
     * 新たにふぁぼられたステータスのフォロワーを保存
     *
     * @param int $fav_id ふぁぼったーステータスID
     * @param string | array $favotters ふぁぼったー
     * @return void
     */
    public function _insertNewFavFollowers($fav_id, $favotters)
    {
        date_default_timezone_set('Asia/Tokyo');
        $now = date('Y-m-d H:i:s');
        try {
            $query = '
                INSERT INTO 
                    fav_follower  
                    (fav_id, account, created_on) 
                VALUES 
            ';

            $count = count($favotters);
            for ($i = 0; $i < $count; $i++) {
                $query .= '(?, ?, ?),';
            }

            $query = substr_replace($query, '', -1, 1);

            $localPdo = $this->_getConnection(); 

            $statement = $localPdo->prepare($query);
            $count = 1;
            foreach ($favotters as $favotter) {
                $statement->bindValue($count++, $fav_id);
                $statement->bindValue($count++, $favotter);
                $statement->bindValue($count++, $now);
            }

            $statement->execute();
        } catch (PDOException $e){
            die($e->getMessage());
        }

        return;
    }

    /**
     * 既にふぁぼられてるtweetへの新しいふぁぼったーの追加 
     *
     * @param int $id ステータスID
     * @return void
     */
    public function insertAddFavs($statuses)
    {
        foreach ($statuses as $fav_id => $favotters) {
            $this->_insertNewFavFollowers($fav_id, $favotters);
        }

        return;
    }

}
