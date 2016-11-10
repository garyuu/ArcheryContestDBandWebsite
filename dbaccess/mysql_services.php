<?php
require_once('connect_info.php');

define('MAX_LENGTH_OF_TAG', 4);
define('MAX_LENGTH_OF_STAGE', 20);

function sql_connect()
{
    $dsn = DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME;
    return new PDO($dsn, DB_USER, DB_PWD);
}

function sql_allplayerlist($data, $resp=[])
{
    
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT P.id AS id, P.tag AS tag, S.position AS position, P.groupname AS group
        FROM `:player` P, `stage_position` S
        WHERE S.teammode = :team AND S.stage = :stage AND P.tag = S.tag
    ");
    if ($data['team'])
        $sth->bindParam(':player', 'team_info')
    else
        $sth->bindParam(':player', 'player_info')
    $sth->bindParam(':team', $data['team'])
    $sth->bindParam(':stage', $data['stage'])
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_COLUMN);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_allwavelist($data, $resp=[])

function sql_playerlistofposition($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("SELECT tag
        FROM players
        WHERE position = :pos");
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

function sql_scorearray($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("
        SELECT W.shot1, W.shot2, W.shot3,
               W.shot4, W.shot5, W.shot6
        FROM waves W, players P
        WHERE P.tag = :tag
          AND P.id = W.pid
          AND W.stage = :stg
        ORDER BY W.number");
    $sth->bindParam(':tag', $data['tag'], PDO::PARAM_STR, MAX_LENGTH_OF_TAG);
    $sth->bindParam(':stg', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
    {
        $result = $sth->fetchAll(PDO::FETCH_NUM);
        $resp['content'] = $result;
    }
    return $resp;
}

function sql_savewave($data, $resp=[])
{
    $pid = sql_pidofplayertag(array('tag'=>$data['tag']))['content'];
    $dbh = sql_connect();
    $sth = $dbh->prepare("INSERT INTO waves
        (number, pid, stage, shot1, shot2, shot3, shot4, shot5, shot6)
        VALUE (:wav, :pid, :stg, :s1, :s2, :s3, :s4, :s5, :s6)");
    $sth->bindParam(':wav', $data['wave'], PDO::PARAM_INT);
    $sth->bindParam(':pid', $pid, PDO::PARAM_INT);
    $sth->bindParam(':stg', $data['stage'], PDO::PARAM_STR, MAX_LENGTH_OF_STAGE);
    for ($i=0 ; $i<6 ; $i+=1)
        $sth->bindParam('s'.($i+1), $data['shots'][$i], PDO::PARAM_INT);
    if (!$sth->execute())
        $resp['error'] = $sth->errorinfo();
    else
        $resp['content'] = "ok";
    return $resp;
}

function sql_teamscorelist($data, $resp=[])
{
    
}

function sql_pidofplayertag($data, $resp=[])
{
    $dbh = sql_connect();
    $sth = $dbh->prepare("SELECT id
        FROM players
        WHERE tag = :tag");
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
