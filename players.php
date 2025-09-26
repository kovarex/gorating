<?php
require_once("src/table_viewer.php");

$search = @$_GET["search"];

echo "<div style=\"text-align:center;\">";
echo "<form>";
echo "<table class=\"centered-table\">";
echo "<tr><td><label for=\"search\">Search (name, pin)</label></td><td><input type=\"text\" name=\"search\"".($search ? "value=\"".$search."\"" : "")."/></td></tr>";
echo "<tr><td><label for=\"country_code\">Country:</label></td><td>".countrySelector(@$_GET["country_code"])."</td></tr>";
echo "<tr><td colspan=2><input type=\"submit\" value=\"Submit\"/></td></tr>";
echo "</table>";
echo "</form>";
echo "</div>";

function addWithOr($query, $addition)
{
  if (empty($query))
    return $addition;
  return $query." or ".$addition;
}

$searchQuery = "";

if (!empty($search))
{
  $parts = explode(" ", $search);
  $textQuery = "";

  foreach ($parts as $part)
  {
    if (empty($part))
      continue;
    if (is_numeric($part) and strlen($part) == 8)
      $textQuery = addWithOr($textQuery, "user.egd_pin=".escape($search));
    else
    {
      $textQuery = addWithOr($textQuery, "MATCH(user.first_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
      $textQuery = addWithOr($textQuery, "MATCH(user.last_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
    }
  }
}

if (!empty($textQuery))
  $searchQuery .= (empty($searchQuery) ? " and " : " WHERE ")."(".$textQuery.")";

if (!empty($_GET["country_code"]))
  $searchQuery .= (empty($searchQuery) ? " and " : " WHERE ")."country.code=".escape($_GET["country_code"]);

$table = new TableViewer("user
                            LEFT JOIN user as inviter ON inviter.id = user.invited_by_user_id
                            JOIN admin_level ON admin_level.id = user.admin_level_id
                            JOIN country ON country.id = user.country_id".$searchQuery,
                         $_GET);
$table->setFixedSort(new SortDefinition("user_register_timestamp is null", true));
if (empty($search))
  $table->setPrimarySort(new SortDefinition("rating", false));
else
  $table->setPrimarySort(new SortDefinition("name"));
$table->addColumn("rating",
                  "Rating",
                  array(array("user.rating", "rating")),
                  function($row) { echo round($row["rating"]); },
                  "style=\"text-align: right;\"");
$table->addColumn("name",
                  "Name",
                  array(array("CONCAT(user.first_name, ' ', user.last_name)", "name"),
                        array("user.id", "id"),
                        array("user.username", "username")),
                  function($row){ echo playerLink($row); });
$table->addColumn("egd_pin",
                  "EGD",
                  array(array("user.egd_pin", "egd_pin")),
                  function($row) { echo egdLink($row["egd_pin"]); });
$table->addColumn("country_code",
                  "Country",
                  array(array("country.code", "country_code")),
                  function($row) { echo $row["country_code"]; });
$table->addColumn("admin_level_name",
                  "Role",
                  array(array("admin_level.name", "admin_level_name")),
                  function($row) { echo $row["admin_level_name"]; });
if (canSeeInviters())
  $table->addColumn("inviter_name",
                    "Invited By",
                    array(array("inviter.id", "inviter_id"),
                          array("CONCAT(inviter.first_name, ' ', inviter.last_name)", "inviter_name"),
                          array("inviter.username", "inviter_username")),
                    function($row) { echo playerLink($row, "inviter"); });
if (canSeeEmails())
  $table->addColumn("user_email",
                    "Email",
                    array(array("user.email", "user_email")),
                    function($row) { echo $row["user_email"]; });
$table->addColumn("user_register_timestamp",
                  "Registered",
                  array(array("user.register_timestamp", "user_register_timestamp")),
                  function($row)
                  {
                    if (!empty($row["user_register_timestamp"]))
                      echo date("d. m. Y H:i", strtotime($row["user_register_timestamp"]));
                  });

$table->addColumn("games",
                  "Games",
                  array(array("user.win_count + user.loss_count + user.egd_win_count + user.egd_loss_count", "games")),
                  function ($row) { echo $row["games"]; },
                  "style=\"text-align: right;\"");

$table->addColumn("wins",
                  "Wins",
                  array(array("user.win_count + user.egd_win_count", "wins")),
                  function ($row) { echo $row["wins"]; },
                  "style=\"text-align: right;\"");

$table->addColumn("losses",
                  "Losses",
                  array(array("user.loss_count + user.egd_loss_count", "losses")),
                  function ($row) { echo $row["losses"]; },
                  "style=\"text-align: right;\"");

$table->addColumn("friendly_games",
                  "Friendly Games",
                  array(array("user.win_count + user.loss_count", "friendly_games")),
                  function ($row) { echo $row["friendly_games"]; },
                  "style=\"text-align: right;\"");

$table->render();
?>
