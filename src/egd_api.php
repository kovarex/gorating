<?php

function getUrlContent($url)
{
  fopen("cookies.txt", "w");
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

  curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
  curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function getPageDom($url)
{
  tryAgain:
  $data = getUrlContent($url);
  if (empty($data))
    die("Couldn't load the page.".$url);
  if ($data == "error code: 1015")
  {
    usleep(500000);
    goto tryAgain;
  }
  $doc = new DOMDocument();
  if (!$doc->loadHTML('<?xml encoding="UTF-8">'.$data))
    die("Couldn't parse the HTML from".$url);
  return $doc;
}

function getCountryCodeAndID($country)
{
  $countryPieces = explode(" ", $country);
  $countryCodeInBrackets = $countryPieces[count($countryPieces) - 1];
  $countryCode = substr($countryCodeInBrackets, 1, strlen($countryCodeInBrackets) - 2);
  $result = query("SELECT id FROM country WHERE code=".escape($countryCode))->fetch_assoc();
  if (empty($result))
    die("Couldn't find country with code:".$countryCode." complete name:".$country);
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
  print_r($inputs);
  foreach ($inputs as $input)
    if ($input->attributes->getNamedItem("name")->textContent == "gor")
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
    elseif ($input->attributes->getNamedItem("name")->textContent == "country")
      $result["country"] = getCountryCodeAndID($input->attributes->getNamedItem("value")->textContent);
  if (!empty(@$result["rating"]) && !empty(@$result["country"]))
    return $result;
  die("Couldn't determine the rating from the EGD page. Link:".$url." rating:".$result["rating"]."</br>");
}
?>
