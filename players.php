<?php
require("src/table_viewer.php");

$search = @$_GET["search"];

echo "<div style=\"text-align:center;\">";
echo "<form >";
echo "<input type=\"text\" name=\"search\"".($search ? "value=\"".$search."\"" : "")."/><br/>";
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
  $searchQuery .= " and (".$textQuery.")";

$table = new TableViewer("user LEFT JOIN user as inviter ON inviter.id = user.invited_by_user_id,
                          admin_level,
                          country
                          WHERE
                            user.admin_level_id = admin_level.id and
                            user.country_id = country.id".$searchQuery,
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
                        array("user.id", "id")),
                  function($row) { echo playerLink($row["id"], $row["name"]); });
$table->addColumn("egd_link",
                  "EGD",
                  array(array("user.egd_pin", "egd_pin")),
                  function($row) { echo egdLink($row["egd_pin"]); });
$table->addColumn("country",
                  "Country",
                  array(array("country.code", "country_code")),
                  function($row) { echo $row["country_code"]; });
$table->addColumn("role",
                  "Role",
                  array(array("admin_level.name", "admin_level_name")),
                  function($row) { echo $row["admin_level_name"]; });
if (canSeeInviters())
  $table->addColumn("invited_by",
                    "Invited By",
                    array(array("inviter.id", "inviter_id"),
                          array("inviter.first_name", "inviter_first_name"),
                          array("inviter.last_name", "inviter_last_name")),
                    function($row) { echo playerLink($row["inviter_id"], $row["inviter_first_name"]." ".$row["inviter_last_name"]); });
if (canSeeEmails())
  $table->addColumn("email",
                    "Email",
                    array(array("user.email", "user_email")),
                    function($row) { echo $row["user_email"]; });
$table->addColumn("registered",
                  "Registered",
                  array(array("user.register_timestamp", "user_register_timestamp")),
                  function($row)
                  {
                    if (!empty($row["user_register_timestamp"]))
                      echo date("d. m. Y H:i", strtotime($row["user_register_timestamp"]));
                    else if (canInvite())
                      echo "<a href=invites?pin=".$row["egd_pin"].">Invite</a>";
                  });
$table->render();
?>
