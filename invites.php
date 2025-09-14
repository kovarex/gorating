<?php checkLoggedIn(); ?>
<form method="post" action="invite">
  <table>
    <tr>
      <td><label for="first_name">First name:</label></td>
      <td><input type="text" name="first_name"/></td>
    </tr>
    <tr>
      <td><label for="last_name">Last name:</label></td>
      <td><input type="text" name="last_name"/></td>
    </tr>
    <tr>
      <td><label for="egd_pin">EGD pin:</label></td>
      <td><input type="text" name="egd_pin"/></td>
    </tr>
    <tr>
      <td><label for="rating">Proposed rating (used when egd is not provided):</label></td>
      <td><input type="text" name="rating"/></td>
    </tr>
    <tr>
      <td><label for="email">email:</label></td>
      <td><input type="text" name="email"/></td>
    </tr>
  </table>
  <input type="hidden" name="redirect" value="invites"/>
  <input type="submit" value="invite"/>
</form>

<?php
$result = query("SELECT * FROM invite");
if ($result->num_rows == 0)
{
  echo "No pending invites";
  return;
}

echo "<table class='data-table'><tr><th>Name</th><th>EGD</th><th>email</th></tr>";
while($row = $result->fetch_assoc())
{
  echo "<tr>";
  echo "<td>".$row["first_name"]." ".$row["last_name"]."</td>";
  echo "<td>".egdLink($row["egd_pin"])."</td>";
  echo "<td>".$row["email"]."</td>";
  echo "</tr>";
}
echo "</table>";
?>
