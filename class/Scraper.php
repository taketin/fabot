<?php

/**
 * ふぁぼったー用スクレイピングクラス
 * 
 * @author taketin
 */
class Scraper
{

    private $_entryId = 'status_';

    private $_statusClass = ' status_text description';

    private $_favotterClass = 'favotters';

    private $_statuses = array();

    /**
     * コンストラクタ 
     *
     * @param string $html スクレイピング対象のHTML
     * @return void
     */
    public function __construct($html)
    {
        $this->_html = $html;
        $this->getStatusIds();
        $this->getTweets();
        $this->getFavotters();

        return;
    }

    /**
     * Getter 全データ
     *
     * @return mixed | array
     */
    public function getParamStatuses()
    {
        return $this->_statuses;
    }

    /**
     * Getter ステータスID 
     *
     * @return int | array
     */
    public function getParamIds()
    {
        return $this->_statuses['id'];
    }

    /**
     * Getter ツイート
     *
     * @return void
     */
    public function getParamTweets()
    {
        return $this->_statuses['tweet'];
    }

    /**
     * Getter ふぁぼったアカウント 
     *
     * @return void
     */
    public function getParamFavotters()
    {
        return $this->_statuses['fav'];
    }

    /**
     * 各発言のステータスIDを取得する
     *
     * @return void
     */
    public function getStatusIds()
    {
        preg_match_all('/(<div id="' . $this->_entryId . ')[0-9]+(" )/', $this->_html, $results);
        foreach ($results[0] as $tag) {
            preg_match('/(status_)[0-9]+/', $tag, $matchs);
            $this->_statuses['id'][] = str_replace('status_', '', $matchs[0]);
        }

        return;
    }

    /**
     * 発言内容を取得
     *
     * @return void
     */
    public function getTweets()
    {
        preg_match_all('/(<span class="' . $this->_statusClass . '">).*(<\/span>)/', $this->_html, $results);
        foreach ($results[0] as $tweet) {
            $tweet = preg_replace('/(<\/span>)/', '', preg_replace('/(<span class="' . $this->_statusClass . '">)/', '', $tweet));
            $tweet = preg_replace('/(<\/a>)/', '', preg_replace('/(<a href=).+(>)/', '', $tweet));    
            $this->_statuses['tweet'][] = $tweet;
        }

        return;
    }

    /**
     * ふぁぼってくれたアカウントを取得
     *
     * @return void
     */
    public function getFavotters()
    {
        preg_match_all('/(<span class="' . $this->_favotterClass . '">).*(<\/span>)/', $this->_html, $results);
        foreach ($results[0] as $favs) {
            preg_match_all('/(<a href="\/user\/)[a-zA-Z0-9_]{3,16}("><img)/', $favs, $matches);
            $favotters = '';
            foreach ($matches[0] as $fav) {
                 $favotters[] = str_replace('<a href="/user/', '', str_replace('"><img', '', $fav));
            }

            preg_match_all('/[0-9]+( fav)/', $favs, $totalFavs);
            $this->_statuses['total_fav'][] = str_replace(' fav', '', $totalFavs[0]);
            $this->_statuses['fav'][] = $favotters;
        }

        return;
    }

}
