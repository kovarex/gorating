<?php
require("table_viewer.php");

function showTournaments($limit = NULL, $caption = NULL)
{
  $table = new TableViewer("egd_tournament, country, game_type
                            WHERE
                              egd_tournament.country_id=country.id and
                              egd_tournament.game_type_id=game_type.id",
                           $_GET);
  $table->limit = $limit;
  $table->caption = $caption;
  $table->tableClass = "centered-data-table";

  $table->setPrimarySort(new SortDefinition("timestamp", false));
  $table->setLastSort(new SortDefinition("egd_tournament.id", false));

  $table->addColumn("name",
                    "Name",
                    array(array("egd_tournament.name", "name"),
                          array("egd_tournament.id", "id")),
                    function($row){ echo tournamentLink($row["id"], $row["name"]); });

  $table->addColumn("country_code",
                    "Country",
                    array(array("country.code", "country_code")),
                    function($row) { echo $row["country_code"]; },
                    "style=\"text-align:center;\"");

  $table->addColumn("player_count",
                    "Player count",
                    array(array("egd_tournament.player_count", "player_count")),
                    function($row) { echo $row["player_count"]; },
                    "style=\"text-align:right;\"");

  $table->addColumn("game_type_name",
                    "Class",
                    array(array("game_type.name", "game_type_name")),
                    function($row) { echo $row["game_type_name"]; });

  $table->addColumn("timestamp",
                    "Date",
                    array(array("egd_tournament.timestamp", "timestamp")),
                    function($row) { echo date("d. m. Y", strtotime($row["timestamp"])); });

  $table->render();
}
?>
