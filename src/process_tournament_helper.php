<?php
require_once("db.php");
require_once("egd_api.php");
require_once("rating_helper.php");

function getTournamentsToIgnore()
{
  $processedTournamentsResult = query("select egd_key FROM egd_tournament");
  while ($row = $processedTournamentsResult->fetch_assoc())
    $tournamentsToIgnore[$row["egd_key"]] = true;

  $tournamentsMarkedForUpdate = query("select egd_key FROM egd_tournament_to_process");
  while ($row = $tournamentsMarkedForUpdate->fetch_assoc())
    $tournamentsToIgnore[$row["egd_key"]] = true;
  return $tournamentsToIgnore;
}

function processTournament($key)
{
  if (empty($key))
  {
    $result = query("SELECT egd_key FROM egd_tournament_to_process ORDER BY id LIMIT 1")->fetch_assoc();
    if (!empty($result))
      $key = $result["egd_key"];
    else
      return false;
  }

  try
  {
    $existingTournament = query("SELECT * FROM egd_tournament WHERE egd_key =".escape($key))->fetch_assoc();
    $urlContent = getUrlContentSafe("https://www.europeangodatabase.eu/EGD/Tournament_ShowGoR.php?key=".$key);
    $doc = getStringDom($urlContent);

    $inputs = $doc->getElementsByTagName('input');
    foreach ($inputs as $input)
    {
      $inputName = @$input->attributes->getNamedItem("name")->textContent;
      if (!$inputName)
        continue;
      $value = @$input->attributes->getNamedItem("value")->textContent;
      if ($inputName == "nation")
      {
        try
        {
          $country = getCountryCodeAndID($value);
        }
        catch (Exception $e)
        {
          throw new Exception("Error while processing tournament: ".$key." Message: ".$e->getMessage());
        }
      }
      elseif ($inputName == "class")
        $gameTypeID = getGameTypeID($value);
      elseif ($inputName == "tournament_date")
        $date = $value;
      elseif ($inputName == "city")
        $city = $value;
      elseif ($inputName == "rounds" and is_numeric($value))
        $roundCount = $value;
      elseif ($inputName == "total_player")
        $playerCount = $value;
    }

    $spans = $doc->getElementsByTagName('span');
    foreach ($spans as $span)
      if (@$span->attributes->getNamedItem("class")->textContent == "plain5")
      {
        $tournamentName = $span->nodeValue;
        break;
      }

    // tournament name is null sometimes
    if (empty($country))
      throw new Exception("Tournament country info couldn't be determined. key=".$key);
    if (empty($gameTypeID))
      throw new Exception("Game type info couldn't be determined. key=".$key);
    if (empty($date))
      throw new Exception("Tournament date couldn't be determined. key=".$key);
    if ($city === NULL)
      throw new Exception("Tournament city couldn't be determined. key=".$key);
    if (empty($playerCount))
      throw new Exception("Player count couldn't be determined. key=".$key);
    if (empty($roundCount))
      throw new Exception("Round count couldn't be determined. key=".$key);

    $phpTimestamp = strtotime($date);
    $timestamp = date("Y-m-d H:i:s", $phpTimestamp);

    $divs = $doc->getElementsByTagName("div");

    beginTransaction();

    if (!$existingTournament)
    {
      query("INSERT INTO
               egd_tournament(egd_key,
                              timestamp,
                              country_id,
                              game_type_id,
                              city,
                              name,
                              player_count,
                              round_count)
               VALUES(".escape($key).",".
                        escape($timestamp).",".
                        escape($country["id"]).",".
                        escape($gameTypeID).",".
                        escape($city).",".
                        escape($tournamentName).",".
                        escape($playerCount).",".
                        escape($roundCount).")");
      $tournamentID = lastInsertID();
    }
    else
      $tournamentID = $existingTournament["id"];

    query("DELETE FROM egd_tournament_to_process WHERE egd_key=".escape($key));

    $placement = 1;
    $ratingUpdates = [];

    foreach ($divs as $div)
      if (@$div->attributes->getNamedItem("class")->textContent == "thisdiv")
      {
        $currentGor = $div->getElementsByTagname("b")[1]->nodeValue;
        if (!is_numeric($currentGor))
          throw new Exception("The value of gor before tournament: \"".$currentGor." is not a number.");

        $playerLink = $div->getElementsByTagname("a")[0]->nodeValue;
        $pieces = explode(" ", $playerLink);
        $playerPin = $pieces[0];
        if (!is_numeric($playerPin))
          throw new Exception("Pin ".$playerPin." isn't numeric");
        $firstName = $pieces[2];
        $lastName = $pieces[3];
        for ($i = 4; $i < count($pieces); $i++)
          $lastName .= " ".$pieces[$i];
        $userID = addEGDPlayerIfNotPresent($playerPin, $firstName, $lastName);
        $user = query("SELECT * from user WHERE user.id=".escape($userID))->fetch_assoc();
        if (!$existingTournament)
          query("INSERT INTO
                   egd_tournament_result(egd_tournament_id, user_id, placement)
                   VALUES(".escape($tournamentID).",".escape($userID).",".escape($placement).")");
        if (!empty($user["register_timestamp"]) && strtotime($user["register_timestamp"]) < $phpTimestamp)
        {
          $userRating = fetchUserRatingBeforeFromUser($user, $userID, $timestamp, $tournamentID);
          $userStartRating = $userRating;
        }
        else
          unset($userRating);

        $placement = $placement + 1;

        $rows = $div->getElementsByTagName("table")[1]->getElementsByTagName("tr");
        foreach ($rows as $row)
        {
          $cells = $row->getElementsByTagName("td");
          if (count($cells) == 0)
            continue;
          $round = $cells[0]->nodeValue;
          if (!is_numeric($round))
            throw new Exception("Round should be numeric, but is \"".$round."\"");
          $gorChange = $cells[1]->nodeValue;
          if (!is_numeric($gorChange))
            throw new Exception("gor change isn't numeric: \"".$gorChange."\"");

          $color = $cells[3]->nodeValue;
          if ($color == "")
            $color = "b"; // some old tournaments don't have color specified
          if ($color != "w" and $color != "b")
            throw new Exception("Color value unexpected:\"".$color."\"");
          $handicapText = $cells[4]->nodeValue;
          $handicap = explode(" ", $handicapText)[0];
          $komi = ($handicap == 0 ? 6.5 : 0.5);
          $resultText = $cells[5]->nodeValue;

          $jigo = ($resultText == "Jigo");
          if ($resultText != "Win" and $resultText != "Loss" and $resultText != "Jigo")
            throw new Exception("Result text has unexpected value:\"".$resultText."\"");

          $opponentPin = $cells[6]->nodeValue;
          if (!is_numeric($opponentPin))
            throw new Exception("Opponent pin ".$opponentPin." isn't numeric.");

          // I need to save one into winner and one into loser even when it is a jigo, it just needs to be consistent
          // so the way I do it is to save the winner as the first one encountered (higher in the placement)
          if ($jigo)
            $userWon = empty($pinsProcessed[$opponentPin][$playerPin][$round]);
          else
            $userWon = ($resultText == "Win");

          $opponentName = $cells[7]->nodeValue;
          // for some reason we see first_name first in this case (as opposed to the plaeyrs table)
          $opponentNameSplit = explode(" ", $opponentName);
          $opponentLastName = $opponentNameSplit[1];
          $opponentFirstName = $opponentNameSplit[0];
          if (empty($opponentLastName))
          {
            $opponentLastName = $opponentFirstName;
            $opponentFirstName = " "; // some people have just a space as a first name
          }
          $opponentUserID = addEGDPlayerIfNotPresent($opponentPin, $opponentFirstName, $opponentLastName);
          $opponentGor = $cells[9]->nodeValue;
          if (!is_numeric($opponentGor))
            throw new Exception("Opponent gor not numeric.");
          $opponentGorChange = $cells[10]->nodeValue;
          if (!is_numeric($opponentGorChange))
            throw new Exception("Opponent gor Change is not numeric.");

          $winnerUserID = $userWon ? $userID : $opponentUserID;
          $loserUserID = $userWon ? $opponentUserID : $userID;
          $winnerOldGor = $userWon ? $currentGor : $opponentGor;
          $winnerNewGor = $userWon ? ($currentGor + $gorChange) : ($opponentGor + $opponentGorChange);
          $loserOldGor = $userWon ? $opponentGor : $currentGor;
          $loserNewGor = $userWon ? ($opponentGor + $opponentGorChange) : ($currentGor + $gorChange);
          $winnerIsBlack = ($userWon == ($color == "b"));
          $prefix = $userWon ? "winner_" : "loser_";

          if (isset($userRating))
          {
            $oldUserRating = $userRating;
            $opponentRating = fetchUserRatingBefore($opponentUserID, $timestamp, $tournamentID, $opponentGor);
            $userRating += calculateNewRating($userStartRating,
                                              $opponentRating,
                                              $jigo ? 0.5 : ($userWon ? 1 : 0),
                                              $gameTypeID,
                                              ($color == "b" ? $handicap : -$handicap),
                                              ($color == "b" ? -1 : 1) * ($komi - 6.5)) - $userStartRating;
          }

          if (!empty($pinsProcessed[$opponentPin][$playerPin][$round]))
          {
            $id = $pinsProcessed[$opponentPin][$playerPin][$round];
            if (!$existingTournament)
              query("UPDATE".
                    "  game ".
                    "SET ".
                    "  ".$prefix."old_egd_rating=".$currentGor.",".
                    "  ".$prefix."new_egd_rating=".($currentGor + $gorChange).
                    (isset($userRating) ? (",".$prefix."old_rating=".$oldUserRating.",".$prefix."new_rating=".$userRating) : "").
                    " WHERE id=".$id);
            else
            {
              $game = query("SELECT ".$prefix."old_egd_rating as old_egd,".$prefix."new_egd_rating as new_egd,".$prefix."old_rating as old,".$prefix."new_rating as new FROM game WHERE id=".$id)->fetch_assoc();
              $oldEgdIsCorrect = abs($game["old_egd"] - $currentGor) <= 0.0001;
              $newEgdIsCorrect = abs($game["new_egd"] - ($currentGor + $gorChange)) <= 0.0001;
              $oldIsCorrect = !isset($rating) or abs($oldUserRating - $game["old"]) <= 0.0001;
              $newIsCorrect = !isset($rating) or abs($userRating - $game["new"]) <= 0.0001;
              if (!$oldEgdIsCorrect || !$newEgdIsCorrect || !$oldIsCorrect || !$newIsCorrect)
                query("UPDATE ".
                      "  game ".
                      "SET "
                        .$prefix."old_egd_rating=".$currentGor.", ".
                         $prefix."new_egd_rating=".($currentGor + $gorChange).
                         (isset($userRating) ? (",".$prefix."old_rating=".$oldUserRating.",".$prefix."new_rating=".$userRating) : "").
                         " WHERE id=".$id);
            }
          }
          else
          {
            if ($existingTournament)
              $game = query("SELECT
                               id,".
                               $prefix."old_egd_rating as old,".
                               $prefix."new_egd_rating as new,
                               jigo=".($jigo ? "true" : "false")."
                             FROM
                               game
                             WHERE
                               egd_tournament_id=".escape($tournamentID)." and
                               egd_tournament_round=".escape($round)." and
                               winner_user_id=".escape($winnerUserID))->fetch_assoc();
            if (!$existingTournament or !$game)
            {
              query("INSERT INTO
                       game(winner_user_id,
                            loser_user_id,
                            game_type_id,
                            timestamp,
                            winner_old_egd_rating,
                            winner_new_egd_rating,
                            loser_old_egd_rating,
                            loser_new_egd_rating,
                            ".(!isset($userRating) ? "" : ($userWon ? "winner_old_rating, winner_new_rating," : "loser_old_rating, loser_new_rating,"))."
                            winner_is_black,
                            handicap,
                            komi,
                            egd_tournament_id,
                            egd_tournament_round,
                            jigo)
                       VALUES(".$winnerUserID.",".
                                $loserUserID.",".
                                $gameTypeID.",".
                                escape($timestamp).",".
                                escape($winnerOldGor).",".
                                escape($winnerNewGor).",".
                                escape($loserOldGor).",".
                                escape($loserNewGor).",".
                                (!isset($userRating) ? "" : ($oldUserRating.",".$userRating.",")).
                                ($winnerIsBlack ? "true" : "false").",".
                                escape($handicap).",".
                                escape($komi).",".
                                escape($tournamentID).",".
                                escape($round).",".
                                ($jigo ? "true" : "false").")");
              $gameID = lastInsertID();
            }
            else
            {
              $game = query("SELECT
                               id,".
                               $prefix."old_rating as old,".
                               $prefix."new_rating as new,".
                               $prefix."old_egd_rating as old_egd,".
                               $prefix."new_egd_rating as new_egd,
                               jigo=".($jigo ? "true" : "false")."
                             FROM
                               game
                             WHERE
                               egd_tournament_id=".escape($tournamentID)." and
                               egd_tournament_round=".escape($round)." and
                               winner_user_id=".escape($winnerUserID));
              if ($game->num_rows == 0)
                throw new Exception("Processing existing tournament, but game is missing.");
              if ($game->num_rows > 1)
                throw new Exception("Processing existing tournament, but there are more games with the parameters.");
              $game = $game->fetch_assoc();

              $gameID = $game["id"];
              $oldEgdIsCorrect = abs($game["old_egd"] - $currentGor) <= 0.0001;
              $newEgdIsCorrect = abs($game["new_egd"] - ($currentGor + $gorChange)) <= 0.0001;
              $oldIsCorrect = !isset($rating) or abs($oldUserRating - $game["old"]) <= 0.0001;
              $newIsCorrect = !isset($rating) or abs($userRating - $game["new"]) <= 0.0001;
              if (!$oldEgdIsCorrect || !$newEgdIsCorrect || !$oldIsCorrect || !$newIsCorrect)
                query("UPDATE ".
                      "  game ".
                      "SET "
                        .$prefix."old_egd_rating=".$currentGor.", ".
                         $prefix."new_egd_rating=".($currentGor + $gorChange).
                         (isset($userRating) ? (",".$prefix."old_rating=".$oldUserRating.",".$prefix."new_rating=".$userRating) : "").
                         " WHERE id=".$gameID);
            }
          }
          $currentGor = $currentGor + $gorChange;
          $pinsProcessed[$playerPin][$opponentPin][$round] = $gameID;
        }
        $usersToUpdateRating[$userID] = true;
      }


    foreach($usersToUpdateRating as $userID=>$value)
      updateFinalRating($userID);
    commitTransaction();
    return true;
  }
  catch (Exception $e)
  {
    echo "Error: ".$e->getMessage();
    echo $urlContent;
    return false;
  }
}

function getPageOfLatestTournaments()
{
  $post_data = array('ricerca' => '1',
                     'orderBy' => 'orderBy=Tournament_Date,Tournament_Code',
                     'viewStart' => 'viewStart=0',
                     'orderDir' => 'orderDir=DESC',
                     'tournament_code' => '',
                     'date_from' => '',
                     'date_to' => '',
                     'tournament_description' => '',
                     'country_code' => '*',
                     'city' => '*',
                     'filter' => 'All');
  return getUrlContentSafe("https://www.europeangodatabase.eu/EGD/Find_Tournament.php", $post_data);
}
