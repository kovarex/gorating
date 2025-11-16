<?php
require_once("constants.php");

function getUrlContent($url, $postData = NULL)
{
  fopen("/tmp/cookies.txt", "w");
  $parts = parse_url($url);
  $host = $parts['host'];
  $ch = curl_init();
  $header = array('GET /1575051 HTTP/1.1',
      "Host: {$host}",
      'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language:en-US,en;q=0.8',
      'Cache-Control:max-age=0',
      'Connection:keep-alive',
      'Host:adfoc.us',
      'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
  );

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
  curl_setopt($ch, CURLOPT_COOKIESESSION, true);

  curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
  curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  if ($postData)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function getUrlContentSafe($url, $postData = NULL)
{
  return scrape($url, $postData);
}

function getStringDom($data)
{
  $doc = new DOMDocument();
  if (!$doc->loadHTML('<?xml encoding="UTF-8">'.$data, LIBXML_NOWARNING | LIBXML_NOERROR))
    die("Couldn't parse the HTML from".$url);
  return $doc;
}

function getPageDom($url, $postData = NULL)
{
  return getStringDom(getUrlContentSafe($url, $postData));
}

function getCountryCodeAndID($country)
{
  $countryPieces = explode(" ", $country);
  $countryCodeInBrackets = $countryPieces[count($countryPieces) - 1];
  $countryCode = substr($countryCodeInBrackets, 1, strlen($countryCodeInBrackets) - 2);
  $result = query("SELECT id FROM country WHERE code=".escape($countryCode))->fetch_assoc();
  if (empty($result))
    throw new Exception("Couldn't find country with code:".$countryCode." complete name:".$country);
  $result["id"] = $result["id"];
  $result["code"] = $countryCode;
  return $result;
}

function getGameTypeID($text)
{
  $start = substr($text, 0, 1);
  if ($start == "A")
    return GAME_TYPE_EGD_A;
  if ($start == "B")
    return GAME_TYPE_EGD_B;
  if ($start == "C")
    return GAME_TYPE_EGD_C;
  if ($start == "D")
    return GAME_TYPE_EGD_D;
  die("Couldn't determine egd tournament class from the string:".$text);
}

function getEgdInfo($pin)
{
  $url = "https://www.europeangodatabase.eu/EGD/Player_Card.php?&key=".$pin;
  $doc = getPageDom($url);

  $inputs = $doc->getElementsByTagName('input');
  foreach ($inputs as $input)
    if (@$input->attributes->getNamedItem("name")->textContent == "gor")
    {
      $gor = $input->attributes->getNamedItem("value")->textContent;
      $gorPieces = explode(" ", $gor);
      $rating = $gorPieces[0];
      if (!is_numeric($rating))
      {
        echo "Gor:".$gor."</br>";
        echo "Gor pieces:";
        print_r($gorPieces);
        echo "<br/>";
        die("The provided rating from egd (".$rating.") doesn't seem to be a number.");
      }
      $result["rating"] = $rating;
    }
    elseif (@$input->attributes->getNamedItem("name")->textContent == "country")
      $result["country"] = getCountryCodeAndID($input->attributes->getNamedItem("value")->textContent);
  if (!empty(@$result["rating"]) && !empty(@$result["country"]))
    return $result;
  die("Couldn't determine the rating from the EGD page. Link:".$url."</br>");
}

function addEGDPlayerIfNotPresent($pin, $firstName, $lastName)
{
  $player = query("SELECT id FROM user WHERE egd_pin =".escape($pin))->fetch_assoc();
  if (!empty($player))
    return $player["id"];
  $info = getEgdInfo($pin);
  query("INSERT INTO
           user(first_name,
                last_name,
                egd_pin,
                egd_rating,
                rating,
                country_id,
                admin_level_id)
           VALUES(".escape($firstName).",".
                    escape($lastName).",".
                    escape($pin).",".
                    escape($info["rating"]).",".
                    escape($info["rating"]).",".
                    escape($info["country"]["id"]).",".
                    ADMIN_LEVEL_UNREGISTERED.")");
  return lastInsertID();
}

function isTournamentKey($key)
{
  if (strlen($key) < 4)
    return false;

  if (strlen($key) > 10)
    return false;

  if ($key[0] != "T" and $key[0] != "W" and $key[0] != "E" and $key[0] != "G")
    return false;

  return true;
}

?>
