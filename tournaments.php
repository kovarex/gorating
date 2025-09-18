<?php

$data = query("SELECT
                 egd_tournament.name as name,
                 egd_tournament.player_count as player_count,
                 country.code as country_code,
                 egd_tournament.timestamp as timestamp
               FROM
                 egd_tournament, country
               WHERE
                 egd_tournament.country_id=country.id
               ORDER BY
                 egd_tournament.timestamp DESC, egd_tournament.id DESC
               LIMIT 50");
  
echo "<table class=\"data-table\">";
echo "<tr><th>Name</th><th>Country</th><th>Players</th><th>Date</th></tr>";
while ($row = $data->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".$row["name"]."</td>";
  echo "<td>".$row["country_code"]."</td>";
  echo "<td>".$row["player_count"]."</td>";
  echo "<td>".date("d. m. Y", strtotime($row["timestamp"]))."</td>";
  echo "</tr>";
}
echo "</table>"
?>
