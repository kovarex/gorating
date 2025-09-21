<?php
require("src/table_viewer.php");

echo "<div style=\"text-align:center;\">";
echo "<form >";
echo "<input type=\"text\" name=\"search\"/><br/>";
echo "</form>";
echo "</div>";

$search = @$_GET["search"];

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

  foreach ($parts as $part)
  {
    if (empty($part))
      continue;
    if (is_numeric($part) and strlen($part) == 8)
      $textQuery = addWithOr($searchQuery, "user.egd_pin=".escape($search));
    else
    {
      $textQuery = addWithOr($searchQuery, "MATCH(user.first_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
      $textQuery = addWithOr($searchQuery, "MATCH(user.last_name) AGAINST(".escape("*".$part."*")." IN BOOLEAN MODE)");
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
$table->addColumn("rating",
                  "Rating",
                  array(array("user.rating", "rating")),
                  function($row) { echo round($row["rating"]); },
                  "style=\"text-align: right;\"");
$table->addColumn("name",
                  "Name",
                  array(array("user.first_name", "first_name"),
                        array("user.last_name", "last_name"),
                        array("user.id", "id")),
                  function($row) { echo playerLink($row["id"], $row["first_name"]." ".$row["last_name"]); });
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
                  array(array("user.register_timestamp", "register_timestamp")),
                  function($row)
                  {
                    if (!empty($row["register_timestamp"]))
                      echo date("d. m. Y H:i", strtotime($row["register_timestamp"]));
                    else if (canInvite())
                      echo "<a href=invites?pin=".$row["egd_pin"].">Invite</a>";
                  });
if (userID())
  $table->addColumn("",
                    "Report loss",
                    array(),
                    function($row) { if ($row["id"] != userID()) echo "<a href=\"report?id=".$row["id"]."\"/>Report</a>"; });
$table->render();
?>
