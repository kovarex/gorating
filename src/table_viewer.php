<?php
require_once("link_helper.php");
define("SORT_ASCEND", 1);
define("SORT_DESCEND", 0);

class SortDefinition
{
  function __construct($column, $ascending = SORT_ASCEND)
  {
    $this->column = $column;
    $this->ascending = $ascending;
  }

  function render()
  {
    return $this->column.($this->ascending ? "" : " DESC");
  }

  public $column;
  public $ascending;
};

class SortBuilder
{
  public function add($sortDefinition)
  {
    if (empty($this->result))
      $this->result = " ORDER BY \n";
    else
      $this->result .= ",\n";
    $this->result .= $sortDefinition->render();
  }
  public $result;
}

class SqlFromFiller
{
  public function add($sql, $as = NULL)
  {
    if (!empty($this->result))
      $this->result .= ",\n";
    $this->result .= $sql;
    if ($as)
      $this->result .= " as ".$as;
  }
  public $result;
};

class TableColumn
{
  function __construct($name, $caption, $sql, $cellFiller, $cellParameters, $get, $defaultSortAscend = SORT_ASCEND)
  {
    $this->name = $name;
    $this->caption = $caption;
    $this->sql = $sql;
    $this->cellFiller = $cellFiller;
    $this->cellParameters = $cellParameters;
    $this->get = $get;
    $this->defaultSortAscend = $defaultSortAscend;
  }

  public function fillFrom(&$sqlFromFiller)
  {
    foreach ($this->sql as $part)
      $sqlFromFiller->add($part[0], $part[1]);
  }

  public function fillFromSecondary(&$sqlFromFiller)
  {
    if (isset($this->secondarySql))
    {
      foreach ($this->secondarySql as $part)
        $sqlFromFiller->add($part[0], $part[1]);
    }
    else
      foreach ($this->sql as $part)
        $sqlFromFiller->add("NULL", $part[1]);
  }

  public function addSecondary($secondarySql)
  {
    $this->secondarySql = $secondarySql;
		return $this;
  }

	public function setHeaderAttributes($headerAttributes)
	{
		$this->headerAttributes = $headerAttributes;
	}

  public function renderHeader($currentSort, $limit)
  {
    echo "<th".(!empty($this->headerAttributes) ? (" ".$this->headerAttributes) : "") .">";
    echo "<span style=\"vertical-align: middle;\">";
    if (!isset($limit))
    {
      $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
      $getCopy = $this->get;
      $thisIsSorted = $currentSort->column == $this->name;
      $getCopy["sort"] = $this->name;
      if ($thisIsSorted)
        $getCopy["d"] = $currentSort->ascending ? "down" : "up";
      else
        $getCopy["d"] = $this->defaultSortAscend ? "up" : "down";

      echo "<a href=\"".generateAddress($url, $getCopy)."\">";
      echo $this->caption;
      echo "</a>";
      if ($thisIsSorted)
      {
        global $resourceAddress;
        echo "<img class=\"sorting-image\" src=\"".$resourceAddress."/img/arrow-".($currentSort->ascending ? "up" : "down").".png\"/>";
      }
    }
    else
      echo $this->caption;
    echo "</span>";
    echo "</th>";
  }

  public function renderCell($row)
  {
    echo "<td".$this->getCellParameters().">";
    $filler = $this->cellFiller;
    $filler($row);
    echo "</td>";
  }

  private function getCellParameters()
  {
    if (empty($this->cellParameters))
      return "";
    return " ".$this->cellParameters;
  }

  public function getSort($textualDirection)
  {
    return new SortDefinition($this->sql[0][1], $textualDirection ? ($textualDirection == "up") : $this->defaultSortAscend);
  }

  private $get;
  public $name;
  public $caption;
  public $sql;
  public $secondarySql;
  public $cellFiller;
  public $cellParameters;
  public $defaultSortAscend;
	public $headerAttributes;
};

class TableViewer
{
   public function __construct($queryCoreData, $get)
  {
    $this->queryCoreData = $queryCoreData;
    $this->get = $get;
  }

  public function queryCore($forCount)
  {
    $member = $this->queryCoreData;
    if (is_callable($member))
      return $member($forCount);
    return $member;
  }

  public function secondaryQueryCore($forCount)
  {
    $member = $this->secondaryQueryCoreData;
    if (is_callable($member))
      return $member($forCount);
    return $member;
  }

  public function addColumn($name, $caption, $sql, $cellFiller, $cellParameters = NULL, $defaultSortAscend = SORT_ASCEND)
  {
    $this->columns[$name] = new TableColumn($name, $caption, $sql, $cellFiller, $cellParameters, $this->get, $defaultSortAscend);
    if (!empty($name) && @$_GET["sort"] == $name)
      $this->currentSort = $this->columns[$name]->getSort(@$_GET["d"]);
    return $this->columns[$name];
  }

  public function renderHeader()
  {
    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderHeader($this->currentSort, $this->limit);
    echo "</tr>";
  }

  private function buildSort()
  {
    $sortBuilder = new SortBuilder();
    if ($this->fixedSort)
      $sortBuilder->add($this->fixedSort);
    if ($this->currentSort)
      $sortBuilder->add($this->currentSort);
    if ($this->lastSort)
      $sortBuilder->add($this->lastSort);
    return $sortBuilder->result;
  }

  private function getStart()
  {
    $result = @$this->get["start"];
    if ($result and is_numeric($result))
      return $result;
    return 1;
  }

  private function buildQuery()
  {
    $result = "SELECT \n";
    $sqlFromFiller = new SqlFromFiller();
    foreach ($this->columns as $column)
      $column->fillFrom($sqlFromFiller);
    $result .= $sqlFromFiller->result;
    $result .= " FROM \n";
    $result .= $this->queryCore(false /* not for count */);

    if (isset($this->secondaryQueryCoreData))
    {
      $result .= " UNION ALL \n";
      $result .= "SELECT\n";
      $sqlSecondaryFromFiller = new SqlFromFiller();
      foreach ($this->columns as $column)
        $column->fillFromSecondary($sqlSecondaryFromFiller);
      $result .= $sqlSecondaryFromFiller->result;
      $result .= " FROM \n";
      $result .= $this->secondaryQueryCore(false /* not for count */);
    }

    $result .= $this->buildSort();
    $result .= " LIMIT ".(isset($this->limit) ? $this->limit : TABLE_PAGE_SIZE);
    $start = $this->getStart();
    if ($start > 1)
      $result .= " OFFSET ".($start - 1);
    return $result;
  }

  private function renderRow($row)
  {
    if (isset($this->specialRow))
    {
      $specialRow = $this->specialRow;
      if ($specialRow($row))
        return;
    }

    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderCell($row);
    echo "</tr>\n";
  }

  public function render()
  {
    echo "<br/>\n";
    echo "<table class=\"".(isset($this->tableClass) ? $this->tableClass : "data-table")."\">\n";
    $data = query($this->buildQuery());

    if (!isset($this->limit))
    {
      $total = query("SELECT COUNT(*) as total FROM ".$this->queryCore(true/* for count*/))->fetch_assoc()["total"];
      if ($data->num_rows < $total)
      {
        echo "<caption>";
        $start = $this->getStart();
        if ($start > 1)
        {
          $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
          $getCopy = $this->get;
          $getCopy["start"] = max($start - TABLE_PAGE_SIZE, 1);
          echo "<a href=\"".generateAddress($url, $getCopy)."\">Previous</a> ";
        }

        echo  $start."-".min($start + $data->num_rows, $total)." of ".$total;

        if ($start + $data->num_rows < $total)
        {
          $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
          $getCopy = $this->get;
          $getCopy["start"] = $start + TABLE_PAGE_SIZE;
          echo " <a href=\"".generateAddress($url, $getCopy)."\">Next</a> ";
        }
        echo "</caption>";
      }
    }
    else if (isset($this->caption))
      echo "<caption>".$this->caption."</caption>";

    $this->renderHeader();
    while ($row = $data->fetch_assoc())
      $this->renderRow($row);
    echo "</table>";
  }

  public function setFixedSort($fixedSort)
  {
    $this->fixedSort = $fixedSort;
  }

  public function setPrimarySort($primarySort)
  {
    $this->currentSort = $primarySort;
  }

  public function setLastSort($lastSort)
  {
    $this->lastSort = $lastSort;
  }

  public function setSpecialRow($specialRow)
  {
    $this->specialRow = $specialRow;
  }

  public function addSecondaryUnionAll($secondaryQueryCoreData)
  {
    $this->secondaryQueryCoreData = $secondaryQueryCoreData;
  }

  public $queryCoreData;
  public $secondaryQueryCoreData;
  public $limit;
  public $caption;
  public $tableClass;
  private $columns;
  private $get;
  private $fixedSort;
  private $currentSort;
  private $lastSort;
  private $specialRow;
};

?>
