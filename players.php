<?php
require_once("src/table_viewer.php");

$search = @$_GET["search"];

echo "<div style=\"text-align:center;\">";
echo "<form>";
echo "<table class=\"centered-table\">";
echo "<tr><td><label for=\"search\">Search (name, pin)</label></td><td><input type=\"text\" name=\"search\"".($search ? "value=\"".$search."\"" : "")."/></td></tr>";
echo "<tr><td><label for=\"country_code\">Country:</label></td><td>".countrySelector(@$_GET["country_code"])."</td></tr>";
$showRegisteredOnly = !@$_GET["from_search"] or !@$_GET["is_registered"];
echo "<tr><td></td><td><input type=\"checkbox\" name=\"is_registered\" id=\"is_registered\"".($showRegisteredOnly ? " checked=\"checked\"" : "")."/><label for=\"is_registered\">Only registered:</label></td></tr>";
echo "<tr><td colspan=2><input type=\"submit\" value=\"Submit\"/></td></tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"from_search\" value=\"true\"/>";
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
      $textQuery = addWithOr($textQuery, "MATCH(user.name) AGAINST(".escape($part."*")." IN BOOLEAN MODE)");
  }
}

if (!empty($textQuery))
  $searchQuery .= (empty($searchQuery) ? " WHERE " : " and ")."(".$textQuery.")";

if (!empty($_GET["country_code"]))
  $searchQuery .= (empty($searchQuery) ? " WHERE " : " and ")."country.code=".escape($_GET["country_code"]);

if ($showRegisteredOnly)
  $searchQuery .= (empty($searchQuery) ? " WHERE " : " and ")."user.register_timestamp IS NOT NULL";

$table = new TableViewer(function($forCount)
                         {
                           global $_GET;
                           global $searchQuery;
                           $result = "user";
                           if (!$forCount)
                           {
                            $result .= " LEFT JOIN user as inviter ON inviter.id = user.invited_by_user_id";
                            $result .= " JOIN admin_level ON admin_level.id = user.admin_level_id";
                           }
                           if (!$forCount or @$_GET["country_code"])
                            $result .= " JOIN country ON country.id = user.country_id";
                          $result .= $searchQuery;
                          return $result;
                         },
                         $_GET);

if (empty($search))
  $table->setPrimarySort(new SortDefinition("rating", false));
else
  $table->setPrimarySort(new SortDefinition("name"));
$table->addColumn("rating",
                  "Rating",
                  array(array("user.rating", "rating")),
                  function($row) { echo showRating($row["rating"]); },
                  "style=\"text-align: right;\"",
                  SORT_DESCEND);
$table->addColumn("name",
                  "Name",
                  array(array("user.name", "name"),
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
$table->addColumn("admin_level_id",
                  "Role",
                  array(array("admin_level.id", "admin_level_id"),
                        array("admin_level.name", "admin_level_name")),
                  function($row) { echo $row["admin_level_name"]; });
if (canSeeInviters())
  $table->addColumn("inviter_name",
                    "Invited By",
                    array(array("inviter.id", "inviter_id"),
                          array("inviter.name", "inviter_name"),
                          array("inviter.username", "inviter_username"),
                          array("inviter.rating", "inviter_rating")),
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

$table->addColumn("overall_game_count",
                  "Games",
                  array(array("user.overall_game_count", "overall_game_count")),
                  function ($row) { echo $row["overall_game_count"]; },
                  "style=\"text-align: right;\"",
                  SORT_DESCEND);

$table->addColumn("overall_win_count",
                  "Wins",
                  array(array("user.overall_win_count", "overall_win_count")),
                  function ($row) { echo $row["overall_win_count"]; },
                  "style=\"text-align: right;\"",
                  SORT_DESCEND);

$table->addColumn("overall_loss_count",
                  "Losses",
                  array(array("user.overall_loss_count", "overall_loss_count")),
                  function ($row) { echo $row["overall_loss_count"]; },
                  "style=\"text-align: right;\"",
                  SORT_DESCEND);

$table->addColumn("game_count",
                  "Friendly Games",
                  array(array("user.game_count", "game_count")),
                  function ($row) { echo $row["game_count"]; },
                  "style=\"text-align: right;\"",
                  SORT_DESCEND);

$table->render();
?>
