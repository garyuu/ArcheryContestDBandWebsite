<?php
require_once('connect_info.conf.php');

define('MAX_LENGTH_OF_TAG', 4);
define('MAX_LENGTH_OF_STAGE', 20);

function sql_connect()
{
    $dsn = DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME;
    return new PDO($dsn, DB_USER, DB_PWD);
}

function sql_allplayerlist($data, $resp=[])
{
    if ($data['team'])
        $type = 'team_info';
    else
        $type = 'player_info';
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT P.id AS id, P.tag AS tag, S.position AS position, P.groupname AS groupname, 
               S.stage AS stage, S.rank AS rank
        FROM `$type` P, `stage_position` S
        WHERE S.teammode = :team AND
            (S.filterstage IS NULL AND S.stage = :stage OR S.filterstage = :stage) AND P.tag = S.tag
    ");
    $sth->bindParam(':team', $data['team'], PDO::PARAM_STR);
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_allwavelist($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT W.tag AS tag, W.score AS score, W.win AS winner,
               W.shot1 AS shot1, W.shot2 AS shot2, W.shot3 AS shot3,
               W.shot4 AS shot4, W.shot5 AS shot5, W.shot6 AS shot6
        FROM `wave` W, `stage_position` S
        WHERE S.stage = :stage AND S.tag = W.tag
    ");
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_playerlistofposition($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT P.tag AS tag
        FROM `player_info` P, `stage_position` S
        WHERE S.stage = :stage AND S.position = :pos AND P.tag = S.tag
    ");
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    $sth->bindParam(':pos', $data['position'], PDO::PARAM_INT);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_COLUMN);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_savewave($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        INSERT INTO wave (tag, stage, wave, score, win,
                          shot1, shot2, shot3, shot4, shot5, shot6)
        VALUE (:tag, :stage, :wave, :score, :win,
               :s1, :s2, :s3, :s4, :s5, :s6)
    ");
    $sth->bindParam(':tag', $data['tag'], PDO::PARAM_STR);
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    $sth->bindParam(':wave', $data['wave'], PDO::PARAM_INT);
    $sth->bindParam(':score', $data['score'], PDO::PARAM_INT);
    $sth->bindParam(':win', $data['winner'], PDO::PARAM_BOOL);
    for ($i=0 ; $i<6 ; $i+=1)
        $sth->bindParam('s'.($i+1), $data['shots'][$i], PDO::PARAM_INT);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
        $resp['content'] = "ok";
    return $resp;
}

function sql_modifywinner($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        UPDATE `wave`
        SET win = :win
        WHERE tag = :tag AND stage = :stage AND wave = :wave
    ");
    $sth->bindParam(':tag', $data['tag'], PDO::PARAM_STR);
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    $sth->bindParam(':wave', $data['wave'], PDO::PARAM_INT);
    $sth->bindParam(':win', $data['win'], PDO::PARAM_INT);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
        $resp['content'] = "ok";
    return $resp;
}

function sql_savestageposition($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        INSERT INTO `stage_position` (tag, position, stage, filterstage, teammode)
        VALUE (:tag, :position, :stage, :filter, :team)
    ");
    foreach ($data['table'] as $player)
    {
        if (!$sth->execute(array(
            ':tag' => $player['tag'],
            ':position' => $player['position'],
            ':stage' => $player['stage'],
            ':filter' => $player['filter'],
            ':team' => $data['team']
        )))
        {
            $resp['error'] = $sth->errorinfo();
            return $resp;
        }
    }
    $resp['content'] = "ok";
    return $resp;
}

function sql_teamscorelist($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT T.tag AS t_tag, SUM(W.score) AS score
        FROM `team_info` T, `wave` W, `player_info` P
        WHERE W.stage = :stage AND T.groupname = P.groupname AND
            (T.player1id = P.id OR T.player2id = P.id OR T.player3id = P.id) AND W.tag = P.tag
        GROUP BY T.tag
    ");
    $sth->bindParam(':stage', $data['stage'], PDO::PARAM_INT);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_pidofplayertag($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT id
        FROM `player_info`
        WHERE tag = :tag
    ");
    $sth->bindParam(':tag', $data['tag'], PDO::PARAM_STR, MAX_LENGTH_OF_TAG);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetch(PDO::FETCH_NUM);
        $resp['content'] = $result[0];
    }
    return $resp;
}
?>
