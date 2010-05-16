<?php

    /**
     * Favotter Crawler Bot
     *
     * @author taketin
     */
    require_once('./class/Scraper.php');
    require_once('./class/Dao.php');
    require_once('./class/TwitterAPIHandler.php');

    $ini = parse_ini_file('./config.ini', true);
    $ini['database']['dsn'] = 'mysql:host=' . $ini['database']['host'] . '; dbname=' . $ini['database']['dbname'];

    /** 設定したアカウントの数だけループして実行 */
    foreach ($ini['account']['account'] as $account) {
        $html = file_get_contents("http://favotter.net/user/" . $account);

        $scraper = new Scraper($html);
        $dao = new Dao($ini['database']);
        $dao->setAccount($account);

        /** １ページ中の発言の中から新しいものがあれば抽出 */
        $stockFavIds = $dao->getFavs($scraper->getParamIds());
        $newFavIds = array_diff($scraper->getParamIds(), $stockFavIds);
        $statuses = $scraper->getParamStatuses();

        unset($newFavs);
        foreach ($newFavIds as $key => $id) {
            $newFavs[$id] = $statuses['fav'][$key];
        }

        /** 既存のツイートに新規のふぁぼがあれば抽出 */
        $addFavs = $dao->findAddFavs($scraper->getParamStatuses());
        
        /** 抽出されたものがあればbotからReplyしてDB更新 */
        $APIHandler = new TwitterAPIHandler($account, $ini['bot']);
        if (0 < count($newFavs)) {
            $APIHandler->prepare($newFavs, $scraper->getParamStatuses())->tweet();
            $dao->insertNewFavs($newFavs);
        }
        if (0 < count($addFavs)) {
            $APIHandler->prepare($addFavs, $scraper->getParamStatuses())->tweet();
            $dao->insertAddFavs($addFavs);
        }
    }

    exit;
