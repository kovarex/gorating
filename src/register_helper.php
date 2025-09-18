<?php
function checkRegistrationAvailability($inviteID, $secret)
{
  if (empty($inviteID))
    return "Invite ID not specified.";

  $invite = query("SELECT * FROM invite WHERE id=".escape($inviteID))->fetch_assoc();
  if (empty($invite))
    return "Specified invite doesn't exist.";

  if ($invite["secret"] != $secret)
    return "Secret incorrect";

  $existingUserWithSpecifiedEmail = query("SELECT * FROM user WHERE email=".escape($invite["email"]))->fetch_assoc();

  if (!empty($existingUserWithSpecifiedEmail))
    return "User with this email already present.";

  if (!empty($invite["egd_pin"]))
  {
    $existingUserWithSpecifiedEgdPin = query("SELECT * FROM user WHERE egd_pin=".escape($invite["egd_pin"]))->fetch_assoc();
    if (!empty($existingUserWithSpecifiedEgdPin) and
        !empty($existingUserWithSpecifiedEgdPin["register_timestamp"]))
      return "User with this egd pin already present.";
  }
  return true;
}
?>
