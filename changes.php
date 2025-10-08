<?php
require_once("src/table_viewer.php");
if (!canAccessChanges())
  return "No rights.";

$table = new TableViewer("  `change` JOIN user as executed_by ON change.executed_by_user_id=executed_by.id".
                         "           JOIN change_type ON change.change_type_id=change_type.id".
                         "           LEFT JOIN game ON change.game_id=game.id".
                         "           LEFT JOIN user ON change.user_id=user.id",
                        $_GET);

$table->setPrimarySort(new SortDefinition("timestamp", false));

$table->addColumn("change",
                  "Change",
                  array(array("change_type.name", "change_type_name"),
                        array("change_type.id", "change_type_id"),
                        array("change.id", "change_id")),
                  function($row) { echo $row["change_type_name"]; });

$table->addColumn("executed_by",
                  "Executed by",
                  array(array("executed_by.name", "executed_by_name"),
                        array("executed_by.id", "executed_by_id"),
                        array("executed_by.username", "executed_by_username"),
                        array("executed_by.rating", "executed_by_rating")),
                  function($row) { echo playerLink($row, "executed_by"); });

$table->addColumn("game",
                  "Game",
                  array(array("game.id", "game_id")),
                  function($row) { echo gameLink($row["game_id"]); });

$table->addColumn("user",
                  "User",
                  array(array("user.name", "user_name"),
                        array("user.id", "user_id"),
                        array("user.username", "user_username"),
                        array("user.rating", "user_rating")),
                  function($row) { echo playerLink($row, "user"); });

$table->addColumn("from",
                  "From",
                  array(array("change.old_value", "change_old_value")),
                  function($row)
                  {
                    if ($row["change_type_id"] == CHANGE_GAME_SGF and !empty($row["change_old_value"]))
                      echo "<a href=\"sgf?from=change_old&id=".$row["change_id"]."\">Old SGF</a>";
                    else
                      echo $row["change_old_value"];
                  });

$table->addColumn("to",
                  "To",
                  array(array("change.new_value", "change_new_value")),
                  function($row)
                  {
                    if ($row["change_type_id"] == CHANGE_GAME_SGF and !empty($row["change_new_value"]))
                      echo "<a href=\"sgf?from=change_new&id=".$row["change_id"]."\">New SGF</a>";
                    else
                      echo $row["change_new_value"];
                  });

$table->addColumn("timestamp",
                  "Date",
                  array(array("change.timestamp", "timestamp")),
                  function($row)
                  {
                    echo date("d. m. Y H:i", strtotime($row["timestamp"]));
                  });

$table->render();

?>