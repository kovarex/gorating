<?php
// Prints what the PHP version reads from an EGD page (src/egd_api.php, process_tournament_helper.php,
// check_new_tournaments.php), with PHP's DOMDocument, one value per line, to compare the kpp
// version's reader with: php egd_extract.php page.html
$doc = new DOMDocument();
$doc->loadHTML('<?xml encoding="UTF-8">'.file_get_contents($argv[1]), LIBXML_NOWARNING | LIBXML_NOERROR);
foreach ($doc->getElementsByTagName('input') as $input)
{
  $name = @$input->attributes->getNamedItem("name")->textContent;
  if ($name)
    echo "input\t", $name, "\t", @$input->attributes->getNamedItem("value")->textContent, "\n";
}
foreach ($doc->getElementsByTagName('span') as $span)
  if (@$span->attributes->getNamedItem("class")->textContent == "plain5")
  {
    echo "plain5\t", $span->nodeValue, "\n";
    break;
  }
foreach ($doc->getElementsByTagName('div') as $div)
  if (@$div->attributes->getNamedItem("class")->textContent == "thisdiv")
  {
    echo "player\t", $div->getElementsByTagName("b")[1]->nodeValue, "\t", $div->getElementsByTagName("a")[0]->nodeValue, "\n";
    foreach ($div->getElementsByTagName("table")[1]->getElementsByTagName("tr") as $row)
    {
      $cells = $row->getElementsByTagName("td");
      if (count($cells) == 0)
        continue;
      $values = [];
      foreach ($cells as $cell)
        $values[] = $cell->nodeValue;
      echo "game\t", implode("\t", $values), "\n";
    }
  }
foreach ($doc->getElementsByTagName('table') as $table)
  if (@$table->attributes->getNamedItem("bordercolor")->textContent == "#396B95")
  {
    foreach ($table->getElementsByTagName('tr') as $row)
    {
      $cells = $row->getElementsByTagName('td');
      if (count($cells) == 0)
        continue;
      echo "listed\t", $cells[0]->nodeValue, "\n";
    }
    break;
  }
