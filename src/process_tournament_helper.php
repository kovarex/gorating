<?php
require("db.php");
require("egd_api.php");

function processTournament($key)
{
  if (empty($key))
  {
    $result = query("SELECT egd_key FROM egd_tournament_to_process ORDER BY id LIMIT 1")->fetch_assoc();
    if (!empty($result))
      $key = $result["egd_key"];
    else
    {
      echo "No tournament key provided and nothing to process.";
      return false;
    }
  }

  $existingTournament = query("SELECT * FROM egd_tournament WHERE egd_key =".escape($key))->fetch_assoc();

  if (!empty($existingTournament))
  {
    echo "Tournament already exists.";
    return false;
  }

  $doc = getPageDom("https://www.europeangodatabase.eu/EGD/Tournament_ShowGoR.php?key=".$key);

  $inputs = $doc->getElementsByTagName('input');
  foreach ($inputs as $input)
  {
    $inputName = $input->attributes->getNamedItem("name")->textContent;
    $value = $input->attributes->getNamedItem("value")->textContent;
    if ($inputName == "nation")
      $country = getCountryCodeAndID($value);
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
    if ($span->attributes->getNamedItem("class")->textContent == "plain5")
    {
      $tournamentName = $span->nodeValue;
      break;
    }

  if (empty($tournamentName))
    die("Tournament name couldn't be determined.");
  if (empty($country))
    die("Tournament country info couldn't be determined.");
  if (empty($gameTypeID))
    die("Game type info couldn't be determined.");
  if (empty($date))
    die("Tournament date couldn't be determined.");
  if (empty($city))
    die("Tournament city couldn't be determined.");
  if (empty($playerCount))
    die("Player count couldn't be determined. key=".$key);
  if (empty($roundCount))
    die("Round count couldn't be determined.");

  $timestamp = date("Y-m-d H:i:s", strtotime($date));

  $divs = $doc->getElementsByTagName("div");

  global $db;
  $db->begin_transaction();

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

  query("DELETE FROM egd_tournament_to_process WHERE egd_key=".escape($key));
  $resultNumber = 1;

  foreach ($divs as $div)
    if ($div->attributes->getNamedItem("class")->textContent == "thisdiv")
    {
      $currentGor = $div->getElementsByTagname("b")[1]->nodeValue;
      if (!is_numeric($currentGor))
        die("The value of gor before tournament: \"".$currentGor." is not a number.");
      
      $playerLink = $div->getElementsByTagname("a")[0]->nodeValue;
      $pieces = explode(" ", $playerLink);
      $playerPin = $pieces[0];
      if (!is_numeric($playerPin))
        die("Pin ".$playerPin." isn't numeric");
      $firstName = $pieces[2];
      $lastName = $pieces[3];
      for ($i = 4; $i < count($pieces); $i++)
        $lastName .= " ".$pieces[$i];
      $userID = addEGDPlayerIfNotPresent($playerPin, $firstName, $lastName);
      query("INSERT INTO
               egd_tournament_result(egd_tournament_id, user_id, placement)
               VALUES(".escape($tournamentID).",".escape($userID).",".escape($resultNumber).")");
      $resultNumber = $resultNumber + 1;

      $rows = $div->getElementsByTagName("table")[1]->getElementsByTagName("tr");
      foreach ($rows as $row)
      {
        $cells = $row->getElementsByTagName("td");
        if (count($cells) == 0)
          continue;
        $round = $cells[0]->nodeValue;
        if (!is_numeric($round))
          die("Round should be numeric, but is \"".$round."\"");
        $gorChange = $cells[1]->nodeValue;
        if (!is_numeric($gorChange))
          die("gor change isn't numeric: \"".$gorChange."\"");

        $color = $cells[3]->nodeValue;
        if ($color == "")
          $color = "b"; // some old tournaments don't have color specified
        if ($color != "w" and $color != "b")
          die("Color value unexpected:\"".$color."\"");
        $handicapText = $cells[4]->nodeValue;
        $handicap = explode(" ", $handicapText)[0];
        $resultText = $cells[5]->nodeValue;
        if ($resultText == "Jigo")
          continue; // I just ignore ties
        if ($resultText != "Win" and $resultText != "Loss")
          die("Result text has unexpected value:\"".$resultText."\"");
        $userWon = ($resultText == "Win");
        $opponentPin = $cells[6]->nodeValue;
        if (!is_numeric($opponentPin))
          die("Opponent pin ".$opponentPin." isn't numeric.");
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
          die("Opponent gor not numeric.");
        $opponentGorChange = $cells[10]->nodeValue;
        if (!is_numeric($opponentGorChange))
          die("Opponent gro Change is not numeric.");
        
        $winnerUserID = $userWon ? $userID : $opponentUserID;
        $loserUserID = $userWon ? $opponentUserID : $userID;
        $winnerOldGor = $userWon ? $currentGor : $opponentGor;
        $winnerNewGor = $userWon ? ($currentGor + $gorChange) : ($opponentGor + $opponentGorChange);
        $loserOldGor = $userWon ? $opponentGor : $currentGor;
        $loserNewGor = $userWon ? ($opponentGor + $opponentGorChange) : ($currentGor + $gorChange);

        if (!empty($pinsProcessed[$opponentPin][$playerPin]))
        {
          $prefix = $userWon ? "winner_" : "loser_";
          $id = $pinsProcessed[$opponentPin][$playerPin];
          query("UPDATE game SET ".$prefix."old_egd_rating=".$currentGor.",".$prefix."new_egd_rating=".($currentGor + $gorChange)." WHERE id=".$id);
        }
        else
          query("INSERT INTO
                   game(winner_user_id,
                        loser_user_id,
                        game_type_id,
                        timestamp,
                        winner_old_egd_rating,
                        winner_new_egd_rating,
                        loser_old_egd_rating,
                        loser_new_egd_rating,
                        winner_is_black,
                        handicap,
                        komi,
                        egd_tournament_id,
                        egd_tournament_round)
                   VALUES(".$winnerUserID.",".
                            $loserUserID.",".
                            $gameTypeID.",".
                            escape($timestamp).",".
                            $winnerOldGor.",".
                            $winnerNewGor.",".
                            $loserOldGor.",".
                            $loserNewGor.",".
                            (($userWon == ($color == "b")) ? "true" : "false").",".
                            $handicap.",".
                            ($handicap == 0 ? "6.5" : "0.5").",".
                            escape($tournamentID).",".
                            escape($round).")");
        $currentGor = $currentGor + $gorChange;
        $pinsProcessed[$playerPin][$opponentPin] = lastInsertID();
      }
    }
  $db->commit();
  echo $key." ";
  return true;
}
