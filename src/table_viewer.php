<?php

class SortDefinition
{
  function __construct($column, $ascending = true)
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
  public function add($sql, $as)
  {
    if (!empty($this->result))
      $this->result .= ",\n";
    $this->result .= $sql." as ".$as."";
  }
  public $result;
};

class TableColumn
{
  function __construct($name, $caption, $sql, $cellFiller, $cellParameters, $get)
  {
    $this->name = $name;
    $this->caption = $caption;
    $this->sql = $sql;
    $this->cellFiller = $cellFiller;
    $this->cellParameters = $cellParameters;
    $this->sort = @$get[$name];
  }

  public function fillFrom(&$sqlFromFiller)
  {
    foreach ($this->sql as $part)
      $sqlFromFiller->add($part[0], $part[1]);
  }

  public function renderHeader()
  {
    echo "<th>".$this->caption."</th>";
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

  public $name;
  public $caption;
  public $sql;
  public $cellFiller;
  public $cellParameters;
  public $sort;
};

class TableViewer
{
   public function __construct($queryCore, $get)
  {
    $this->queryCore = $queryCore;
    $this->get = $get;
  }

  public function addColumn($name, $caption, $sql, $cellFiller, $cellParameters = NULL)
  {
    $this->columns[$name] = new TableColumn($name, $caption, $sql, $cellFiller, $cellParameters, $this->get);
  }

  public function renderHeader()
  {
    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderHeader();
    echo "</tr>";
  }

  private function buildSort()
  {
    $sortBuilder = new SortBuilder();
    if ($this->fixedSort)
      $sortBuilder->add($this->fixedSort);
    if ($this->currentSort)
      $sortBuilder->add($this->currentSort);
    return $sortBuilder->result;
  }

  private function buildQuery()
  {
    $result = "SELECT \n";
    $sqlFromFiller = new SqlFromFiller();
    foreach ($this->columns as $column)
      $column->fillFrom($sqlFromFiller);
    $result .= $sqlFromFiller->result;
    $result .= " FROM \n";
    $result .= $this->queryCore;
    $result .= $this->buildSort();
    $result .= " LIMIT 100";
    return $result;
  }

  private function renderRow($row)
  {
    echo "<tr>";
    foreach ($this->columns as $column)
      $column->renderCell($row);
    echo "</tr>\n";
  }

  public function render()
  {
    echo "<table class=\"data-table\">";
    $this->renderHeader();
    $data = query($this->buildQuery());
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

  public $queryCore;
  private $columns;
  private $get;
  private $fixedSort;
  private $currentSort;
};

?>
