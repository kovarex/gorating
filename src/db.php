<?php
require_once("config.php");

$db = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($db->connect_error)
  die("Connection failed: " . $db->connect_error);

function multi_query_and_clear($query)
{
  global $db;
  $db->multi_query($query);
  while ($db->next_result())
  {
    // flushing results
    if (!empty($db->error))
      die($db->error);
  }
}

function query($query, $show = false)
{
  global $db;
  if ($show)
    echo "Debug query: ".$query;
  try
  {
    $result = $db->query($query);
    if (!empty($db->error))
    {
      echo "<div>Sql error:".$db->error."</div>";
      echo "<pre>Query:".$query."</pre>";
      die();
    }
    return $result;
  }
  catch (Exception $e)
  {
      echo "<div>Sql error:".$e->getMessage()."</div>";
      echo "<pre>Query:".$query."</pre>";
      die();
  }
}

function lastInsertID()
{
  global $db;
  return $db->insert_id;
}

function escape($input)
{
  if ($input == NULL)
    return "NULL";
  global $db;
  return "'".$db->real_escape_string($input)."'";
}
?>
