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

function getEgdInfo($pin)
{
  $url = "https://www.europeangodatabase.eu/EGD/Player_Card.php?&key=".$pin;
  $data = getUrlContent($url);
  if (empty($data))
    die("Couldn't load the egd page.");
  $doc = new DOMDocument();
  if (!$doc->loadHTML('<?xml encoding="UTF-8">'.$data))
    die("Couldn't parse the HTML");
  
  $inputs = $doc->getElementsByTagName('input');
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
  if (!empty(@$result["rating"]))
    return $result;
  die("Couldn't determine the rating from the EGD page.");
}

?>
