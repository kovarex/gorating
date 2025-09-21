<?php

$data = query("SELECT
                 egd_tournament.name as name,
                 egd_tournament.id as id,
                 egd_tournament.player_count as player_count,
                 country.code as country_code,
                 egd_tournament.timestamp as timestamp,
                 game_type.name as game_type_name
               FROM
                 egd_tournament, country, game_type
               WHERE
                 egd_tournament.country_id=country.id and
                 egd_tournament.game_type_id=game_type.id
               ORDER BY
                 egd_tournament.timestamp DESC, egd_tournament.id DESC
               LIMIT 50");

echo "<table class=\"data-table\">";
echo "<tr><th>Name</th><th>Country</th><th>Players</th><th>Type</th><th>Date</th></tr>";
while ($row = $data->fetch_assoc())
{
  echo "<tr>";
  echo "<td><a href=\"/tournament?id=".$row["id"]."\">".$row["name"]."</a></td>";
  echo "<td>".$row["country_code"]."</td>";
  echo "<td>".$row["player_count"]."</td>";
  echo "<td>".$row["game_type_name"]."</td>";
  echo "<td>".date("d. m. Y", strtotime($row["timestamp"]))."</td>";
  echo "</tr>";
}
echo "</table>"
?>
