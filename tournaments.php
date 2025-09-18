<?php

$data = query("SELECT
                 egd_tournament.name as egd_tournament_name,
                 country.code as country_code,
                 egd_tournament.timestamp as egd_tournament_timestamp
               FROM
                 egd_tournament, country
               WHERE
                 egd_tournament.country_id=country.id
               ORDER BY
                 egd_tournament.timestamp DESC, egd_tournament.id DESC
               LIMIT 50");
  
echo "<table class=\"data-table\">";
echo "<tr><th>Name</th><th>Country</th><th>Date</th></tr>";
while ($row = $data->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".$row["egd_tournament_name"]."</td>";
  echo "<td>".$row["country_code"]."</td>";
  echo "<td>".date("d. m. Y", strtotime($row["egd_tournament_timestamp"]))."</td>";
  echo "</tr>";
}
echo "</table>"
?>
